<?php

namespace App\Services;

use App\Models\Employee;
use Illuminate\Support\Facades\DB;

class CoreTimeHistoryService
{
    public function assign(Employee $employee, array $attributes): void
    {
        DB::transaction(function () use ($employee, $attributes) {
            $current = Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
            $fields = ['core_time_start_date', 'core_time_end_date', 'core_time_schedule'];
            if (!$current->coreTimeSchedules()->exists()) {
                $current->coreTimeSchedules()->create([
                    ...$current->only($fields), 'applies_from' => '1900-01-01',
                ]);
            }
            // New approvals may apply today or in the future; they never rewrite earlier days.
            $effective = max(now('Asia/Manila')->toDateString(), $attributes['core_time_start_date'] ?? '1900-01-01');
            $current->coreTimeSchedules()->create([
                ...$attributes, 'applies_from' => $effective, 'created_by' => auth()->id(),
            ]);
            $current->update($attributes);
        });
        $employee->refresh();
    }
}
