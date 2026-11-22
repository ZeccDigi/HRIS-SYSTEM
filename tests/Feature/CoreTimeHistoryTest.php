<?php

use App\Models\DtrRecord;
use App\Models\Employee;
use App\Services\AttendanceCalculator;
use App\Services\CoreTimeHistoryService;

function historyWeekdaySchedule(string $start): array
{
    return array_fill_keys(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], ['am_start' => $start, 'am_end' => '12:00', 'pm_start' => '13:00', 'pm_end' => '17:00']);
}

test('identical historical core times display as one monthly schedule', function () {
    $user = \App\Models\User::factory()->create(['must_change_password' => false]);
    $employee = Employee::create(['firstname' => 'Ana', 'lastname' => 'Test', 'user_id' => $user->id,
        'account_role' => 'employee', 'core_time_schedule' => historyWeekdaySchedule('08:00')]);
    foreach ([['2026-11-01', '2026-11-11'], ['2026-11-12', '2026-12-31']] as [$start, $end]) {
        $employee->coreTimeSchedules()->create(['applies_from' => $start,
            'core_time_start_date' => $start, 'core_time_end_date' => $end,
            'core_time_schedule' => historyWeekdaySchedule('08:00')]);
    }
    $this->travelTo(\Carbon\Carbon::parse('2026-12-01', 'Asia/Manila'));
    $this->actingAs($user)->get(route('my-dtr.index', ['month' => 11, 'year' => 2026]))
        ->assertOk()->assertSee('Nov. 12, 2026 to Dec. 31, 2026')
        ->assertDontSee('Nov. 01, 2026 to Nov. 30, 2026')
        ->assertDontSee('Nov. 01, 2026 to Nov. 11, 2026');
});

test('changing core time preserves old dates even when new dates are backdated', function () {
    $employee = Employee::create(['firstname' => 'Ana', 'lastname' => 'Test',
        'core_time_start_date' => '2026-11-01', 'core_time_end_date' => '2026-12-31', 'core_time_schedule' => historyWeekdaySchedule('08:00')]);
    $oldRecord = new DtrRecord(['date' => '2026-11-16', 'time_in' => '08:30:00', 'am_out' => '12:00:00', 'pm_in' => '13:00:00', 'time_out' => '17:00:00']);
    $calculator = new AttendanceCalculator;
    $minutesBefore = $calculator->actualMinutes($oldRecord, $employee);
    $this->travelTo(\Carbon\Carbon::parse('2026-11-18', 'Asia/Manila'));
    app(CoreTimeHistoryService::class)->assign($employee, [
        'core_time_start_date' => '2026-11-01', 'core_time_end_date' => '2026-12-31', 'core_time_schedule' => historyWeekdaySchedule('09:00'),
    ]);
    expect($calculator->actualMinutes($oldRecord, $employee))->toBe($minutesBefore)
        ->and($calculator->lateMinutes($oldRecord, $employee))->toBe(15)
        ->and($calculator->schedule($employee, '2026-11-18')['am_start'])->toBe('09:00');
    $this->assertDatabaseCount('employee_core_time_schedules', 2);
});

test('future schedule approvals do not change current schedules and can be superseded', function () {
    $employee = Employee::create(['firstname' => 'Ana', 'lastname' => 'Test', 'core_time_schedule' => historyWeekdaySchedule('08:00')]);
    $this->travelTo(\Carbon\Carbon::parse('2026-11-18', 'Asia/Manila'));
    $service = new CoreTimeHistoryService;
    $calculator = new AttendanceCalculator;
    $service->assign($employee, ['core_time_start_date' => '2026-12-01', 'core_time_end_date' => '2027-12-31', 'core_time_schedule' => historyWeekdaySchedule('09:00')]);
    expect($calculator->schedule($employee, '2026-11-19')['am_start'])->toBe('08:00')
        ->and($calculator->schedule($employee, '2026-12-01')['am_start'])->toBe('09:00');
    $service->assign($employee, ['core_time_start_date' => '2026-11-20', 'core_time_end_date' => '2027-12-31', 'core_time_schedule' => historyWeekdaySchedule('10:00')]);
    expect($calculator->schedule($employee, '2026-11-19')['am_start'])->toBe('08:00')
        ->and($calculator->schedule($employee, '2026-12-01')['am_start'])->toBe('10:00');
});
