<?php

namespace App\Services;

use App\Models\Employee;
use Carbon\Carbon;

class SalnPdsDefaultsService
{
    public const FIELDS = ['family_name', 'first_name', 'middle_initial', 'position', 'agency', 'office_address'];

    public function defaults(Employee $employee, int $year): array
    {
        $pds = app(PdsSnapshotService::class)->draftForYear($employee, $year);
        $personal = $pds->pdsPersonal;
        $family = app(PdsSnapshotService::class)->employeeForYear($employee, $year)->pdsFamily;
        $date = now()->copy()->min(Carbon::create($year, 12, 31)->endOfDay());
        $work = $pds->pdsWorkExperiences->filter(function ($row) use ($date) {
            try {
                return $row->is_gov_service && $row->date_from && Carbon::parse($row->date_from)->lte($date)
                    && (!$row->date_to || Carbon::parse($row->date_to)->gte($date));
            } catch (\Throwable) { return false; }
        })->sortByDesc('date_from')->first();
        $initial = fn ($name) => $this->value($name) === 'N/A' ? 'N/A' : mb_strtoupper(mb_substr(trim($name), 0, 1)).'.';
        return [
            'declarant_info' => [
                'family_name' => $this->value($personal?->surname), 'first_name' => $this->value($personal?->firstname),
                'middle_initial' => $initial($personal?->middlename), 'position' => $this->value($work?->position_title),
                'agency' => $this->value($work?->company), 'office_address' => 'N/A',
            ],
            'spouse_info' => [
                'family_name' => $this->value($family?->spouse_surname), 'first_name' => $this->value($family?->spouse_firstname),
                'middle_initial' => $initial($family?->spouse_middlename), 'position' => $this->value($family?->spouse_occupation),
                'agency' => $this->value($family?->spouse_employer), 'office_address' => $this->value($family?->spouse_business_address),
            ],
        ];
    }

    public function merge(?array $saved, array $defaults): array
    {
        $saved ??= [];
        foreach ($defaults as $field => $value) {
            // PDS identity fields stay linked, including replacing old N/A placeholders.
            if ($value !== 'N/A' || blank($saved[$field] ?? null) || $this->value($saved[$field]) === 'N/A') $saved[$field] = $value;
        }
        return $saved;
    }

    public function mergeLocked(?array $saved, array $defaults, string $group): array
    {
        $result = $this->merge($saved, $defaults);
        foreach (self::FIELDS as $field) {
            if ($group === 'declarant_info' && $field === 'office_address') continue;
            $result[$field] = $defaults[$field];
        }
        return $result;
    }

    public function children(Employee $employee, int $year, string $asOfDate, array $saved = []): array
    {
        $date = Carbon::parse($asOfDate)->startOfDay();
        $pds = app(PdsSnapshotService::class)->employeeForYear($employee, $year);
        $rows = [];
        foreach ($pds->pdsChildren as $child) {
            if (!$child->date_of_birth || blank($child->fullname)) continue;
            $birth = Carbon::parse($child->date_of_birth)->startOfDay();
            $age = (int) $birth->diffInYears($date, false);
            if ($birth->gt($date) || $age < 0 || $age >= 18) continue;
            $rows[] = ['pds_child_id' => $child->id, 'name' => $child->fullname, 'date_of_birth' => $birth->toDateString(), 'age' => $age];
        }
        $names = collect($rows)->pluck('name')->map(fn ($name) => mb_strtolower(trim($name)));
        foreach ($saved as $row) {
            if (!is_array($row) || !empty($row['pds_child_id'])) continue;
            if ($names->contains(mb_strtolower(trim((string) ($row['name'] ?? ''))))) continue;
            $rows[] = $row;
        }
        return $rows;
    }

    public function syncApprovedFamily(Employee $employee, int $year): void
    {
        $snapshotService = app(PdsSnapshotService::class);
        $draft = $snapshotService->draftForYear($employee, $year);
        if ($draft->pdsSectionReviews->firstWhere('section_name', 'Family Background')?->status !== 'approved') return;
        $family = $snapshotService->employeeForYear($employee, $year)->pdsFamily;
        if (!$family) return;
        $initial = $this->value($family->spouse_middlename) === 'N/A' ? 'N/A' : mb_strtoupper(mb_substr(trim($family->spouse_middlename), 0, 1)).'.';
        $fields = ['family_name' => $this->value($family->spouse_surname), 'first_name' => $this->value($family->spouse_firstname),
            'middle_initial' => $initial, 'position' => $this->value($family->spouse_occupation),
            'agency' => $this->value($family->spouse_employer), 'office_address' => $this->value($family->spouse_business_address)];
        \Illuminate\Support\Facades\DB::transaction(function () use ($employee, $year, $fields) {
            foreach ($employee->salns()->whereYear('as_of_date', $year)->lockForUpdate()->get() as $saln) {
                $saln->spouse_info = array_replace($saln->spouse_info ?? [], $fields);
                if ($saln->approved_data !== null) {
                    $approved = $saln->approved_data;
                    $spouse = $approved['spouse_info'] ?? [];
                    $wasJson = is_string($spouse);
                    if ($wasJson) $spouse = json_decode($spouse, true) ?: [];
                    $spouse = array_replace($spouse, $fields);
                    $approved['spouse_info'] = json_encode($spouse);
                    $saln->forceFill(['approved_data' => $approved]);
                }
                if ($saln->isDirty()) $saln->save();
            }
        });
    }

    private function value(?string $value): string
    {
        $value = trim($value ?? '');
        return $value === '' || in_array(strtoupper($value), ['NA', 'N/A', 'NONE', 'NOT APPLICABLE'], true) ? 'N/A' : $value;
    }
}
