<?php

use App\Models\Employee;
use App\Models\LocatorSlip;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\URL;

function approvedLocatorSlip(string $type): LocatorSlip
{
    $user = User::factory()->create();
    $employee = Employee::create([
        'user_id' => $user->id,
        'firstname' => 'Test',
        'lastname' => 'Employee',
        'division' => 'Finance and Administrative Division',
        'position' => 'Staff',
        'account_role' => 'employee',
    ]);

    return LocatorSlip::create([
        'employee_id' => $employee->id,
        'date_covered' => now()->toDateString(),
        'destination' => 'DMW Office',
        'purpose' => 'Official transaction',
        'type' => $type,
        'status' => 'approved',
    ]);
}

it('records the return scan within eight hours', function (string $type) {
    Carbon::setTestNow('2026-11-03 08:00:00');
    $slip = approvedLocatorSlip($type);
    $url = URL::temporarySignedRoute('locator-slips.scan', now()->addDay(), ['locatorSlip' => $slip]);

    $this->get($url)->assertOk();

    Carbon::setTestNow('2026-11-03 16:00:00');
    $this->get($url)->assertOk();

    $slip->refresh();
    expect($slip->time_from)->not->toBeNull()
        ->and($slip->time_to)->not->toBeNull();

    Carbon::setTestNow();
})->with(['Pass Slip', 'Official Business']);

it('rejects the return scan after the eight-hour limit', function (string $type) {
    Carbon::setTestNow('2026-11-03 08:00:00');
    $slip = approvedLocatorSlip($type);
    $url = URL::temporarySignedRoute('locator-slips.scan', now()->addDay(), ['locatorSlip' => $slip]);

    $this->get($url)->assertOk();

    Carbon::setTestNow('2026-11-03 16:00:01');
    $this->get($url)
        ->assertStatus(422)
        ->assertSee('8-hour return window expired');

    expect($slip->fresh()->time_to)->toBeNull();

    Carbon::setTestNow();
})->with(['Pass Slip', 'Official Business']);

it('defaults to five locator slips and allows the employee to show twenty', function () {
    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::create([
        'user_id' => $user->id,
        'firstname' => 'Anna',
        'lastname' => 'Garcia',
        'division' => 'Finance and Administrative Division',
        'position' => 'Staff',
        'account_role' => 'employee',
    ]);

    foreach (range(1, 21) as $index) {
        LocatorSlip::create([
            'employee_id' => $employee->id,
            'date_covered' => now()->toDateString(),
            'destination' => "Destination {$index}",
            'purpose' => 'Official transaction',
            'type' => 'Pass Slip',
            'status' => 'pending',
        ]);
    }

    $this->actingAs($user)
        ->get(route('locator-slips.index'))
        ->assertOk()
        ->assertViewHas('locatorSlips', fn ($paginator) => $paginator->perPage() === 5 && $paginator->count() === 5);

    $this->actingAs($user)
        ->get(route('locator-slips.index', ['per_page' => 20]))
        ->assertOk()
        ->assertViewHas('locatorSlips', fn ($paginator) => $paginator->perPage() === 20 && $paginator->count() === 20);
});
