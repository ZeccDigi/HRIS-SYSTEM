<?php

use App\Models\DtrRecord;
use App\Models\Employee;
use App\Services\AttendanceCalculator;

function scheduledAttendanceEmployee(array $schedule): Employee
{
    return Employee::create([
        'firstname' => 'Schedule', 'lastname' => 'Test',
        'core_time_start_date' => '2026-11-01', 'core_time_end_date' => '2026-12-31',
        'core_time_schedule' => ['wednesday' => $schedule],
    ]);
}

test('unscheduled weekend punches remain visible and count actual time without official hours', function () {
    $employee = scheduledAttendanceEmployee(['am_start' => '08:00', 'am_end' => '12:00']);
    $record = new DtrRecord(['date' => '2026-11-21', 'time_in' => '08:00:00', 'am_out' => '12:00:00', 'pm_in' => '13:00:00', 'time_out' => '17:00:00']);
    $calculator = new AttendanceCalculator;
    expect($calculator->isBlankDay($employee, $record->date))->toBeFalse()
        ->and($calculator->actualMinutes($record, $employee))->toBe(480)
        ->and($calculator->assignedMinutes($employee, $record->date))->toBe(0)
        ->and($calculator->overtimeMinutes($record, $employee))->toBe(0);
});

test('AHW counts early arrival and short lunch while official hours and overtime stay schedule based', function () {
    $employee = scheduledAttendanceEmployee(['am_start' => '08:00', 'am_end' => '12:00', 'pm_start' => '13:00', 'pm_end' => '17:00']);
    $record = new DtrRecord(['date' => '2026-11-18', 'time_in' => '06:00:00', 'am_out' => '12:00:00', 'pm_in' => '12:00:05', 'time_out' => '17:00:00']);
    $calculator = new AttendanceCalculator;
    expect($calculator->regularMinutes($record, $employee))->toBe(480)
        ->and(number_format($calculator->actualMinutes($record, $employee) / 60, 2))->toBe('11.00')
        ->and($calculator->overtimeMinutes($record, $employee))->toBe(0);
});

test('late arrival uses each assigned session start and fifteen minute grace', function () {
    $employee = scheduledAttendanceEmployee(['am_start' => '09:00', 'am_end' => '12:00', 'pm_start' => '14:00', 'pm_end' => '18:00']);
    $record = new DtrRecord(['date' => '2026-11-18', 'time_in' => '09:15:00', 'pm_in' => '14:15:00']);
    $calculator = new AttendanceCalculator;
    expect($calculator->lateMinutes($record, $employee))->toBe(0);
    $record->time_in = '09:16:00';
    $record->pm_in = '14:20:00';
    expect($calculator->lateMinutes($record, $employee))->toBe(6);
});

test('afternoon only employee has no missing morning flag or morning deduction', function () {
    $employee = scheduledAttendanceEmployee(['am_start' => null, 'am_end' => null, 'pm_start' => '13:00', 'pm_end' => '17:00']);
    $record = new DtrRecord(['date' => '2026-11-18', 'pm_in' => '12:00:00', 'time_out' => '17:00:00']);
    $calculator = new AttendanceCalculator;
    expect($calculator->flags($record, $employee))->toBeEmpty()
        ->and($calculator->regularMinutes($record, $employee))->toBe(240)
        ->and($calculator->lateMinutes($record, $employee))->toBe(0);
});

test('full day employee arriving in afternoon has blank morning and a missing morning flag', function () {
    $employee = scheduledAttendanceEmployee(['am_start' => '08:00', 'am_end' => '12:00', 'pm_start' => '13:00', 'pm_end' => '17:00']);
    $record = new DtrRecord(['date' => '2026-11-18', 'pm_in' => '13:10:00', 'time_out' => '17:00:00']);
    $calculator = new AttendanceCalculator;
    expect($record->time_in)->toBeNull()
        ->and($calculator->flags($record, $employee))->toContain('Missing AM attendance')
        ->and($calculator->regularMinutes($record, $employee))->toBe(230);
});

test('missing lunch punches use scheduled sessions instead of crediting the lunch gap', function () {
    $employee = scheduledAttendanceEmployee(['am_start' => '08:00', 'am_end' => '12:00', 'pm_start' => '13:00', 'pm_end' => '17:00']);
    $record = new DtrRecord(['date' => '2026-11-18', 'time_in' => '06:00:00', 'time_out' => '17:00:00']);
    expect((new AttendanceCalculator)->actualMinutes($record, $employee))->toBe(600);
});

test('early AM and PM outs add undertime against their assigned end times and display two decimals', function () {
    $user = \App\Models\User::factory()->create(['must_change_password' => false]);
    $employee = scheduledAttendanceEmployee(['am_start' => '09:00', 'am_end' => '12:00', 'pm_start' => '14:00', 'pm_end' => '18:00']);
    $employee->update(['user_id' => $user->id, 'account_role' => 'employee']);
    $record = DtrRecord::create([
        'employee_id' => $employee->id, 'date' => '2026-11-18',
        'time_in' => '09:20:00', 'am_out' => '11:45:00',
        'pm_in' => '14:25:00', 'time_out' => '17:30:00', 'status' => 'present',
    ]);
    $calculator = new AttendanceCalculator;
    expect($calculator->undertimeMinutes($record, $employee))->toBe(45)
        ->and($calculator->lateMinutes($record, $employee))->toBe(15);
    $this->travelTo(\Carbon\Carbon::parse('2026-12-01', 'Asia/Manila'));
    $this->actingAs($user)->get(route('my-dtr.index', ['month' => 11, 'year' => 2026]))
        ->assertOk()->assertSee('15.00 min')->assertSee('45.00 min')
        ->assertDontSee('15.000 min')->assertDontSee('45.000 min')
        ->assertSee('dtr-duration-cell');
});
