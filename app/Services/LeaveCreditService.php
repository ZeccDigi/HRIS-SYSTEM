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
        \Illuminate\Support\Facades\DB::transaction(function () use ($employee, $year, $asOf) {
        Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
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

            $previousEarned = (float) $credit->earned;
            $earned = (float) ($type->days_per_year ?? $credit->balance ?? 0);
            $used = $this->approvedPaidUsageDays($employee, $type, $year);

            $credit->earned = $earned;
            $credit->carried_forward = 0;
            $credit->used = $used;
            $credit->balance = round(max(0, $earned + (float) $credit->adjusted - $used), 3);
            $credit->accrued_as_of = $asOf->toDateString();
            $credit->save();

            if ((float) $previousEarned === 0.0 && $earned > 0) {
                $this->recordTransaction($employee, $type, null, 'entitlement', (float) $credit->earned, (float) $credit->balance, $asOf, 'Annual fixed leave entitlement');
            }
        }
        });
    }

    public function applyApprovedLeave(LeaveRequest $leaveRequest): void
    {
        if ($leaveRequest->is_monetization) {
            app(LeaveMonetizationService::class)->post($leaveRequest);
            return;
        }
        $leaveRequest->loadMissing(['employee', 'leaveType']);
        if (!$leaveRequest->employee || !$leaveRequest->leaveType) return;
        // Include this approval in the usage query before recomputing; the caller owns the transaction.
        if ($leaveRequest->isDirty()) $leaveRequest->save();
        $start = Carbon::parse($leaveRequest->start_date);
        $end = Carbon::parse($leaveRequest->end_date);
        $creditType = $this->creditTypeForUsage($leaveRequest->leaveType);
        for ($year = $start->year; $year <= $end->year; $year++) {
            $this->ensureCredits($leaveRequest->employee, $year, now());
            if (!$leaveRequest->is_paid) continue;
            $from = $start->copy()->max(Carbon::create($year, 1, 1));
            $to = $end->copy()->min(Carbon::create($year, 12, 31));
            $days = LeaveRequest::calculateBusinessDays($from, $to);
            if (!$days || LeaveCreditTransaction::where('leave_request_id', $leaveRequest->id)->where('type', 'usage')->whereYear('effective_date', $year)->exists()) continue;
            $credit = $leaveRequest->employee->leaveCredits()->where('leave_type_id', $creditType->id)->where('year', $year)->first();
            LeaveCreditTransaction::create([
                'employee_id' => $leaveRequest->employee_id, 'leave_type_id' => $creditType->id,
                'leave_request_id' => $leaveRequest->id, 'type' => 'usage', 'credit_year' => $year, 'amount' => -$days,
                'balance_after' => $credit->balance, 'effective_date' => $to->toDateString(),
                'description' => 'Approved paid leave deduction',
                'metadata' => ['year' => $year, 'request_leave_type_id' => $leaveRequest->leave_type_id,
                    'request_leave_type' => $leaveRequest->leaveType?->name,
                    'start_date' => $from->toDateString(), 'end_date' => $to->toDateString()],
            ]);
        }
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
        $opening = $this->carryoverForYear($employee, $type, $year, $asOf);
        $earned = $this->earnedForYear($employee, $type, $year, $asOf);
        $used = $this->approvedPaidUsageDays($employee, $type, $year);

        $credit->earned = $earned;
        $credit->carried_forward = $opening;
        $credit->used = $used;
        $credit->balance = round(max(0, $opening + $earned + (float) $credit->adjusted - $used), 3);
        $credit->accrued_as_of = $asOf->toDateString();
        $credit->save();

        if ($opening > 0 || LeaveCreditTransaction::where('employee_id', $employee->id)->where('leave_type_id', $type->id)->where('type', 'carryover')->whereYear('effective_date', $year)->exists()) {
            LeaveCreditTransaction::updateOrCreate(
                ['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'type' => 'carryover', 'credit_year' => $year],
                ['effective_date' => Carbon::create($year, 1, 1)->toDateString(), 'amount' => $opening, 'balance_after' => $opening, 'description' => 'Unused leave credits carried forward from '.($year - 1), 'metadata' => ['year' => $year, 'source_year' => $year - 1]]
            );
        }
        $delta = round($earned - $previousEarned, 3);
        if (abs($delta) > 0.0001) {
            $this->recordTransaction($employee, $type, null, 'accrual', $delta, (float) $credit->balance, $asOf, 'Organization monthly leave credit accrual');
        }
    }

    private function carryoverForYear(Employee $employee, LeaveType $type, int $year, Carbon $asOf): float
    {
        // Start with recorded credits; do not invent balances for undocumented prior service.
        $previous = $employee->leaveCredits()->where('leave_type_id', $type->id)->where('year', '<', $year)->orderByDesc('year')->first();
        if (!$previous) return 0.0;
        $closeDate = Carbon::create((int) $previous->year, 12, 31)->min($asOf->copy());
        // An undated legacy closing balance is an imported balance, not a reconstructed service history.
        if ($previous->accrued_as_of !== null) $this->recomputeEarnedCredit($employee, $type, $previous, (int) $previous->year, $closeDate);
        $balance = (float) $previous->balance;
        for ($gapYear = (int) $previous->year + 1; $gapYear < $year; $gapYear++) {
            $gap = $employee->leaveCredits()->firstOrCreate(['leave_type_id' => $type->id, 'year' => $gapYear], ['balance' => 0, 'earned' => 0, 'used' => 0, 'adjusted' => 0]);
            $this->recomputeEarnedCredit($employee, $type, $gap, $gapYear, Carbon::create($gapYear, 12, 31)->min($asOf->copy()));
            $balance = (float) $gap->balance;
        }
        return round(max(0, $balance), 3);
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

            // Award the full monthly entitlement from the employee's starting month.
            // Recomputing within a month never adds another monthly award.
            $activeDays = 30;
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

        return $employee->leaveRequests()->withTrashed()
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
        $leaveTypeIds = $this->isVacationLeave($type)
            ? LeaveType::whereIn('name', ['Vacation Leave', 'Mandatory/Force Leave'])->pluck('id')->all()
            : [$type->id];

        return round((float) $employee->leaveRequests()->withTrashed()
            ->whereIn('leave_type_id', $leaveTypeIds)
            ->where('status', 'approved')
            ->where('is_paid', true)
            ->where(function ($query) use ($year) {
                $query->where(fn ($q) => $q->where('is_monetization', false)->whereDate('start_date', '<=', "$year-12-31")->whereDate('end_date', '>=', "$year-01-01"))
                    ->orWhere(fn ($q) => $q->where('is_monetization', true)->whereYear('date_filed', $year));
            })
            ->get()
            ->sum(fn (LeaveRequest $request) => $request->is_monetization ? $request->duration : LeaveRequest::calculateBusinessDays(
                Carbon::parse($request->start_date)->max(Carbon::create($year, 1, 1)),
                Carbon::parse($request->end_date)->min(Carbon::create($year, 12, 31))
            )), 3);
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

    private function creditTypeForUsage(LeaveType $type): LeaveType
    {
        if (! $this->isMandatoryForceLeave($type)) {
            return $type;
        }

        return LeaveType::where('name', 'Vacation Leave')->first() ?? $type;
    }

    private function isVacationLeave(LeaveType $type): bool
    {
        return strtolower(trim($type->name)) === 'vacation leave';
    }

    private function isMandatoryForceLeave(LeaveType $type): bool
    {
        return strtolower(trim($type->name)) === 'mandatory/force leave';
    }

    private function recordTransaction(Employee $employee, LeaveType $type, ?LeaveRequest $leaveRequest, string $transactionType, float $amount, float $balanceAfter, Carbon $effectiveDate, string $description): void
    {
        LeaveCreditTransaction::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $type->id,
            'leave_request_id' => $leaveRequest?->id,
            'type' => $transactionType,
            'credit_year' => $effectiveDate->year,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'effective_date' => $effectiveDate->toDateString(),
            'description' => $description,
        ]);
    }
}
