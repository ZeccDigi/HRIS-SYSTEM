<?php

use App\Models\DtrRecord;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\TravelOrder;
use App\Models\User;

function dtrScreenCells(string $html, int $day): array
{
    $dom = new DOMDocument;
    @$dom->loadHTML($html);
    $xpath = new DOMXPath($dom);
    $cells = $xpath->query('//table[contains(@class,"dtr-screen-table")]/tbody/tr[td[1][normalize-space()="'.$day.'"]]/td');
    return array_map(fn ($cell) => trim($cell->textContent), iterator_to_array($cells));
}

test('holidays hide attendance values while weekend punches remain visible without deleting scans', function () {
    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::create(['user_id' => $user->id, 'account_role' => 'employee', 'firstname' => 'Ana', 'lastname' => 'Test',
        'core_time_schedule' => ['wednesday' => ['am_start' => '08:00', 'am_end' => '12:00', 'pm_start' => '13:00', 'pm_end' => '17:00']]]);
    Holiday::create(['name' => 'Test holiday', 'date' => '2026-11-18']);
    foreach (['2026-11-18' => '06:01:00', '2026-11-19' => '07:02:00', '2026-11-21' => '07:03:00'] as $date => $time) {
        DtrRecord::create(['employee_id' => $employee->id, 'date' => $date, 'time_in' => $time, 'status' => 'present']);
    }
    TravelOrder::create(['employee_id' => $employee->id, 'ta_number' => 'TA-HOLIDAY', 'travel_type' => 'local', 'travel_date_start' => '2026-11-18', 'travel_date_end' => '2026-11-19', 'places_of_travel' => 'Digos', 'purpose' => 'Meeting', 'status' => 'approved']);
    $this->travelTo(\Carbon\Carbon::parse('2026-12-01', 'Asia/Manila'));
    $response = $this->actingAs($user)->get(route('my-dtr.index', ['month' => 11, 'year' => 2026]))->assertOk()->assertDontSee('TA-HOLIDAY');
    $holidayCells = array_slice(dtrScreenCells($response->getContent(), 18), 2);
    $expectedHolidayCells = array_fill(0, 12, '');
    $expectedHolidayCells[7] = '8.00';
    expect($holidayCells)->toBe($expectedHolidayCells);
    expect(array_slice(dtrScreenCells($response->getContent(), 19), 2))->toBe(array_fill(0, 12, ''));
    $expectedWeekend = array_fill(0, 11, '');
    $expectedWeekend[0] = '07:03 AM';
    expect(array_slice(dtrScreenCells($response->getContent(), 21), 2, 11))->toBe($expectedWeekend)
        ->and(dtrScreenCells($response->getContent(), 21)[13])->toBe('Weekend');
    expect($response->viewData('dtrRecords')->pluck('date')->all())->toBe(['2026-11-21']);
    $this->get(route('my-dtr.print', ['month' => 11, 'year' => 2026]))->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->assertDatabaseCount('dtr_records', 3);
});

test('overlapping approved applications hide references keep actual punches and keep OHW', function () {
    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::create(['user_id' => $user->id, 'account_role' => 'employee', 'firstname' => 'Ana', 'lastname' => 'Test']);
    $type = LeaveType::create(['name' => 'Vacation Leave', 'is_active' => true]);
    $leave = LeaveRequest::create(['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'start_date' => '2026-11-20', 'end_date' => '2026-11-20', 'date_filed' => '2026-11-18', 'reason' => 'Vacation', 'status' => 'approved']);
    TravelOrder::create(['employee_id' => $employee->id, 'ta_number' => 'TA-OVERLAP', 'travel_type' => 'local', 'travel_date_start' => '2026-11-20', 'travel_date_end' => '2026-11-20', 'places_of_travel' => 'Digos', 'purpose' => 'Meeting', 'status' => 'approved']);
    DtrRecord::create(['employee_id' => $employee->id, 'date' => '2026-11-20', 'time_in' => '08:31:00', 'status' => 'present']);
    $this->travelTo(\Carbon\Carbon::parse('2026-12-01', 'Asia/Manila'));
    $response = $this->actingAs($user)->get(route('my-dtr.index', ['month' => 11, 'year' => 2026]))->assertOk()
        ->assertDontSee('TA-OVERLAP')->assertDontSee(sprintf('LR-2026-%04d', $leave->id));
    $cells = dtrScreenCells($response->getContent(), 20);
    expect($cells[2])->toBe('08:31 AM')->and(array_slice($cells, 3, 3))->toBe(['', '', ''])->and($cells[9])->toBe('8.00');
});

