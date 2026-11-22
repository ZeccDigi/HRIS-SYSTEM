<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\SalaryRate;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class SalaryScheduleService
{
    public function normalizeWorkRows(array $rows): array
    {
        foreach ($rows as $index => &$row) {
            if (blank($row['salary_step'] ?? null) && blank($row['salary_schedule_year'] ?? null)) continue;
            $rate = SalaryRate::where('is_active', true)->where('salary_grade', $row['salary_grade'] ?? '')
                ->where('salary_step', $row['salary_step'] ?? '')->where('year', $row['salary_schedule_year'] ?? 0)->first();
            if (!$rate) throw ValidationException::withMessages(["work_experience.$index.salary_step" => 'Choose a salary grade, step, and year with an active rate in Utilities.']);
            $row['monthly_salary'] = $rate->monthly_salary;
        }
        return $rows;
    }

    public function approvedSalary(Employee $employee): ?array
    {
        // Salary comes from the published PDS, never from an employee's pending changes.
        if ($employee->pds_approved_data !== null) {
            $rows = collect($employee->pds_approved_data['relations']['pdsWorkExperiences'] ?? []);
        } elseif ($employee->pdsSectionReviews()->where('section_name', 'Work Experience')->where('status', 'approved')->exists()) {
            $rows = $employee->pdsWorkExperiences()->get()->map->getAttributes();
        } else {
            return null;
        }
        $row = $rows->filter(function ($row) {
            if (empty($row['is_gov_service']) || blank($row['date_from'] ?? null)
                || (filled($row['salary_schedule_year'] ?? null) && (int) $row['salary_schedule_year'] > now()->year)
                || (float) ($row['monthly_salary'] ?? 0) <= 0) return false;
            try {
                return Carbon::parse($row['date_from'])->startOfDay()->lte(today());
            } catch (\Throwable) { return false; }
        })->sortByDesc(fn ($row) => (float) $row['monthly_salary'])->first();

        if (!$row) return null;

        return [
            'grade' => $row['salary_grade'] ?? null, 'step' => $row['salary_step'] ?? null,
            'year' => filled($row['salary_schedule_year'] ?? null) ? (int) $row['salary_schedule_year'] : null, 'amount' => (float) $row['monthly_salary'],
        ];
    }
}
