<?php

namespace App\Services;

use App\Models\DtrRecord;
use App\Models\Employee;
use Carbon\Carbon;

class CocOvertimeVerificationService
{
    /**
     * Determine eligible COC hours from the employee's actual PM time-out and
     * HR-assigned core-time schedule. This deliberately never infers a
     * time-out: an incomplete DTR stays ineligible until it is corrected.
     */
    public function forEmployeeDate(Employee $employee, string|Carbon $date): array
    {
        $workDate = Carbon::parse($date)->startOfDay();
        $record = DtrRecord::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $workDate->toDateString())
            ->first();

        if (! $record?->time_out) {
            return $this->result(false, 'No PM time-out is recorded in the DTR for this date.');
        }

        $scheduledEnd = data_get(
            $employee->core_time_schedule ?? [],
            strtolower($workDate->format('l')).'.pm_end'
        );

        if (! $scheduledEnd) {
            return $this->result(false, 'No PM core time is assigned for this employee on this date.');
        }

        $scheduledEndAt = Carbon::parse($workDate->toDateString().' '.$scheduledEnd);
        $actualTimeOutAt = Carbon::parse($workDate->toDateString().' '.$record->time_out);

        if ($actualTimeOutAt->lessThanOrEqualTo($scheduledEndAt)) {
            return $this->result(
                false,
                'The recorded PM time-out is not later than the assigned core-time end.',
                $scheduledEndAt,
                $actualTimeOutAt
            );
        }

        $minutes = $scheduledEndAt->diffInMinutes($actualTimeOutAt);

        return $this->result(true, null, $scheduledEndAt, $actualTimeOutAt, $minutes);
    }

    private function result(
        bool $eligible,
        ?string $reason = null,
        ?Carbon $scheduledEnd = null,
        ?Carbon $actualTimeOut = null,
        int $minutes = 0
    ): array {
        return [
            'eligible' => $eligible,
            'reason' => $reason,
            'minutes' => $minutes,
            'hours' => round($minutes / 60, 2),
            'scheduled_end' => $scheduledEnd?->format('h:i A'),
            'actual_time_out' => $actualTimeOut?->format('h:i A'),
        ];
    }
}
