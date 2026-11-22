<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeReportSnapshot;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EmployeeReportSnapshotService
{
    public function captureForMonth(?Carbon $month = null): int
    {
        $snapshotMonth = ($month ?? now())->copy()->startOfMonth();
        $captured = 0;

        Employee::query()
            ->with(['user', 'pdsPersonal', 'leaveCredits.leaveType'])
            ->chunkById(100, function ($employees) use ($snapshotMonth, &$captured) {
                foreach ($employees as $employee) {
                    $this->capture($employee, $snapshotMonth);
                    $captured++;
                }
            });

        return $captured;
    }

    public function capture(Employee $employee, Carbon $month): EmployeeReportSnapshot
    {
        $employee->loadMissing(['user', 'pdsPersonal', 'leaveCredits.leaveType']);

        $leaveCredits = $employee->leaveCredits->where('year', $month->year)->map(fn ($credit) => [
            'leave_type' => $credit->leaveType?->name,
            'balance' => (float) $credit->balance,
            'earned' => (float) $credit->earned,
            'carried_forward' => (float) $credit->carried_forward,
            'used' => (float) $credit->used,
            'adjusted' => (float) $credit->adjusted,
        ])->values()->all();

        return EmployeeReportSnapshot::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'snapshot_month' => $month->copy()->startOfMonth()->toDateString(),
            ],
            [
                'data' => [
                    'employee_name' => trim(implode(' ', array_filter([$employee->firstname, $employee->middlename, $employee->lastname]))),
                    'rfid_number' => $employee->rfid_number,
                    'division' => $employee->division,
                    'position' => $employee->position,
                    'employment_status' => $employee->employment_status,
                    'account_status' => $employee->user?->account_status,
                    'gender' => $employee->pdsPersonal?->sex,
                    'service_start_date' => $employee->service_start_date?->toDateString(),
                    'cto_balance' => (float) $employee->cto_balance,
                    'leave_credits' => $leaveCredits,
                ],
            ]
        );
    }
}
