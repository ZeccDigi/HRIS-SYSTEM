<?php

use App\Models\DtrRecord;
use App\Models\Employee;
use App\Models\LocatorSlip;
use App\Models\User;
use App\Services\AttendanceCalculator;

test('locator coverage counts in actual hours without duplicating punched time', function (string $type, ?string $return, int $actual) {
    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::create(['firstname' => 'Ana', 'lastname' => 'Test', 'user_id' => $user->id, 'account_role' => 'employee']);
    $record = DtrRecord::create(['employee_id' => $employee->id, 'date' => '2026-11-18', 'time_in' => '08:00:00', 'am_out' => '12:00:00', 'pm_in' => '13:00:00', 'time_out' => '17:00:00', 'status' => 'present']);
    LocatorSlip::create(['employee_id' => $employee->id, 'date_covered' => '2026-11-18', 'destination' => 'Digos', 'purpose' => 'Errand', 'type' => $type, 'status' => 'approved', 'time_from' => '14:00:00', 'time_to' => $return, 'out_scanned_at' => '2026-11-18 14:00:00']);
    $calculator = new AttendanceCalculator;
    expect($calculator->actualMinutes($record, $employee))->toBe($actual)
        ->and($calculator->overtimeMinutes($record, $employee))->toBe(0);
    $this->travelTo(\Carbon\Carbon::parse('2026-12-01', 'Asia/Manila'));
    $response = $this->actingAs($user)->get(route('my-dtr.index', ['month' => 11, 'year' => 2026]))->assertOk()->assertSee($type);
    $dom = new DOMDocument;
    @$dom->loadHTML($response->getContent());
    $xpath = new DOMXPath($dom);
    $cells = iterator_to_array($xpath->query('//table[contains(@class,"dtr-screen-table")]/tbody/tr[td[1][normalize-space()="18"]]/td'));
    expect(trim($cells[8]->textContent))->toBe(number_format($actual / 60, 2))
        ->and(trim($cells[9]->textContent))->toBe('8.00');
})->with([
    'Pass Slip with return' => ['Pass Slip', '15:00:00', 480],
    'Official Business with return' => ['Official Business', '15:00:00', 480],
    'Pass Slip without return' => ['Pass Slip', null, 480],
    'Official Business without return' => ['Official Business', null, 480],
]);

test('locator coverage does not erase unrelated morning late or early dismissal', function () {
    $employee = Employee::create(['firstname' => 'Ana', 'lastname' => 'Test']);
    $record = DtrRecord::create(['employee_id' => $employee->id, 'date' => '2026-11-18', 'time_in' => '08:30:00', 'am_out' => '12:00:00', 'pm_in' => '13:00:00', 'time_out' => '16:00:00']);
    LocatorSlip::create(['employee_id' => $employee->id, 'date_covered' => '2026-11-18', 'destination' => 'Digos', 'purpose' => 'Errand', 'type' => 'Official Business', 'status' => 'approved', 'time_from' => '14:00:00', 'time_to' => '15:00:00', 'out_scanned_at' => '2026-11-18 14:00:00']);
    $calculator = new AttendanceCalculator;
    expect($calculator->lateMinutes($record, $employee))->toBe(15)
        ->and($calculator->undertimeMinutes($record, $employee))->toBe(60);
});

test('unscanned approved locator does not affect actual work hours', function () {
    $employee = Employee::create(['firstname' => 'Ana', 'lastname' => 'Test']);
    $record = new DtrRecord(['date' => '2026-11-18', 'time_in' => '08:00:00', 'am_out' => '12:00:00', 'pm_in' => '13:00:00', 'time_out' => '17:00:00']);
    LocatorSlip::create(['employee_id' => $employee->id, 'date_covered' => '2026-11-18', 'destination' => 'Digos', 'purpose' => 'Errand', 'type' => 'Pass Slip', 'status' => 'approved']);
    expect((new AttendanceCalculator)->actualMinutes($record, $employee))->toBe(480);
});

test('afternoon locator counts four hours and keeps the usage table out of DTR', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-12-01', 'Asia/Manila'));
    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::create(['firstname' => 'Afternoon', 'lastname' => 'Test', 'user_id' => $user->id, 'account_role' => 'employee',
        'core_time_schedule' => ['wednesday' => ['pm_start' => '13:00', 'pm_end' => '17:00']]]);
    $record = DtrRecord::create(['employee_id' => $employee->id, 'date' => '2026-11-18', 'pm_in' => '13:00:00', 'time_out' => '17:00:00', 'status' => 'present']);
    LocatorSlip::create(['employee_id' => $employee->id, 'date_covered' => '2026-11-18', 'destination' => 'Davao', 'purpose' => 'Errand', 'type' => 'Pass Slip', 'status' => 'approved',
        'time_from' => '13:30:00', 'time_to' => '15:30:00', 'out_scanned_at' => '2026-11-18 13:30:00']);
    $calculator = new AttendanceCalculator;
    expect($calculator->actualMinutes($record, $employee))->toBe(240)->and($calculator->lateMinutes($record, $employee))->toBe(0)->and($calculator->undertimeMinutes($record, $employee))->toBe(0);
    $response = $this->actingAs($user)->get(route('my-dtr.index', ['month' => 11, 'year' => 2026]))->assertOk()
        ->assertDontSee('Locator slip usage');
    $dom = new DOMDocument;
    @$dom->loadHTML($response->getContent());
    $xpath = new DOMXPath($dom);
    $cells = iterator_to_array($xpath->query('//table[contains(@class,"dtr-screen-table")]/tbody/tr[td[1][normalize-space()="18"]]/td'));
    expect(trim($cells[4]->textContent))->toBe('01:00 PM')->and(trim($cells[5]->textContent))->toBe('05:00 PM')
        ->and(trim($cells[8]->textContent))->toBe('4.00')->and(trim($cells[9]->textContent))->toBe('4.00');
    $employee->update(['core_time_schedule' => ['wednesday' => ['am_start' => '08:00', 'am_end' => '12:00', 'pm_start' => '13:00', 'pm_end' => '17:00']]]);
    $record->update(['time_in' => '08:00:00', 'am_out' => '12:00:00']);
    expect($calculator->actualMinutes($record, $employee))->toBe(480)->and($calculator->scheduledMinutes($employee, $record->date))->toBe(480);
});
