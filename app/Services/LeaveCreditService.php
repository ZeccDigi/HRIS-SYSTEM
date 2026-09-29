<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveCredit;
use App\Models\LeaveCreditTransaction;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;

class LeaveCreditService
{
    private const MONTHLY_VL_SL_CREDIT = 1.25;

    public function ensureCredits(Employee $employee, int $year, ?Carbon $asOf = null): void
    {
        $asOf ??= now();
        $types = LeaveType::where('is_active', true)->get();

        foreach ($types as $type) {
            $credit = $employee->leaveCredits()->firstOrCreate(
                ['leave_type_id' => $type->id, 'year' => $year],
                ['balance' => 0, 'earned' => 0, 'used' => 0, 'adjusted' => 0]
            );

            if ($this->isMonthlyEarnedType($type)) {
                $this->recomputeEarnedCredit($employee, $type, $credit, $year, $asOf);
                continue;
            }

            if ((float) $credit->earned === 0.0 && (float) $credit->used === 0.0) {
                $earned = (float) ($type->days_per_year ?? $credit->balance ?? 0);
                $used = $this->approvedPaidUsageDays($employee, $type, $year);
                $credit->earned = $earned;
                $credit->used = $used;
                $credit->balance = round(max(0, $earned + (float) $credit->adjusted - $used), 3);
                $credit->accrued_as_of = $asOf->toDateString();
                $credit->save();

                $this->recordTransaction($employee, $type, null, 'entitlement', (float) $credit->earned, (float) $credit->balance, $asOf, 'Annual fixed leave entitlement');
            }
        }
    }

    public function applyApprovedLeave(LeaveRequest $leaveRequest): void
    {
        $leaveRequest->loadMissing(['employee', 'leaveType']);

        if (! $leaveRequest->employee || ! $leaveRequest->leaveType) {
            return;
        }

        $year = Carbon::parse($leaveRequest->start_date)->year;
        $this->ensureCredits($leaveRequest->employee, $year, Carbon::parse($leaveRequest->end_date));

        if (! $leaveRequest->is_paid) {
            $this->ensureCredits($leaveRequest->employee, $year, now());
            return;
        }

        $credit = $leaveRequest->employee->leaveCredits()
            ->where('leave_type_id', $leaveRequest->leave_type_id)
            ->where('year', $year)
            ->first();

        if (! $credit || LeaveCreditTransaction::where('leave_request_id', $leaveRequest->id)->where('type', 'usage')->exists()) {
            return;
        }

        $days = (float) $leaveRequest->duration;
        $credit->used = round((float) $credit->used + $days, 3);
        $credit->balance = round(max(0, (float) $credit->earned + (float) $credit->adjusted - (float) $credit->used), 3);
        $credit->save();

        LeaveCreditTransaction::create([
            'employee_id' => $leaveRequest->employee_id,
            'leave_type_id' => $leaveRequest->leave_type_id,
            'leave_request_id' => $leaveRequest->id,
            'type' => 'usage',
            'amount' => -$days,
            'balance_after' => $credit->balance,
            'effective_date' => Carbon::parse($leaveRequest->end_date)->toDateString(),
            'description' => 'Approved paid leave deduction',
            'metadata' => [
                'start_date' => Carbon::parse($leaveRequest->start_date)->toDateString(),
                'end_date' => Carbon::parse($leaveRequest->end_date)->toDateString(),
            ],
        ]);
    }

    public static function dayCreditForDays(float $days): float
    {
        $days = max(0, min(30, $days));

        return round($days * self::MONTHLY_VL_SL_CREDIT / 30, 3);
    }

    public static function dayFractionForMinutes(int $minutes): float
    {
        return round(max(0, $minutes) / 480, 3);
    }

    public static function roundedLeaveDay(float $dayFraction): float
    {
        if ($dayFraction >= 0.75) {
            return 1.0;
        }

        if ($dayFraction >= 0.25) {
            return 0.5;
        }

        return 0.0;
    }

    private function recomputeEarnedCredit(Employee $employee, LeaveType $type, LeaveCredit $credit, int $year, Carbon $asOf): void
    {
        $previousEarned = (float) $credit->earned;
        $earned = $this->earnedForYear($employee, $type, $year, $asOf);
        $used = $this->approvedPaidUsageDays($employee, $type, $year);

        $credit->earned = $earned;
        $credit->used = $used;
        $credit->balance = round(max(0, $earned + (float) $credit->adjusted - $used), 3);
        $credit->accrued_as_of = $asOf->toDateString();
        $credit->save();

        $delta = round($earned - $previousEarned, 3);
        if (abs($delta) > 0.0001) {
            $this->recordTransaction($employee, $type, null, 'accrual', $delta, (float) $credit->balance, $asOf, 'Organization monthly/daily leave credit accrual');
        }
    }

    private function earnedForYear(Employee $employee, LeaveType $type, int $year, Carbon $asOf): float
    {
        $serviceStart = $this->serviceStartDate($employee);
        $yearStart = Carbon::create($year, 1, 1)->startOfDay();
        $yearEnd = Carbon::create($year, 12, 31)->startOfDay();
        $end = $asOf->copy()->startOfDay()->min($yearEnd);
        $start = $serviceStart->max($yearStart);

        if ($start->gt($end)) {
            return 0.0;
        }

        $unpaidLeaveDays = $this->unpaidLeaveDaysByMonth($employee, $type, $year);
        $earned = 0.0;

        for ($month = $start->copy()->startOfMonth(); $month->lte($end); $month->addMonth()) {
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth()->startOfDay();
            $activeStart = $start->copy()->max($monthStart);
            $activeEnd = $end->copy()->min($monthEnd);

            if ($activeStart->gt($activeEnd)) {
                continue;
            }

            $activeDays = $activeStart->isSameDay($monthStart) && $activeEnd->isSameDay($monthEnd)
                ? 30
                : min(30, $activeStart->diffInDays($activeEnd) + 1);
            $lwopDays = (float) ($unpaidLeaveDays[$month->format('Y-m')] ?? 0);
            $creditableDays = max(0, $activeDays - $lwopDays);
            $earned += self::dayCreditForDays($creditableDays);
        }

        return round($earned, 3);
    }

    private function unpaidLeaveDaysByMonth(Employee $employee, LeaveType $type, int $year): Collection
    {
        $holidays = Holiday::whereYear('date', $year)
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))
            ->all();

        return $employee->leaveRequests()
            ->with('leaveType')
            ->where('status', 'approved')
            ->where('is_paid', false)
            ->whereYear('start_date', '<=', $year)
            ->whereYear('end_date', '>=', $year)
            ->get()
            ->reject(fn (LeaveRequest $request) => str_contains(strtolower($request->leaveType?->name ?? ''), 'sick'))
            ->flatMap(function (LeaveRequest $request) use ($year, $holidays) {
                $start = Carbon::parse($request->start_date)->max(Carbon::create($year, 1, 1));
                $end = Carbon::parse($request->end_date)->min(Carbon::create($year, 12, 31));

                return collect(CarbonPeriod::create($start, $end))
                    ->filter(fn (Carbon $date) => $date->isWeekday() && ! in_array($date->format('Y-m-d'), $holidays, true))
                    ->map(fn (Carbon $date) => $date->format('Y-m'));
            })
            ->countBy();
    }

    private function approvedPaidUsageDays(Employee $employee, LeaveType $type, int $year): float
    {
        return round((float) $employee->leaveRequests()
            ->where('leave_type_id', $type->id)
            ->where('status', 'approved')
            ->where('is_paid', true)
            ->whereYear('start_date', $year)
            ->get()
            ->sum(fn (LeaveRequest $request) => $request->duration), 3);
    }

    private function serviceStartDate(Employee $employee): Carbon
    {
        return Carbon::parse($employee->service_start_date ?? $employee->created_at ?? now())->startOfDay();
    }

    private function isMonthlyEarnedType(LeaveType $type): bool
    {
        $name = strtolower($type->name);

        return str_contains($name, 'vacation') || str_contains($name, 'sick');
    }

    private function recordTransaction(Employee $employee, LeaveType $type, ?LeaveRequest $leaveRequest, string $transactionType, float $amount, float $balanceAfter, Carbon $effectiveDate, string $description): void
    {
        LeaveCreditTransaction::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'leave_request_id' => $leaveRequest?->id,
            'type' => $transactionType,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'effective_date' => $effectiveDate->toDateString(),
            'description' => $description,
        ]);
    }
}
