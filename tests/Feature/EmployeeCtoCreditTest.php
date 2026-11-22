<?php

use App\Models\Employee;
use App\Models\User;

test('HR can add CTO hours with an audit entry', function () {
    $hr = User::factory()->create(['must_change_password' => false]);
    Employee::factory()->for($hr)->create(['account_role' => 'hrstaff']);
    $employee = Employee::factory()->create(['account_role' => 'employee', 'cto_balance' => 2.25]);
    $url = route('employees.cto-credits.store', $employee);

    $this->actingAs($employee->user)->post($url, ['hours' => '1.50', 'reason' => 'Opening balance'])->assertForbidden();
    $this->actingAs($hr)->post($url, ['hours' => '-1', 'reason' => 'Invalid'])
        ->assertSessionHasErrorsIn('ctoCredit', 'hours');

    $this->post($url, ['hours' => '1.50', 'reason' => 'Opening balance'])
        ->assertRedirect(route('employees.show', ['employee' => $employee, 'tab' => 'cto-credits']));
    expect((float) $employee->fresh()->cto_balance)->toBe(3.75);
    $this->assertDatabaseHas('activity_audits', ['user_id' => $hr->id, 'action' => 'Add CTO Credits']);
});
