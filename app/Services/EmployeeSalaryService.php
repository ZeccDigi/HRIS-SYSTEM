<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\SalaryRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeSalaryService
{
    public function values(Request $request): array
    {
        if (! $request->hasAny(['salary_grade', 'salary_step', 'salary_schedule_year', 'monthly_salary'])) {
            return [];
        }
        abort_unless(in_array(strtolower($request->user()->role), ['admin', 'hrstaff'], true), 403);
        if (is_string($request->input('monthly_salary'))) {
            $request->merge(['monthly_salary' => str_replace(',', '', $request->input('monthly_salary'))]);
        }
        $data = $request->validate([
            'salary_grade' => 'nullable|string|max:50',
            'salary_step' => 'nullable|required_with:salary_grade|string|max:50',
            'salary_schedule_year' => 'nullable|required_with:salary_grade|integer|min:2000|max:'.now()->year,
            'monthly_salary' => 'nullable|numeric|decimal:0,2|min:0|max:99999999.99',
        ]);
        if (filled($data['salary_grade'] ?? null)) {
            $rate = SalaryRate::where('is_active', true)->where('salary_grade', $data['salary_grade'])
                ->where('salary_step', $data['salary_step'])->where('year', $data['salary_schedule_year'])->first();
            if (! $rate) {
                throw ValidationException::withMessages(['salary_grade' => 'No active salary rate matches this grade, step, and year. Set it up in Utilities first.']);
            }
            $data['monthly_salary'] = $rate->monthly_salary;
        } else {
            $data['salary_step'] = null;
            $data['salary_schedule_year'] = null;
        }

        return $data;
    }

    public function syncPds(Employee $employee, ?array $changedFields = null): void
    {
        $personal = $employee->pdsPersonal()->first();
        $personalValues = [];
        foreach (['lastname' => 'surname', 'firstname' => 'firstname', 'middlename' => 'middlename', 'contact_number' => 'mobile_no', 'notification_email' => 'email_address'] as $source => $target) {
            if (! $personal || $changedFields === null || in_array($source, $changedFields, true) || (blank($personal->{$target}) && filled($employee->{$source}))) {
                $personalValues[$target] = $employee->{$source};
            }
        }
        if ($personalValues) {
            $employee->pdsPersonal()->updateOrCreate([], $personalValues);
        }
        $row = $employee->pdsWorkExperiences()->where('hr_salary_assignment', true)->first();
        if (! $row) {
            $row = $employee->pdsWorkExperiences()->whereNull('date_to')->orderByDesc('date_from')->get()->first(function ($work) {
                return preg_match('/\bdmw\b|\bdepartment of migrant workers\b/i', (string) $work->company) === 1;
            });
        }
        $syncWork = ! $row || $changedFields === null || array_intersect($changedFields, ['service_start_date', 'position', 'employment_status', 'monthly_salary', 'salary_grade', 'salary_step', 'salary_schedule_year']);
        $salaryFields = ['monthly_salary', 'salary_grade', 'salary_step', 'salary_schedule_year'];
        $syncSalary = $changedFields === null || array_intersect($changedFields, $salaryFields);
        $publishedWork = [];
        if ($syncWork) {
            $values = ['date_from' => $employee->service_start_date?->toDateString() ?? $employee->created_at->toDateString(),
                'date_to' => null, 'position_title' => $employee->position ?: 'N/A', 'company' => 'Department of Migrant Workers',
                'monthly_salary' => $employee->monthly_salary, 'salary_grade' => $employee->salary_grade,
                'salary_step' => $employee->salary_step, 'salary_schedule_year' => $employee->salary_schedule_year,
                'appointment_status' => $employee->employment_status, 'is_gov_service' => true, 'hr_salary_assignment' => true];
            $publishedWork = $values;
            if ($row && ! $syncSalary) {
                foreach ($salaryFields as $field) {
                    $values[$field] = $row->{$field};
                    unset($publishedWork[$field]);
                }
            }
            if ($row) {
                $row->update($values);
            } else {
                $row = $employee->pdsWorkExperiences()->create($values);
            }
            $publishedWork = ['id' => $row->id, 'employee_id' => $employee->id, ...$publishedWork];
        }
        // Publish only HR's assignment, preserving unrelated employee edits awaiting review.
        $employee->refresh();
        if ($employee->pds_approved_data === null) {
            app(PdsSnapshotService::class)->ensureApprovedBaseline($employee);
        } else {
            $approved = $employee->pds_approved_data;
            if ($personalValues) {
                $approved['relations']['pdsPersonal'] = array_merge($approved['relations']['pdsPersonal'] ?? [], $personalValues);
            }
            if ($syncWork) {
                $work = collect($approved['relations']['pdsWorkExperiences'] ?? []);
                $found = false;
                $work = $work->map(function ($item) use ($row, $publishedWork, &$found) {
                    if (! empty($item['hr_salary_assignment']) || (int) ($item['id'] ?? 0) === (int) $row->id) {
                        $found = true;

                        return array_merge($item, $publishedWork);
                    }

                    return $item;
                });
                if (! $found) {
                    $work->push($publishedWork);
                }
                $approved['relations']['pdsWorkExperiences'] = $work->values()->all();
            }
            $employee->forceFill(['pds_approved_data' => $approved])->save();
        }
    }

    public function prefillMissingContacts(Employee $employee): void
    {
        if ((int) ($employee->pds_record_year ?? now()->year) !== (int) now()->year) {
            return;
        }
        DB::transaction(function () use ($employee) {
            $employee = Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
            $personal = $employee->pdsPersonal()->firstOrNew();
            $defaults = ['surname' => $employee->lastname, 'firstname' => $employee->firstname, 'middlename' => $employee->middlename,
                'mobile_no' => $employee->contact_number, 'email_address' => $employee->notification_email];
            foreach ($defaults as $field => $value) {
                if (blank($personal->{$field}) && filled($value)) {
                    $personal->{$field} = $value;
                }
            }
            if ($personal->isDirty()) {
                $employee->pdsPersonal()->save($personal);
            }
            if ($employee->pds_approved_data !== null) {
                $data = $employee->pds_approved_data;
                foreach ($defaults as $field => $value) {
                    if (blank($data['relations']['pdsPersonal'][$field] ?? null) && filled($value)) {
                        $data['relations']['pdsPersonal'][$field] = $value;
                    }
                }
                if ($data !== $employee->pds_approved_data) {
                    $employee->forceFill(['pds_approved_data' => $data])->save();
                }
            }
            $snapshot = $employee->pdsSnapshots()->where('year', now()->year)->lockForUpdate()->first();
            if ($snapshot) {
                foreach (['data', 'draft_data'] as $attribute) {
                    $data = $snapshot->{$attribute};
                    if ($data === null) {
                        continue;
                    }
                    foreach ($defaults as $field => $value) {
                        if (blank($data['relations']['pdsPersonal'][$field] ?? null) && filled($value)) {
                            $data['relations']['pdsPersonal'][$field] = $value;
                        }
                    }
                    $snapshot->{$attribute} = $data;
                }
                if ($snapshot->isDirty()) {
                    $snapshot->save();
                }
            }
        });
    }

    public function prefillMissingEmployment(Employee $employee): void
    {
        if ((int) ($employee->pds_record_year ?? now()->year) !== (int) now()->year) {
            return;
        }
        DB::transaction(function () use ($employee) {
            $employee = Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
            $row = $employee->pdsWorkExperiences()->where('hr_salary_assignment', true)->first()
                ?? $employee->pdsWorkExperiences()->whereNull('date_to')->orderByDesc('date_from')->get()->first(fn ($row) => preg_match('/\bdmw\b|\bdepartment of migrant workers\b/i', (string) $row->company));
            if (!$row && blank($employee->position)) return;
            $defaults = ['date_from' => $employee->service_start_date?->toDateString(), 'date_to' => null,
                'position_title' => $employee->position, 'company' => 'Department of Migrant Workers',
                'monthly_salary' => $employee->monthly_salary, 'salary_grade' => $employee->salary_grade,
                'salary_step' => $employee->salary_step, 'salary_schedule_year' => $employee->salary_schedule_year,
                'appointment_status' => $employee->employment_status, 'is_gov_service' => true, 'hr_salary_assignment' => true];
            if (! $row) {
                $row = $employee->pdsWorkExperiences()->make();
            }
            foreach ($defaults as $field => $value) {
                if (blank($row->{$field}) && filled($value)) {
                    $row->{$field} = $value;
                }
            }
            $row->hr_salary_assignment = true;
            if ($row->isDirty()) {
                $employee->pdsWorkExperiences()->save($row);
            }
            $fill = function (array $data) use ($row, $defaults, $employee): array {
                $found = false;
                $rows = collect($data['relations']['pdsWorkExperiences'] ?? [])->map(function ($item) use ($row, $defaults, &$found) {
                    $currentDmw = blank($item['date_to'] ?? null) && preg_match('/\bdmw\b|\bdepartment of migrant workers\b/i', (string) ($item['company'] ?? ''));
                    if (! $found && ((int) ($item['id'] ?? 0) === (int) $row->id || ! empty($item['hr_salary_assignment']) || $currentDmw)) {
                        $found = true;
                        foreach ($defaults as $field => $value) {
                            if (blank($item[$field] ?? null) && filled($value)) {
                                $item[$field] = $value;
                            }
                        }
                    }

                    return $item;
                });
                if (! $found) {
                    $rows->push(['id' => $row->id, 'employee_id' => $employee->id, ...$defaults]);
                }
                $data['relations']['pdsWorkExperiences'] = $rows->values()->all();

                return $data;
            };
            if ($employee->pds_approved_data !== null) {
                $data = $fill($employee->pds_approved_data);
                if ($data !== $employee->pds_approved_data) {
                    $employee->forceFill(['pds_approved_data' => $data])->save();
                }
            }
            $snapshot = $employee->pdsSnapshots()->where('year', now()->year)->lockForUpdate()->first();
            if ($snapshot) {
                foreach (['data', 'draft_data'] as $attribute) {
                    if ($snapshot->{$attribute} !== null) {
                        $snapshot->{$attribute} = $fill($snapshot->{$attribute});
                    }
                }
                if ($snapshot->isDirty()) {
                    $snapshot->save();
                }
            }
        });
    }
}
