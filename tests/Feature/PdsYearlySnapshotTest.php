<?php

use App\Models\Employee;
use App\Models\PdsPersonalInformation;
use App\Models\PdsSnapshot;
use App\Models\User;
use App\Services\PdsSnapshotService;
use Carbon\Carbon;

test('employee can select and view a historical yearly PDS snapshot', function () {
    Carbon::setTestNow('2026-11-03 09:00:00');

    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::create([
        'firstname' => 'Anna',
        'lastname' => 'Garcia',
        'division' => 'Migrant Workers Processing Division',
        'position' => 'Regional Aide',
        'account_role' => 'employee',
        'employment_status' => 'Regular',
        'user_id' => $user->id,
    ]);

    PdsPersonalInformation::create([
        'employee_id' => $employee->id,
        'surname' => 'Garcia Historical',
        'firstname' => 'Anna',
        'email_address' => 'old@example.com',
    ]);

    app(PdsSnapshotService::class)->capture($employee, 2025);

    $employee->pdsPersonal()->update([
        'surname' => 'Garcia Current',
        'email_address' => 'current@example.com',
    ]);

    $this->actingAs($user)
        ->get(route('pds.print', ['year' => 2025]))
        ->assertOk()
        ->assertSee('Garcia Historical')
        ->assertSee('old@example.com')
        ->assertDontSee('Garcia Current')
        ->assertSee('<option value="2025" selected', false);

    $this->actingAs($user)
        ->get(route('pds.print', ['year' => 2026]))
        ->assertOk()
        ->assertSee('Garcia Current')
        ->assertSee('current@example.com');
});

test('updating the PDS stores the current year snapshot', function () {
    Carbon::setTestNow('2026-11-03 09:00:00');

    $user = User::factory()->create(['must_change_password' => false]);
    Employee::create([
        'firstname' => 'Min',
        'lastname' => 'Yoon',
        'division' => 'Finance and Administrative Division',
        'position' => 'Administrative Officer',
        'account_role' => 'employee',
        'employment_status' => 'Regular',
        'user_id' => $user->id,
    ]);

    $this->actingAs($user)->put(route('pds.update'), [
        'personal' => [
            'surname' => 'Yoon',
            'firstname' => 'Min',
            'email_address' => 'min@example.com',
        ],
    ])->assertRedirect(route('pds.edit'));

    $snapshot = PdsSnapshot::firstOrFail();
    expect($snapshot->year)->toBe(2026)
        ->and(data_get($snapshot->data, 'relations.pdsPersonal.email_address'))->toBe('min@example.com');
});
