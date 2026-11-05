<?php

use App\Http\Controllers\DashboardController;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Http\Request;

test('hr calendar displays mandatory force leave schedules from all employees', function () {
    $hrUser = User::factory()->create(['must_change_password' => false]);
    Employee::create([
        'firstname' => 'Maria',
        'lastname' => 'Reyes',
        'account_role' => 'hrstaff',
        'employment_status' => 'Regular',
        'user_id' => $hrUser->id,
    ]);
    $employee = Employee::create([
        'firstname' => 'Anna',
        'lastname' => 'Garcia',
        'employment_status' => 'Regular',
    ]);
    $mandatoryLeave = LeaveType::create(['name' => 'Mandatory/Force Leave', 'is_active' => true]);
    $vacationLeave = LeaveType::create(['name' => 'Vacation Leave', 'is_active' => true]);

    LeaveRequest::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $mandatoryLeave->id,
        'start_date' => '2027-01-12',
        'end_date' => '2027-01-13',
        'date_filed' => '2027-01-02',
        'reason' => 'Scheduled mandatory leave',
        'status' => 'pending',
    ]);
    LeaveRequest::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $vacationLeave->id,
        'start_date' => '2027-01-12',
        'end_date' => '2027-01-12',
        'date_filed' => '2027-01-02',
        'reason' => 'Vacation',
        'status' => 'pending',
    ]);

    $request = Request::create('/dashboard', 'GET', ['month' => '2027-01']);
    $request->setUserResolver(fn () => $hrUser->fresh());
    $method = new ReflectionMethod(DashboardController::class, 'leaveCalendarData');
    $calendar = $method->invoke(new DashboardController, $request);
    $january12 = $calendar['leaveCalendarDays']->first(
        fn (array $cell) => $cell['date']?->toDateString() === '2027-01-12'
    );

    $calendarHtml = view('dashboard._leave-calendar', $calendar)->render();

    expect($calendar['isHrAdmin'])->toBeTrue()
        ->and($january12['requests'])->toHaveCount(1)
        ->and($january12['requests']->first()->leaveType->name)->toBe('Mandatory/Force Leave')
        ->and($calendarHtml)
        ->toContain('Mandatory/Force Leave Calendar')
        ->toContain('Anna Garcia')
        ->toContain('data-mfl-day')
        ->toContain('data-mfl-panel')
        ->not->toContain('Number indicates employees scheduled that day');
});
