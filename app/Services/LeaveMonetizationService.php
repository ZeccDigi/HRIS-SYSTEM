<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveCreditTransaction;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;

class LeaveMonetizationService
{
    public const FACTOR = '0.0481927';

    public function amount(float $salary, float $days): float
    {
        return round($salary * $days * (float) self::FACTOR, 2, PHP_ROUND_HALF_UP);
    }

    public function validateCredits(Employee $employee, LeaveType $type, array $data, int $year, ?int $exceptId = null): float
    {
        $name = strtolower(trim($type->name));
        if (!in_array($name, ['vacation leave', 'sick leave'], true)) $this->fail('leave_type_id', 'Only Vacation Leave and Sick Leave credits can be monetized.');
        app(LeaveCreditService::class)->ensureCredits($employee, $year, now());
        $credit = $employee->leaveCredits()->where('year', $year)->where('leave_type_id', $type->id)->first();
        $pending = $employee->leaveRequests()->where('status', 'pending')->whereKeyNot($exceptId ?? 0)
            ->where(fn ($query) => $query->whereYear('start_date', $year)->orWhere(fn ($q) => $q->where('is_monetization', true)->whereYear('date_filed', $year)))
            ->where(function ($query) use ($type, $name) {
                $query->where('leave_type_id', $type->id);
                if ($name === 'vacation leave') $query->orWhereHas('leaveType', fn ($q) => $q->where('name', 'Mandatory/Force Leave'));
            })->get()->sum(fn ($request) => $request->duration);
        $balance = max(0, (float) ($credit?->balance ?? 0) - $pending);
        $days = (float) ($data['monetization_days'] ?? 0);
        if ($days <= 0 || $days > $balance + 0.0001) $this->fail('monetization_days', 'The requested days exceed the available credits after pending requests.');
        $basis = $data['monetization_basis'] ?? 'regular';
        if ($basis === 'regular') {
            if ($name !== 'vacation leave') $this->fail('monetization_basis', 'Sick Leave requires exceptional monetization with a supporting letter.');
            if ($balance < 15 || $days < 10 || $balance - $days < 5) $this->fail('monetization_days', 'Regular monetization requires at least 15 available VL days, at least 10 days requested, and at least 5 days retained.');
            $annual = $employee->leaveRequests()->where('is_monetization', true)->where('monetization_basis', 'regular')->whereIn('status', ['pending', 'approved'])
                ->whereYear('date_filed', $year)->whereKeyNot($exceptId ?? 0)->sum('monetization_days');
            if ($annual + $days > 30) $this->fail('monetization_days', 'Regular monetization cannot exceed 30 days in the filing year, including pending requests.');
        } elseif ($basis === 'exceptional') {
            $allCredits = $employee->leaveCredits()->with('leaveType')->where('year', $year)->get();
            $vl = (float) ($allCredits->first(fn ($row) => strtolower($row->leaveType?->name ?? '') === 'vacation leave')?->balance ?? 0);
            $sl = (float) ($allCredits->first(fn ($row) => strtolower($row->leaveType?->name ?? '') === 'sick leave')?->balance ?? 0);
            if ($days + 0.0001 < ($vl + $sl) / 2) $this->fail('monetization_days', 'Exceptional monetization must cover at least 50% of accumulated VL and SL credits.');
            if ($name === 'sick leave' && $vl > 0.0001) $this->fail('leave_type_id', 'Vacation Leave credits must be exhausted before monetizing Sick Leave.');
        } else {
            $this->fail('monetization_basis', 'Choose regular or exceptional monetization.');
        }
        return $balance;
    }

    public function post(LeaveRequest $request): void
    {
        if ($request->monetization_posted_at) return;
        $employee = Employee::whereKey($request->employee_id)->lockForUpdate()->firstOrFail();
        $year = Carbon::parse($request->date_filed)->year;
        if (!$request->monetization_salary || !$request->monetization_days || !$request->monetization_verified_at) $this->fail('monetization', 'HR must verify the monetization details and salary before final approval.');
        $available = $this->validateCredits($employee, $request->leaveType, $request->toArray(), $year, $request->id);
        $credit = $employee->leaveCredits()->where('year', $year)->where('leave_type_id', $request->leave_type_id)->lockForUpdate()->firstOrFail();
        $before = (float) $credit->balance;
        $days = (float) $request->monetization_days;
        $credit->used = round((float) $credit->used + $days, 3);
        $credit->balance = round($before - $days, 3);
        $credit->save();
        $request->monetization_credit_before = $before;
        $request->monetization_credit_after = $credit->balance;
        $request->monetization_factor = self::FACTOR;
        $request->monetization_amount = $this->amount((float) $request->monetization_salary, $days);
        $request->monetization_posted_at = now();
        LeaveCreditTransaction::create(['employee_id' => $employee->id, 'leave_type_id' => $request->leave_type_id, 'leave_request_id' => $request->id,
            'type' => 'monetization', 'credit_year' => $year, 'amount' => -$days, 'balance_after' => $credit->balance, 'effective_date' => now()->toDateString(),
            'description' => 'Approved leave-credit monetization', 'metadata' => ['salary' => $request->monetization_salary, 'factor' => self::FACTOR, 'gross_amount' => $request->monetization_amount]]);
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
