<?php

use App\Models\Employee;
use App\Models\User;
use App\Services\PdsSnapshotService;
use App\Services\SalnPdsDefaultsService;
use Illuminate\Support\Facades\Notification;

it('imports PDS children with age at the SALN date and preserves additional children', function () {
    $employee = Employee::factory()->create();
    $child = $employee->pdsChildren()->create(['fullname' => 'PDS Child', 'date_of_birth' => '2016-05-02']);
    $employee->pdsChildren()->create(['fullname' => 'Birthday Boundary', 'date_of_birth' => '2008-12-31']);
    app(PdsSnapshotService::class)->ensureApprovedBaseline($employee);
    $employee->pdsChildren()->create(['fullname' => 'Unapproved Child', 'date_of_birth' => '2020-01-01']);
    $service = app(SalnPdsDefaultsService::class);
    $rows = $service->children($employee, 2026, '2026-12-31', [['name' => 'PDS Child', 'age' => 99], ['name' => 'Additional Child', 'age' => 7]]);
    expect($rows)->toHaveCount(2)->and($rows[0]['pds_child_id'])->toBe($child->id)->and($rows[0]['age'])->toBe(10)
        ->and($rows[1]['name'])->toBe('Additional Child');
    $beforeBirthday = $service->children($employee, 2026, '2026-12-30');
    expect(collect($beforeBirthday)->firstWhere('name', 'Birthday Boundary')['age'])->toBe(17);
});

it('enforces PDS identity and children on save while allowing a manually added child', function () {
    Notification::fake();
    $this->travelTo(\Carbon\Carbon::parse('2026-11-22'));
    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::factory()->for($user)->create(['account_role' => 'employee']);
    $employee->pdsPersonal()->create(['surname' => 'PDS Family', 'firstname' => 'PDS First']);
    $employee->pdsFamily()->create(['spouse_surname' => 'PDS Spouse', 'spouse_firstname' => 'Juan']);
    $child = $employee->pdsChildren()->create(['fullname' => 'PDS Child', 'date_of_birth' => '2016-05-02']);
    app(PdsSnapshotService::class)->ensureApprovedBaseline($employee);
    $this->actingAs($user)->get(route('salns.create'))->assertOk()->assertSee('element.readOnly = true', false)->assertSee('PDS Child');
    $this->post(route('salns.store'), ['type_of_filing' => 'annual_filing', 'as_of_date' => '2026-12-31', 'filing_status' => 'joint',
        'declarant_info' => ['family_name' => 'Tampered', 'first_name' => 'Tampered', 'office_address' => 'Manual office address'],
        'spouse_info' => ['family_name' => 'Tampered'], 'children' => [['pds_child_id' => $child->id, 'name' => 'Tampered Child', 'age' => 1], ['name' => 'Additional Child', 'age' => 7]],
        'has_business_interests' => 0, 'has_relatives_in_gov' => 0])->assertSessionHasNoErrors();
    $saln = $employee->salns()->firstOrFail();
    expect($saln->declarant_info['family_name'])->toBe('PDS Family')->and($saln->spouse_info['family_name'])->toBe('PDS Spouse')
        ->and($saln->declarant_info['office_address'])->toBe('Manual office address')
        ->and($saln->children)->toHaveCount(2)->and($saln->children[0]['name'])->toBe('PDS Child')->and($saln->children[0]['age'])->toBe(10)
        ->and($saln->children[1]['name'])->toBe('Additional Child');
});
