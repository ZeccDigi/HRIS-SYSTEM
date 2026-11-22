<?php

use App\Models\Employee;
use App\Models\User;

test('manage employees matches the service start year exactly', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-11-21'));
    $hr = User::factory()->create(['must_change_password' => false]);
    Employee::factory()->for($hr)->create(['account_role' => 'hrstaff']);

    $joinedIn2025 = Employee::factory()->create([
        'firstname' => 'Previous', 'lastname' => 'Employee', 'employment_status' => 'Regular',
        'service_start_date' => '2025-06-01',
    ]);
    $joinedIn2026 = Employee::factory()->create([
        'firstname' => 'Current', 'lastname' => 'Employee', 'employment_status' => 'Job Order',
        'service_start_date' => '2026-01-01',
    ]);

    $this->actingAs($hr)->get(route('employees.index', ['year' => 2025]))
        ->assertOk()->assertSee('Employee, Previous')->assertDontSee('Employee, Current')
        ->assertSee('Year');

    $this->actingAs($hr)->get(route('employees.index', ['year' => 2026]))
        ->assertOk()->assertSee('Employee, Current')->assertDontSee('Employee, Previous');
    $this->actingAs($hr)->get(route('employees.index'))
        ->assertOk()->assertViewHas('year', 0)->assertSee('Years')
        ->assertSee('Employee, Previous')->assertSee('Employee, Current');
    $this->actingAs($hr)->getJson(route('employees.filter'))
        ->assertOk()->assertJsonPath('count', 3);

    $this->actingAs($hr)->get(route('employees.index', ['year' => 2026, 'employment_status' => 'Job Order']))
        ->assertOk()->assertSee('Employee, Current')->assertDontSee('Employee, Previous');

    $this->actingAs($hr)->getJson(route('employees.filter', ['year' => 2025, 'employment_status' => 'Regular']))
        ->assertOk()->assertJsonPath('count', 1)->assertJsonPath('html', fn (string $html) => str_contains($html, (string) $joinedIn2025->lastname));
});
