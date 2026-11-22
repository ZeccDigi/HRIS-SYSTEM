<?php

namespace App\Services;

use App\Models\DtrRecord;
use App\Models\Employee;
use Carbon\Carbon;

class AttendanceCalculator
{
    public function sourceFor(?Employee $employee, $date)
    {
        $date = Carbon::parse($date, 'Asia/Manila')->toDateString();
        return $employee?->coreTimeSchedules->filter(fn ($version) => $version->applies_from->toDateString() <= $date)->sortByDesc('id')->first() ?? $employee;
    }

    public function schedule(?Employee $employee, $date): array
    {
        $date = Carbon::parse($date, 'Asia/Manila');
        $source = $this->sourceFor($employee, $date);
        if (($source?->core_time_start_date && $date->toDateString() < $source->core_time_start_date->toDateString())
            || ($source?->core_time_end_date && $date->toDateString() > $source->core_time_end_date->toDateString())) return [];
        $configured = $source?->core_time_schedule;
        return $configured ? ($configured[strtolower($date->format('l'))] ?? [])
            : ($date->isWeekend() ? [] : ['am_start' => '08:00', 'am_end' => '12:00', 'pm_start' => '13:00', 'pm_end' => '17:00']);
    }

    public function isBlankDay(?Employee $employee, $date): bool
    {
        $date = Carbon::parse($date, 'Asia/Manila')->toDateString();
        $schedule = $this->schedule($employee, $date);
        $hasSession = (!empty($schedule['am_start']) && !empty($schedule['am_end']))
            || (!empty($schedule['pm_start']) && !empty($schedule['pm_end']));
        if (\App\Models\Holiday::whereDate('date', $date)->exists()) return true;
        return !$hasSession && !Carbon::parse($date, 'Asia/Manila')->isWeekend();
    }

    private function minute(?string $time): ?float
    {
        if (!$time) return null;
        $time = Carbon::parse($time);
        return $time->hour * 60 + $time->minute + $time->second / 60;
    }

    private function locatorIntervals(?Employee $employee, $date): array
    {
        if (!$employee) return [];
        return \App\Models\LocatorSlip::query()
            ->where('employee_id', $employee->id)->whereDate('date_covered', Carbon::parse($date)->toDateString())
            ->whereIn('status', ['approved', 'approved by chief'])
            ->whereNotNull('out_scanned_at')->whereNotNull('time_from')
            ->get(['time_from', 'time_to'])
            ->map(function ($slip) {
                $from = $this->minute($slip->time_from);
                $to = $this->minute($slip->time_to);
                // A missing return does not establish worked time after departure.
                return [$from, $to !== null && $to >= $from ? $to : 1440];
            })->sortBy(fn ($interval) => $interval[0])->values()->all();
    }

    private function uncoveredMinutes(float $start, float $end, ?Employee $employee, $date): float
    {
        if ($end <= $start) return 0;
        $cursor = $start;
        $minutes = 0;
        foreach ($this->locatorIntervals($employee, $date) as [$awayStart, $awayEnd]) {
            if ($awayEnd <= $cursor || $awayStart >= $end) continue;
            $minutes += max(0, min($awayStart, $end) - $cursor);
            $cursor = max($cursor, min($awayEnd, $end));
        }
        return $minutes + max(0, $end - $cursor);
    }

    public function scheduledMinutes(?Employee $employee, $date): int
    {
        if ($this->isBlankDay($employee, $date)) return 0;
        return $this->assignedMinutes($employee, $date);
    }

    public function assignedMinutes(?Employee $employee, $date): int
    {
        $schedule = $this->schedule($employee, $date);
        $minutes = 0;
        foreach (['am', 'pm'] as $session) {
            $start = $this->minute($schedule[$session.'_start'] ?? null);
            $end = $this->minute($schedule[$session.'_end'] ?? null);
            if ($start !== null && $end !== null) $minutes += max(0, $end - $start);
        }
        return (int) $minutes;
    }

    public function regularMinutes(?DtrRecord $record, ?Employee $employee = null): int
    {
        if (!$record) return 0;
        if ($this->isBlankDay($employee ?? $record->employee, $record->date)) return 0;
        $schedule = $this->schedule($employee ?? $record->employee, $record->date);
        $minutes = 0;
        $spanning = $record->time_in && $record->time_out && !$record->am_out && !$record->pm_in;
        foreach (['am' => ['time_in', 'am_out'], 'pm' => ['pm_in', 'time_out']] as $session => [$in, $out]) {
            $start = $this->minute($schedule[$session.'_start'] ?? null);
            $end = $this->minute($schedule[$session.'_end'] ?? null);
            $arrival = $this->minute($spanning ? $record->time_in : $record->{$in});
            $departure = $this->minute($spanning ? $record->time_out : $record->{$out});
            if ($start !== null && $end !== null && $arrival !== null && $departure !== null) {
                $minutes += max(0, min($end, $departure) - max($start, $arrival));
            }
        }
        return (int) $minutes;
    }

    public function overtimeMinutes(?DtrRecord $record, ?Employee $employee = null): int
    {
        if (!$record || !$record->time_out || (!$record->pm_in && !($record->time_in && !$record->am_out))) return 0;
        if ($this->isBlankDay($employee ?? $record->employee, $record->date)) return 0;
        $schedule = $this->schedule($employee ?? $record->employee, $record->date);
        $end = $this->minute($schedule['pm_end'] ?? null);
        if ($end === null) return 0;
        $arrival = $this->minute($record->pm_in ?: $record->time_in);
        return (int) $this->uncoveredMinutes(max($end, $arrival), $this->minute($record->time_out), $employee ?? $record->employee, $record->date);
    }

    public function actualMinutes(?DtrRecord $record, ?Employee $employee = null): int|float
    {
        if (!$record) return 0;
        $employee ??= $record->employee;
        if ($this->isBlankDay($employee, $record->date)) return 0;
        $intervals = [];
        foreach ([['time_in', 'am_out'], ['pm_in', 'time_out']] as [$in, $out]) {
            $arrival = $this->minute($record->{$in});
            $departure = $this->minute($record->{$out});
            if ($arrival !== null && $departure !== null && $departure > $arrival) $intervals[] = [$arrival, $departure];
        }
        if (!$record->am_out && !$record->pm_in && $record->time_in && $record->time_out) {
            $arrival = $this->minute($record->time_in);
            $departure = $this->minute($record->time_out);
            $schedule = $this->schedule($employee, $record->date);
            $lunchStart = $this->minute($schedule['am_end'] ?? null);
            $lunchEnd = $this->minute($schedule['pm_start'] ?? null);
            if ($lunchStart !== null && $lunchEnd !== null && $lunchEnd > $lunchStart) {
                $intervals = [[$arrival, min($departure, $lunchStart)], [max($arrival, $lunchEnd), $departure]];
            } else {
                $intervals = [[$arrival, $departure]];
            }
        }
        // Approved, QR-confirmed locator coverage counts within assigned work sessions.
        // Merge it with punches so overlapping coverage is never counted twice.
        $schedule = $this->schedule($employee, $record->date);
        foreach ($this->locatorIntervals($employee, $record->date) as [$awayStart, $awayEnd]) {
            foreach (['am', 'pm'] as $session) {
                $sessionStart = $this->minute($schedule[$session.'_start'] ?? null);
                $sessionEnd = $this->minute($schedule[$session.'_end'] ?? null);
                if ($sessionStart !== null && $sessionEnd !== null) {
                    $intervals[] = [max($awayStart, $sessionStart), min($awayEnd, $sessionEnd)];
                }
            }
        }
        usort($intervals, fn ($a, $b) => $a[0] <=> $b[0]);
        $minutes = 0;
        $cursor = 0;
        foreach ($intervals as [$start, $end]) {
            $start = max($start, $cursor);
            if ($end > $start) $minutes += $end - $start;
            $cursor = max($cursor, $end);
        }
        return fmod($minutes, 1.0) === 0.0 ? (int) $minutes : round($minutes, 4);
    }

    public function lateMinutes(?DtrRecord $record, ?Employee $employee = null): int
    {
        if (!$record) return 0;
        if ($this->isBlankDay($employee ?? $record->employee, $record->date)) return 0;
        $schedule = $this->schedule($employee ?? $record->employee, $record->date);
        $minutes = 0;
        foreach (['am' => 'time_in', 'pm' => 'pm_in'] as $session => $slot) {
            $start = $this->minute($schedule[$session.'_start'] ?? null);
            $actual = $this->minute($record->{$slot});
            if ($start !== null && $actual !== null) $minutes += $this->uncoveredMinutes($start + 15, $actual, $employee ?? $record->employee, $record->date);
        }
        return (int) $minutes;
    }

    public function undertimeMinutes(?DtrRecord $record, ?Employee $employee = null): int
    {
        if (!$record) return 0;
        if ($this->isBlankDay($employee ?? $record->employee, $record->date)) return 0;
        $schedule = $this->schedule($employee ?? $record->employee, $record->date);
        $minutes = 0;
        foreach (['am' => 'am_out', 'pm' => 'time_out'] as $session => $slot) {
            $end = $this->minute($schedule[$session.'_end'] ?? null);
            $actual = $this->minute($record->{$slot});
            if ($end !== null && $actual !== null) $minutes += $this->uncoveredMinutes($actual, $end, $employee ?? $record->employee, $record->date);
        }
        return (int) $minutes;
    }

    public function hasDayEnded($date): bool
    {
        return Carbon::parse($date, 'Asia/Manila')->toDateString() < now('Asia/Manila')->toDateString();
    }

    public function isIncomplete(?DtrRecord $record, ?Employee $employee, $date, array $coveredSlots = []): bool
    {
        if ($this->isBlankDay($employee, $date)) return false;
        if (!$this->hasDayEnded($date)) return false;
        $schedule = $this->schedule($employee, $date);
        foreach (['am' => ['time_in', 'am_out'], 'pm' => ['pm_in', 'time_out']] as $session => $slots) {
            if (empty($schedule[$session.'_start']) || empty($schedule[$session.'_end'])) continue;
            foreach ($slots as $slot) {
                if (!$record?->{$slot} && empty($coveredSlots[$slot])) return true;
            }
        }
        return false;
    }

    public function flags(?DtrRecord $record, ?Employee $employee = null): array
    {
        if (!$record) return [];
        $schedule = $this->schedule($employee ?? $record->employee, $record->date);
        $flags = [];
        if (!empty($schedule['am_start']) && !empty($schedule['am_end']) && !$record->time_in && $record->pm_in) $flags[] = 'Missing AM attendance';
        if ($record->time_in && !$record->am_out && $record->pm_in) $flags[] = 'Missing AM OUT';
        if ($record->am_out && !$record->pm_in && $record->time_out && !empty($schedule['pm_start'])) $flags[] = 'Missing PM IN';
        if ($record->pm_in && !$record->time_out && Carbon::parse($record->date.' '.($schedule['pm_end'] ?? '23:59'), 'Asia/Manila')->lt(now('Asia/Manila'))) $flags[] = 'Missing PM OUT';
        return $flags;
    }
}
