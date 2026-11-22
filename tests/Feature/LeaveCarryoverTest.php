<?php

use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\LeaveCreditTransaction;
use App\Services\LeaveCreditService;
use Carbon\Carbon;

test('VL and SL carry recorded closing balances once while fixed yearly allowances reset', function () {
    $this->travelTo(Carbon::parse('2027-01-01'));
    $employee = Employee::create(['firstname' => 'Carry', 'lastname' => 'Owner', 'service_start_date' => '2026-01-01']);
    $vl = LeaveType::create(['name' => 'Vacation Leave', 'is_active' => true]);
    $sl = LeaveType::create(['name' => 'Sick Leave', 'is_active' => true]);
    $force = LeaveType::create(['name' => 'Mandatory/Force Leave', 'is_active' => true, 'days_per_year' => 5]);
    foreach ([[$vl, 8], [$sl, 3], [$force, 2]] as [$type, $balance]) $employee->leaveCredits()->create(['leave_type_id' => $type->id, 'year' => 2026, 'balance' => $balance, 'earned' => 0, 'used' => 0, 'adjusted' => 0]);
    $service = app(LeaveCreditService::class);
    foreach (range(1, 3) as $_) $service->ensureCredits($employee, 2027);
    $credits = $employee->leaveCredits()->where('year', 2027)->get()->keyBy('leave_type_id');
    expect((float) $credits[$vl->id]->balance)->toBe(9.25)->and((float) $credits[$sl->id]->balance)->toBe(4.25)
        ->and((float) $credits[$vl->id]->earned)->toBe(1.25)->and((float) $credits[$vl->id]->carried_forward)->toBe(8.0)
        ->and((float) $credits[$force->id]->balance)->toBe(5.0)->and((float) $credits[$force->id]->carried_forward)->toBe(0.0)
        ->and(LeaveCreditTransaction::where('type', 'carryover')->count())->toBe(2);
});

test('force leave deducts VL once and archiving preserves the deduction and carryover', function () {
    $this->travelTo(Carbon::parse('2027-01-04'));
    $employee = Employee::create(['firstname' => 'Force', 'lastname' => 'Owner', 'service_start_date' => '2026-01-01']);
    $vl = LeaveType::create(['name' => 'Vacation Leave', 'is_active' => true]);
    $force = LeaveType::create(['name' => 'Mandatory/Force Leave', 'is_active' => true, 'days_per_year' => 5]);
    $employee->leaveCredits()->create(['leave_type_id' => $vl->id, 'year' => 2026, 'balance' => 8, 'earned' => 0, 'used' => 0, 'adjusted' => 0]);
    $leave = LeaveRequest::create(['employee_id' => $employee->id, 'leave_type_id' => $force->id, 'date_filed' => '2027-01-01', 'start_date' => '2027-01-04', 'end_date' => '2027-01-04', 'status' => 'pending', 'is_paid' => true, 'reason' => 'Force leave']);
    $leave->status = 'approved';
    $service = app(LeaveCreditService::class);
    $service->applyApprovedLeave($leave);
    $service->applyApprovedLeave($leave);
    $leave->delete();
    $service->ensureCredits($employee, 2027);
    expect((float) $employee->leaveCredits()->where('year', 2027)->where('leave_type_id', $vl->id)->value('balance'))->toBe(8.25)
        ->and((float) $employee->leaveCredits()->where('year', 2027)->where('leave_type_id', $force->id)->value('balance'))->toBe(4.0)
        ->and(LeaveCreditTransaction::where('leave_request_id', $leave->id)->where('type', 'usage')->count())->toBe(1);
});

test('December January leave is split across years and completed prior year accrual is carried', function () {
    $this->travelTo(Carbon::parse('2026-11-21'));
    $employee = Employee::create(['firstname' => 'Year', 'lastname' => 'Boundary', 'service_start_date' => '2026-01-01']);
    $vl = LeaveType::create(['name' => 'Vacation Leave', 'is_active' => true]);
    $service = app(LeaveCreditService::class);
    $service->ensureCredits($employee, 2026);
    $this->travelTo(Carbon::parse('2027-01-02'));
    $leave = LeaveRequest::create(['employee_id' => $employee->id, 'leave_type_id' => $vl->id, 'date_filed' => '2026-11-21', 'start_date' => '2026-12-31', 'end_date' => '2027-01-01', 'status' => 'pending', 'is_paid' => true, 'reason' => 'Year boundary']);
    $leave->status = 'approved';
    $service->applyApprovedLeave($leave);
    $credits = $employee->leaveCredits()->get()->keyBy('year');
    expect((float) $credits[2026]->earned)->toBe(15.0)->and((float) $credits[2026]->used)->toBe(1.0)
        ->and((float) $credits[2027]->carried_forward)->toBe(14.0)->and((float) $credits[2027]->used)->toBe(1.0)
        ->and((float) $credits[2027]->balance)->toBe(14.25)
        ->and(LeaveCreditTransaction::where('leave_request_id', $leave->id)->where('type', 'usage')->count())->toBe(2);
});

test('missing intermediate years are calculated without fabricating earlier undocumented balances', function () {
    $this->travelTo(Carbon::parse('2027-01-01'));
    $employee = Employee::create(['firstname' => 'Gap', 'lastname' => 'Years', 'service_start_date' => '2025-01-01']);
    $vl = LeaveType::create(['name' => 'Vacation Leave', 'is_active' => true]);
    $employee->leaveCredits()->create(['leave_type_id' => $vl->id, 'year' => 2025, 'balance' => 8, 'earned' => 0, 'used' => 0, 'adjusted' => 0]);
    $service = app(LeaveCreditService::class);
    $service->ensureCredits($employee, 2027);
    $service->ensureCredits($employee, 2027);
    expect((float) $employee->leaveCredits()->where('year', 2027)->value('balance'))->toBe(24.25);
    $new = Employee::create(['firstname' => 'No', 'lastname' => 'History', 'service_start_date' => '2000-01-01']);
    $service->ensureCredits($new, 2027);
    expect((float) $new->leaveCredits()->where('year', 2027)->value('balance'))->toBe(1.25);
});
