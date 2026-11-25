<?php

use App\Models\Employee;
use App\Models\User;

it('keeps work dashboards separate from personal employee features', function ($role) {
    $user = User::factory()->create(['must_change_password' => false, 'is_approved' => true]);
    Employee::factory()->for($user)->create(['account_role' => $role]);
    $response = $this->actingAs($user)->get(route('dashboard'))->assertOk();
    $response->assertDontSee('My Account')->assertDontSee('My Leave Calendar')
        ->assertDontSee('My Leave Application')->assertSee('Administration');
})->with(['chief', 'regionaldirector', 'recordofficer']);

it('retains personal features on the separate employee account', function () {
    $user = User::factory()->create(['must_change_password' => false, 'is_approved' => true]);
    Employee::factory()->for($user)->create(['account_role' => 'employee']);
    $this->actingAs($user)->get(route('dashboard'))->assertOk()
        ->assertSee('My Account')->assertSee('My Leave Application')->assertSee('My Leave Calendar');
});
