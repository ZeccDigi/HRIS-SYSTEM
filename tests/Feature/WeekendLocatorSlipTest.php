<?php

use App\Models\Employee;
use App\Models\LocatorSlip;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

function weekendLocatorActor(string $role = 'employee'): User
{
    $user = User::factory()->create(['must_change_password' => false]);
    Employee::create(['user_id' => $user->id, 'firstname' => 'Weekend', 'lastname' => $role, 'account_role' => $role, 'division' => 'Test Division']);
    return $user->fresh();
}

test('temporary weekend setting permits Saturday and Sunday filing but keeps past dates blocked', function () {
    Notification::fake();
    $this->travelTo(\Carbon\Carbon::parse('2026-11-22 13:00:00', 'Asia/Manila'));
    config(['locator_slips.allow_weekends' => true]);
    $user = weekendLocatorActor();
    $payload = ['date_covered' => '2026-11-22', 'destination' => 'Test office', 'purpose' => 'Weekend test', 'type' => 'Pass Slip'];
    $this->actingAs($user)->post(route('locator-slips.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
    $this->post(route('locator-slips.store'), [...$payload, 'date_covered' => '2026-11-28'])->assertRedirect()->assertSessionHasNoErrors();
    $this->post(route('locator-slips.store'), [...$payload, 'date_covered' => '2026-11-21'])->assertSessionHasErrors('date_covered');
    expect(LocatorSlip::count())->toBe(2);
    config(['locator_slips.allow_weekends' => false]);
    $this->post(route('locator-slips.store'), $payload)->assertSessionHasErrors('date_covered');
    expect(LocatorSlip::count())->toBe(2);
});

test('Chief locator tables fill the container with narrower actions while HR keeps archive space', function () {
    $chief = weekendLocatorActor('chief');
    $this->actingAs($chief)->get(route('hr.locator-slips.index'))->assertOk()
        ->assertSee('class="w-full table-fixed', false)->assertSee('width: 11%', false);
    $hr = weekendLocatorActor('hrstaff');
    $this->actingAs($hr)->get(route('hr.locator-slips.index'))->assertOk()->assertSee('width: 16%', false);
});
