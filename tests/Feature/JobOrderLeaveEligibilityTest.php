<?php

use App\Http\Controllers\MyLeaveController;
use App\Models\Employee;
use App\Models\LeaveType;

function jobOrderIsEligibleFor(LeaveType $leaveType, Employee $employee): bool
{
    $method = new ReflectionMethod(MyLeaveController::class, 'isLeaveTypeEligibleForEmployee');

    return $method->invoke(new MyLeaveController, $leaveType, $employee);
}

test('job order employees can avail only wellness leave', function () {
    $employee = Employee::create([
        'firstname' => 'Job',
        'lastname' => 'Order',
        'employment_status' => 'Job Order',
    ]);

    $wellness = LeaveType::create(['name' => 'Wellness Leave', 'is_active' => true]);
    $vacation = LeaveType::create(['name' => 'Vacation Leave', 'is_active' => true]);
    $sick = LeaveType::create(['name' => 'Sick Leave', 'is_active' => true]);
    $mandatory = LeaveType::create(['name' => 'Mandatory/Force Leave', 'is_active' => true]);
    $specialPrivilege = LeaveType::create(['name' => 'Special Privilege Leave', 'is_active' => true]);

    expect(jobOrderIsEligibleFor($wellness, $employee))->toBeTrue()
        ->and(jobOrderIsEligibleFor($vacation, $employee))->toBeFalse()
        ->and(jobOrderIsEligibleFor($sick, $employee))->toBeFalse()
        ->and(jobOrderIsEligibleFor($mandatory, $employee))->toBeFalse()
        ->and(jobOrderIsEligibleFor($specialPrivilege, $employee))->toBeFalse();
});

test('regular employees retain access to standard and wellness leave types', function () {
    $employee = Employee::create([
        'firstname' => 'Regular',
        'lastname' => 'Employee',
        'employment_status' => 'Regular',
    ]);

    $wellness = LeaveType::create(['name' => 'Wellness Leave', 'is_active' => true]);
    $vacation = LeaveType::create(['name' => 'Vacation Leave', 'is_active' => true]);

    expect(jobOrderIsEligibleFor($wellness, $employee))->toBeTrue()
        ->and(jobOrderIsEligibleFor($vacation, $employee))->toBeTrue();
});
