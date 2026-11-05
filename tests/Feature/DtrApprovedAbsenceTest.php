<?php

use App\Http\Controllers\DtrController;
use App\Models\Employee;
use App\Models\DtrRecord;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\TravelOrder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

function approvedDtrEvents(Carbon $start, Carbon $end, array $employeeIds)
{
    $method = new ReflectionMethod(DtrController::class, 'approvedAttendanceEvents');

    return $method->invoke(new DtrController, $start, $end, $employeeIds);
}

test('approved leave appears on covered working days in the DTR', function () {
    $employee = Employee::create([
        'firstname' => 'Ana',
        'lastname' => 'Garcia',
    ]);
    $leaveType = LeaveType::create([
        'name' => 'Vacation Leave',
        'is_active' => true,
    ]);

    LeaveRequest::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-11-05',
        'end_date' => '2026-11-09',
        'date_filed' => '2026-11-03',
        'reason' => 'Personal leave',
        'status' => 'approved',
    ]);

    $events = approvedDtrEvents(
        Carbon::parse('2026-11-01'),
        Carbon::parse('2026-11-30'),
        [$employee->id]
    );

    expect($events->get($employee->id . '|2026-11-05')['status'])->toBe('VL Leave')
        ->and($events->get($employee->id . '|2026-11-05')['remarks'])->toBe('VL Leave')
        ->and($events->has($employee->id . '|2026-11-07'))->toBeFalse()
        ->and($events->has($employee->id . '|2026-11-08'))->toBeFalse()
        ->and($events->get($employee->id . '|2026-11-09')['print_label'])->toBe('VL LEAVE');
});

test('approved travel authority appears for the requester and companion', function () {
    $requester = Employee::create([
        'firstname' => 'Min',
        'lastname' => 'Yoon',
    ]);
    $companion = Employee::create([
        'firstname' => 'Anna',
        'lastname' => 'Garcia',
    ]);

    $travelOrder = TravelOrder::create([
        'employee_id' => $requester->id,
        'travel_type' => 'local',
        'travel_date_start' => '2026-11-10',
        'travel_date_end' => '2026-11-11',
        'places_of_travel' => 'Cebu City',
        'purpose' => 'Official meeting',
        'status' => 'approved',
    ]);
    $travelOrder->companions()->attach($companion->id);

    $events = approvedDtrEvents(
        Carbon::parse('2026-11-01'),
        Carbon::parse('2026-11-30'),
        [$requester->id, $companion->id]
    );

    foreach ([$requester, $companion] as $employee) {
        expect($events->get($employee->id . '|2026-11-10')['status'])->toBe('TA')
            ->and($events->get($employee->id . '|2026-11-11')['remarks'])->toBe('TA')
            ->and($events->get($employee->id . '|2026-11-11')['print_label'])->toBe('TA');
    }
});

test('approved sick leave overrides a conflicting raw scan in HR and employee DTR displays', function () {
    $hrUser = User::factory()->create(['must_change_password' => false]);
    Employee::create([
        'firstname' => 'HR',
        'lastname' => 'Reviewer',
        'account_role' => 'hrstaff',
        'user_id' => $hrUser->id,
    ]);

    $employeeUser = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::create([
        'firstname' => 'Anna',
        'lastname' => 'Garcia',
        'account_role' => 'employee',
        'user_id' => $employeeUser->id,
    ]);
    $leaveType = LeaveType::create(['name' => 'Sick Leave', 'is_active' => true]);

    LeaveRequest::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-11-03',
        'end_date' => '2026-11-03',
        'date_filed' => '2026-11-03',
        'reason' => 'Medical rest',
        'status' => 'approved',
        'chief_status' => 'approved',
        'hrstaff_status' => 'approved',
        'rd_status' => 'approved',
    ]);

    DtrRecord::create([
        'employee_id' => $employee->id,
        'date' => '2026-11-03',
        'time_in' => '07:10:01',
        'status' => 'present',
    ]);

    $this->actingAs($hrUser);
    $method = new ReflectionMethod(DtrController::class, 'dtrView');
    $view = $method->invoke(new DtrController, Request::create('/dtr', 'GET', ['date' => '2026-11-03']), false);
    $attendance = $view->getData()['attendanceRecords']->first(fn (array $row) => $row['employee']->id === $employee->id);

    expect($attendance['status'])->toBe('SL Leave')
        ->and($attendance['remarks'])->toBe('SL Leave')
        ->and($attendance['approved_absence'])->toBeTrue()
        ->and($attendance['hours_worked'])->toBe('0.00');

    $response = $this->actingAs($employeeUser)
        ->get(route('my-dtr.index', ['month' => 11, 'year' => 2026]))
        ->assertOk()
        ->assertSee('07:10 AM')
        ->assertSee('SL LEAVE');

    expect(substr_count($response->getContent(), 'SL Leave'))->toBeGreaterThanOrEqual(4)
        ->and(substr_count($response->getContent(), 'SL LEAVE'))->toBeGreaterThanOrEqual(3);
});
