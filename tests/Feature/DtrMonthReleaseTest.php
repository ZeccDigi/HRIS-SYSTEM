<?php

use App\Models\DtrRecord;
use App\Models\Employee;
use App\Models\User;

test('employee DTR opens automatically at midnight Philippine time after month-end', function () {
    $hr = User::factory()->create(['must_change_password' => false]);
    Employee::factory()->for($hr)->create(['account_role' => 'hrstaff']);

    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::factory()->for($user)->create(['account_role' => 'employee']);

    foreach (['2026-10-05' => '08:23:00', '2026-11-05' => '09:17:00'] as $date => $time) {
        DtrRecord::create([
            'employee_id' => $employee->id,
            'date' => $date,
            'time_in' => $time,
            'status' => 'present',
        ]);
    }

    $period = ['month' => 11, 'year' => 2026];
    $this->travelTo(\Carbon\Carbon::parse('2026-11-30 23:59:59', 'Asia/Manila'));

    $reviewUrl = route('dtr.employee', ['employee' => $employee, ...$period]);
    $this->actingAs($hr)->get(route('dtr.index', ['date' => '2026-11-05']))
        ->assertOk()->assertSee($reviewUrl)->assertSee('09:17 AM');

    $this->get($reviewUrl)
        ->assertOk()->assertSee('09:17 AM');

    $this->actingAs($user)->get(route('my-dtr.index', $period))
        ->assertOk()->assertDontSee('Attendance fields are blank')
        ->assertDontSee('09:17 AM')
        ->assertDontSee(route('dtr.employee.print', [...$period, 'employee' => $employee]));

    $this->get($reviewUrl)->assertForbidden();

    $this->get(route('dtr.index', $period))->assertOk()->assertDontSee('09:17 AM');
    $this->get(route('my-dtr.print', $period))->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->get(route('my-dtr.index', ['month' => 10, 'year' => 2026]))
        ->assertOk()->assertSee('08:23 AM');

    $this->travelTo(\Carbon\Carbon::parse('2026-12-01 00:00:00', 'Asia/Manila'));
    $this->get(route('my-dtr.index', $period))->assertOk()->assertSee('09:17 AM');
    $this->assertDatabaseCount('dtr_month_releases', 0);
});
