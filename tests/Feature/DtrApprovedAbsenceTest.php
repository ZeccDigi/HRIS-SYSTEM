<?php

use App\Http\Controllers\DtrController;
use App\Models\Employee;
use App\Models\DtrRecord;
use App\Models\DtrMonthRelease;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LocatorSlip;
use App\Models\TravelOrder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

function approvedDtrEvents(Carbon $start, Carbon $end, array $employeeIds)
{
    $method = new ReflectionMethod(DtrController::class, 'approvedAttendanceEvents');

    return $method->invoke(new DtrController, $start, $end, $employeeIds);
}

test('approved monetization does not create an attendance event', function () {
    $employee = Employee::create(['firstname' => 'Ana', 'lastname' => 'Garcia']);
    $type = LeaveType::create(['name' => 'Vacation Leave', 'is_active' => true]);
    LeaveRequest::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $type->id,
        'start_date' => '2026-11-05',
        'end_date' => '2026-11-06',
        'date_filed' => '2026-11-01',
        'reason' => 'Monetization of Leave Credits',
        'status' => 'approved',
    ]);

    expect(approvedDtrEvents(Carbon::parse('2026-11-01'), Carbon::parse('2026-11-30'), [$employee->id]))->toBeEmpty();
});

test('confirmed locator OUT without a return remains valid without inventing return slots', function () {
    $employee = Employee::create(['firstname' => 'Ana', 'lastname' => 'Garcia']);
    $slip = LocatorSlip::create([
        'employee_id' => $employee->id,
        'date_covered' => '2026-11-12',
        'destination' => 'Digos',
        'purpose' => 'Errand',
        'type' => 'Pass Slip',
        'time_from' => '10:00:00',
        'out_scanned_at' => '2026-11-12 10:00:00',
        'time_to' => null,
        'status' => 'approved',
    ]);
    $event = approvedDtrEvents(Carbon::parse('2026-11-01'), Carbon::parse('2026-11-30'), [$employee->id])->get($employee->id.'|2026-11-12');
    expect($event['remarks'])->toBe('Pass Slip')
        ->and($event['slot_labels'])->toBe(['am_out' => 'Pass Slip'])
        ->and($slip->fresh()->time_to)->toBeNull();

    $slip->update(['out_scanned_at' => null]);
    expect(approvedDtrEvents(Carbon::parse('2026-11-01'), Carbon::parse('2026-11-30'), [$employee->id]))->toBeEmpty();
});

test('approved leave appears on covered working days in the DTR', function () {
    $employee = Employee::create([
        'firstname' => 'Ana',
        'lastname' => 'Garcia',
    ]);
    $leaveType = LeaveType::create([
        'name' => 'Vacation Leave',
        'is_active' => true,
    ]);

    $leave = LeaveRequest::create([
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

    $reference = sprintf('LR-2026-%04d', $leave->id);

    expect($events->get($employee->id . '|2026-11-05')['status'])->toBe($reference)
        ->and($events->get($employee->id . '|2026-11-05')['remarks'])->toBe('Leave')
        ->and($events->has($employee->id . '|2026-11-07'))->toBeFalse()
        ->and($events->has($employee->id . '|2026-11-08'))->toBeFalse()
        ->and($events->get($employee->id . '|2026-11-09')['print_label'])->toBe($reference);
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
        'ta_number' => 'TA-2026-11-10-001',
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
        expect($events->get($employee->id . '|2026-11-10')['status'])->toBe('TA-2026-11-10-001')
            ->and($events->get($employee->id . '|2026-11-11')['remarks'])->toBe('TA')
            ->and($events->get($employee->id . '|2026-11-11')['print_label'])->toBe('TA-2026-11-10-001');
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
        'core_time_start_date' => '2026-08-03',
        'core_time_end_date' => '2026-12-04',
        'core_time_schedule' => [
            'tuesday' => [
                'am_start' => '07:30', 'am_end' => '12:00',
                'pm_start' => '13:00', 'pm_end' => '17:00',
            ],
            'monday' => [
                'am_start' => '07:30',
                'am_end' => '12:00',
                'pm_start' => '13:00',
                'pm_end' => '17:00',
            ],
        ],
    ]);
    $leaveType = LeaveType::create(['name' => 'Sick Leave', 'is_active' => true]);

    $leave = LeaveRequest::create([
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

    DtrMonthRelease::create([
        'year' => 2026,
        'month' => 11,
        'released_at' => now(),
        'released_by' => $hrUser->id,
    ]);

    $this->actingAs($hrUser);
    $method = new ReflectionMethod(DtrController::class, 'dtrView');
    $view = $method->invoke(new DtrController, Request::create('/dtr', 'GET', ['date' => '2026-11-03']), false);
    $attendance = $view->getData()['attendanceRecords']->first(fn (array $row) => $row['employee']->id === $employee->id);

    $reference = sprintf('LR-2026-%04d', $leave->id);

    expect($attendance['status'])->toBe($reference)
        ->and($attendance['remarks'])->toBe('Leave')
        ->and($attendance['approved_absence'])->toBeTrue()
        ->and($attendance['hours_worked'])->toBe('0.00');

    Carbon::setTestNow('2026-12-01 00:00:00');

    $response = $this->actingAs($employeeUser)
        ->get(route('my-dtr.index', ['month' => 11, 'year' => 2026]))
        ->assertOk()
        ->assertSee('07:10 AM')
        ->assertSee($reference)
        ->assertSee('Attendance Summary')
        ->assertSee('Final Computation')
        ->assertSee('Aug. 03, 2026 to Dec. 04, 2026')
        ->assertSee('Mon 07:30 AM - 12:00 PM | 01:00 PM - 05:00 PM');

    $this->actingAs($employeeUser)
        ->get(route('my-dtr.print', ['month' => 11, 'year' => 2026]))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf')
        ->assertHeader('content-disposition', 'inline; filename=Daily_Time_Record_2026_11.pdf');

    expect(substr_count($response->getContent(), $reference))->toBeGreaterThanOrEqual(4);

    Carbon::setTestNow();
});

test('approved locator slip fills only covered missing DTR slots with its reference', function () {
    $employee = Employee::create([
        'firstname' => 'Anna',
        'lastname' => 'Garcia',
    ]);

    $locatorSlip = LocatorSlip::create([
        'employee_id' => $employee->id,
        'date_covered' => '2026-11-12',
        'destination' => 'Digos',
        'purpose' => 'Official errand',
        'type' => 'Official Business',
        'time_from' => '09:00:00',
        'out_scanned_at' => '2026-11-12 09:00:00',
        'time_to' => '17:00:00',
        'status' => 'approved',
    ]);

    $events = approvedDtrEvents(
        Carbon::parse('2026-11-01'),
        Carbon::parse('2026-11-30'),
        [$employee->id]
    );

    $reference = 'Official Business';
    $event = $events->get($employee->id . '|2026-11-12');

    expect($event['status'])->toBe($reference)
        ->and($event['remarks'])->toBe('Official Business')
        ->and($event['slot_labels'])->not->toHaveKey('time_in')
        ->and($event['slot_labels']['am_out'])->toBe($reference)
        ->and($event['slot_labels']['pm_in'])->toBe($reference)
        ->and($event['slot_labels']['time_out'])->toBe($reference);
});

test('approved locator slip without a confirmed OUT QR scan leaves the DTR blank', function () {
    $employee = Employee::create([
        'firstname' => 'Anna',
        'lastname' => 'Garcia',
    ]);

    LocatorSlip::create([
        'employee_id' => $employee->id,
        'date_covered' => '2026-11-13',
        'destination' => 'Digos',
        'purpose' => 'Official errand',
        'type' => 'Pass Slip',
        'time_from' => null,
        'out_scanned_at' => null,
        'time_to' => null,
        'status' => 'approved',
    ]);

    $events = approvedDtrEvents(
        Carbon::parse('2026-11-01'),
        Carbon::parse('2026-11-30'),
        [$employee->id]
    );

    expect($events->has($employee->id . '|2026-11-13'))->toBeFalse();
});
