<?php

use App\Models\CtoRequest;
use App\Models\DtrRecord;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\TravelOrder;
use App\Models\User;
use App\Services\AttendanceCalculator;

test('incomplete remarks wait until midnight in the Philippines', function () {
    $employee = Employee::create(['firstname' => 'Ana', 'lastname' => 'Test']);
    $calculator = new AttendanceCalculator;
    $this->travelTo(\Carbon\Carbon::parse('2026-11-18 23:59:59', 'Asia/Manila'));
    expect($calculator->isIncomplete(null, $employee, '2026-11-18'))->toBeFalse()
        ->and($calculator->hasDayEnded('2026-11-19'))->toBeFalse();
    $this->travelTo(\Carbon\Carbon::parse('2026-11-19 00:00:00', 'Asia/Manila'));
    expect($calculator->isIncomplete(null, $employee, '2026-11-18'))->toBeTrue();
});

test('missing scheduled punches are incomplete including an entirely blank day', function () {
    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::create(['firstname' => 'Ana', 'lastname' => 'Test', 'account_role' => 'employee', 'user_id' => $user->id]);
    $calculator = new AttendanceCalculator;
    $this->travelTo(\Carbon\Carbon::parse('2026-12-01', 'Asia/Manila'));
    $record = DtrRecord::create(['employee_id' => $employee->id, 'date' => '2026-11-18', 'time_in' => '08:00:00', 'status' => 'absent']);
    expect($calculator->isIncomplete($record, $employee, '2026-11-18'))->toBeTrue()
        ->and($calculator->isIncomplete(null, $employee, '2026-11-19'))->toBeTrue();
    $this->actingAs($user)->get(route('my-dtr.index', ['month' => 11, 'year' => 2026]))
        ->assertOk()->assertDontSee('Incomplete')->assertDontSee('>absent<', false);
});

test('afternoon only completed attendance does not require AM punches', function () {
    $employee = Employee::create(['firstname' => 'Ana', 'lastname' => 'Test', 'core_time_schedule' => [
        'wednesday' => ['am_start' => null, 'am_end' => null, 'pm_start' => '13:00', 'pm_end' => '17:00'],
    ]]);
    $this->travelTo(\Carbon\Carbon::parse('2026-12-01', 'Asia/Manila'));
    $record = new DtrRecord(['date' => '2026-11-18', 'pm_in' => '13:00:00', 'time_out' => '17:00:00']);
    expect((new AttendanceCalculator)->isIncomplete($record, $employee, $record->date))->toBeFalse();
});

test('approved application references fill missing slots and preserve an existing AM IN', function (string $kind, bool $hasScan) {
    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::create(['firstname' => 'Ana', 'lastname' => 'Test', 'account_role' => 'employee', 'user_id' => $user->id]);
    $date = '2026-11-18';
    if ($kind === 'leave') {
        $type = LeaveType::create(['name' => 'Vacation Leave', 'is_active' => true]);
        $application = LeaveRequest::create([
            'employee_id' => $employee->id, 'leave_type_id' => $type->id,
            'start_date' => $date, 'end_date' => $date, 'date_filed' => $date,
            'reason' => 'Vacation', 'status' => 'approved',
        ]);
        $reference = sprintf('LR-2026-%04d', $application->id);
    } elseif ($kind === 'ta') {
        $application = TravelOrder::create([
            'employee_id' => $employee->id, 'ta_number' => 'TA-2026-11-18-001', 'travel_type' => 'local',
            'travel_date_start' => $date, 'travel_date_end' => $date,
            'places_of_travel' => 'Digos', 'purpose' => 'Meeting', 'status' => 'approved',
        ]);
        $reference = $application->ta_number;
    } else {
        $application = CtoRequest::create([
            'employee_id' => $employee->id, 'type' => 'use', 'date_start' => $date, 'date_end' => $date,
            'hours' => 8, 'purpose' => 'Use credits', 'status' => 'approved',
        ]);
        $reference = $application->reference;
    }
    if ($hasScan) DtrRecord::create(['employee_id' => $employee->id, 'date' => $date, 'time_in' => '07:31:00', 'status' => 'present']);
    $this->travelTo(\Carbon\Carbon::parse('2026-12-01', 'Asia/Manila'));
    $response = $this->actingAs($user)->get(route('my-dtr.index', ['month' => 11, 'year' => 2026]))->assertOk()->assertSee($reference);
    expect(substr_count($response->getContent(), $reference))->toBeGreaterThanOrEqual($hasScan ? 3 : 4);
    if ($hasScan) $response->assertSee('07:31 AM');
    $calculator = new AttendanceCalculator;
    $record = DtrRecord::where('employee_id', $employee->id)->first();
    expect($calculator->isIncomplete($record, $employee, $date, array_fill_keys(['time_in', 'am_out', 'pm_in', 'time_out'], $reference)))->toBeFalse();
})->with([
    'Leave without scans' => ['leave', false], 'Leave with AM IN' => ['leave', true],
    'TA without scans' => ['ta', false], 'TA with AM IN' => ['ta', true],
    'Use CTO without scans' => ['cto', false], 'Use CTO with AM IN' => ['cto', true],
]);
