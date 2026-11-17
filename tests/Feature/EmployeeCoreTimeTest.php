<?php

use App\Models\Employee;
use App\Models\User;

test('hr can assign a dated weekday core time schedule from the employee tab', function () {
    $hrUser = User::factory()->create([
        'must_change_password' => false,
        'is_approved' => true,
        'account_status' => 'active',
        'privacy_consent' => true,
    ]);
    Employee::create([
        'firstname' => 'Maria',
        'lastname' => 'Reyes',
        'account_role' => 'hrstaff',
        'user_id' => $hrUser->id,
    ]);

    $employee = Employee::create([
        'firstname' => 'Anna',
        'lastname' => 'Garcia',
        'account_role' => 'employee',
    ]);

    $response = $this->actingAs($hrUser)->patch(route('employees.core-time.update', $employee), [
        'core_time_start_date' => '2026-11-01',
        'core_time_end_date' => '2026-12-31',
        'core_time_schedule' => [
            'monday' => [
                'am_start' => '08:00',
                'am_end' => '12:00',
                'pm_start' => '13:00',
                'pm_end' => '17:00',
            ],
            'tuesday' => [
                'am_start' => '08:00',
                'am_end' => '12:00',
                'pm_start' => '13:00',
                'pm_end' => '17:00',
            ],
        ],
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('employees.show', ['employee' => $employee, 'tab' => 'core-time']));

    $employee->refresh();

    expect($employee->core_time_start_date->toDateString())->toBe('2026-11-01')
        ->and($employee->core_time_end_date->toDateString())->toBe('2026-12-31')
        ->and($employee->core_time_schedule['monday']['am_start'])->toBe('08:00')
        ->and($employee->core_time_schedule['tuesday']['pm_end'])->toBe('17:00');
});

