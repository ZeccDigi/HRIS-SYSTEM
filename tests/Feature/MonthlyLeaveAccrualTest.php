<?php

use App\Models\Employee;
use App\Models\LeaveType;
use App\Services\LeaveCreditService;
use Carbon\Carbon;

test('VL and SL award one full starting month without daily growth or duplicate awards', function () {
    $employee = Employee::create(['firstname' => 'New', 'lastname' => 'Employee', 'service_start_date' => '2026-11-21']);
    foreach (['Vacation Leave', 'Sick Leave'] as $name) LeaveType::create(['name' => $name, 'is_active' => true]);
    $service = app(LeaveCreditService::class);
    $service->ensureCredits($employee, 2026, Carbon::parse('2026-11-20'));
    expect($employee->leaveCredits()->sum('earned'))->toBe(0);
    foreach (['2026-11-21', '2026-11-22', '2026-11-30'] as $date) {
        $service->ensureCredits($employee, 2026, Carbon::parse($date));
        foreach ($employee->leaveCredits()->get() as $credit) expect((float) $credit->earned)->toBe(1.25);
    }
    $service->ensureCredits($employee, 2026, Carbon::parse('2026-12-01'));
    foreach ($employee->leaveCredits()->get() as $credit) expect((float) $credit->earned)->toBe(2.5);
});
