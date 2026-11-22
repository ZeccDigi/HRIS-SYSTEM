<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\Holiday;
use Carbon\Carbon;

class CocEntitlementService
{
    public function calculate(Employee $employee, string $date, float $hours): array
    {
        $date = Carbon::parse($date, 'Asia/Manila')->toDateString();
        $schedule = app(AttendanceCalculator::class)->schedule($employee, $date);
        $workday = (!empty($schedule['am_start']) && !empty($schedule['am_end']))
            || (!empty($schedule['pm_start']) && !empty($schedule['pm_end']));
        $holiday = Holiday::whereDate('date', $date)->exists();
        $rate = $holiday || !$workday ? 1.5 : 1.0;
        return [
            'rendered_hours' => round($hours, 2), 'coc_rate' => $rate,
            'overtime_classification' => $holiday ? 'Holiday' : ($workday ? 'Scheduled workday' : 'Scheduled day off'),
            'hours' => round($hours * $rate, 2),
        ];
    }
}
