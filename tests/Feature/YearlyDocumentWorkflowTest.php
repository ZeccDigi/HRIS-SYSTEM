<?php

use App\Models\Employee;
use App\Models\User;
use App\Models\Saln;
use App\Services\PdsSnapshotService;
use Illuminate\Support\Facades\Notification;

function yearlyDocumentActors(): array
{
    Notification::fake();
    $owner = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::factory()->for($owner)->create(['account_role' => 'employee', 'service_start_date' => '2023-05-01']);
    $hr = User::factory()->create(['must_change_password' => false]);
    Employee::factory()->for($hr)->create(['account_role' => 'hrstaff']);
    return [$owner, $employee, $hr];
}

test('missing service years open blank PDS forms and historical approval cannot change the live PDS', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-11-18', 'Asia/Manila'));
    [$owner, $employee, $hr] = yearlyDocumentActors();
    $employee->pdsPersonal()->create(['surname' => 'CURRENT-PDS', 'firstname' => 'Ana']);
    $this->actingAs($owner)->get(route('pds.edit', ['year' => 2023]))->assertOk()
        ->assertViewHas('employee', fn ($draft) => $draft->pdsPersonal->surname === '')
        ->assertViewHas('pdsYears', fn ($years) => $years->all() === [2026, 2025, 2024, 2023]);
    $this->put(route('pds.update'), ['year' => 2023, 'personal' => ['surname' => 'HISTORICAL-DRAFT', 'firstname' => 'Ana'],
        'children' => [['fullname' => 'Child in 2023', 'date_of_birth' => '2020-01-01']]])
        ->assertSessionHasNoErrors()->assertRedirect(route('pds.edit', ['year' => 2023]));
    expect($employee->fresh()->pdsPersonal->surname)->toBe('CURRENT-PDS')
        ->and($employee->pdsChildren()->count())->toBe(0)
        ->and($employee->pdsSectionReviews()->count())->toBe(0);
    $this->get(route('pds.edit', ['year' => 2023]))->assertOk()->assertSee('HISTORICAL-DRAFT');
    $this->get(route('pds.print', ['year' => 2023]))->assertOk()->assertDontSee('HISTORICAL-DRAFT')->assertDontSee('CURRENT-PDS');
    $this->actingAs($hr)->get(route('employees.show', ['employee' => $employee, 'tab' => 'pds', 'pds_year' => 2023]))->assertOk()
        ->assertSee('Personal Information')->assertSee(route('employees.pds.show', ['employee' => $employee, 'year' => 2023]));
    $reviewUrl = route('pds-reviews.store', $employee);
    $this->post($reviewUrl, ['year' => 2023, 'section_name' => 'Personal Information', 'status' => 'approved'])->assertRedirect();
    $this->post($reviewUrl, ['year' => 2023, 'section_name' => 'Personal Information', 'status' => 'rejected', 'remarks' => 'Duplicate'])->assertStatus(409);
    $this->actingAs($owner)->get(route('pds.print', ['year' => 2023]))->assertOk()->assertSee('HISTORICAL-DRAFT')->assertDontSee('Child in 2023');
    $this->get(route('pds.edit', ['year' => 2024]))->assertOk()->assertViewHas('employee', fn ($draft) => $draft->pdsPersonal->surname === '');
    expect($employee->fresh()->pdsPersonal->surname)->toBe('CURRENT-PDS');
    $this->get(route('pds.edit', ['year' => 2022]))->assertNotFound();
    $this->put(route('pds.update'), ['year' => 2027])->assertStatus(422);
});

test('past year SALN edits require HR approval and cannot overwrite another year', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-11-18', 'Asia/Manila'));
    [$owner, $employee, $hr] = yearlyDocumentActors();
    $current = Saln::create(['employee_id' => $employee->id, 'type_of_filing' => 'annual_filing', 'as_of_date' => '2026-12-31',
        'declarant_info' => ['family_name' => 'CURRENT-SALN'], 'filing_status' => 'not_applicable', 'review_status' => 'approved']);
    $this->actingAs($owner)->get(route('salns.index', ['year' => 2023, 'edit' => 1]))->assertOk()
        ->assertSee('declarant_info[family_name]', false);
    $payload = ['type_of_filing' => 'annual_filing', 'as_of_date' => '2023-12-31', 'declarant_info' => [
        'family_name' => 'PAST-SALN', 'first_name' => 'Ana', 'position' => 'Officer', 'agency' => 'DMW', 'office_address' => 'PAST-SALN'],
        'filing_status' => 'not_applicable', 'has_business_interests' => 0, 'has_relatives_in_gov' => 0];
    $this->post(route('salns.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
    $past = $employee->salns()->whereYear('as_of_date', 2023)->firstOrFail();
    $this->get(route('salns.index', ['year' => 2023]))->assertOk()->assertSee('PAST-SALN');
    $this->get(route('salns.index', ['year' => 2023, 'edit' => 1]))->assertOk()->assertSee('PAST-SALN');
    $this->get(route('salns.download', $past))->assertForbidden();
    $this->actingAs($hr)->get(route('salns.show', $past))->assertOk()->assertSee('PAST-SALN');
    $this->patch(route('salns.review', $past), ['review_status' => 'approved'])->assertRedirect();
    $this->actingAs($owner)->get(route('salns.index', ['year' => 2023]))->assertOk()->assertSee('PAST-SALN');
    $payload['declarant_info']['office_address'] = 'PAST-SECOND-DRAFT';
    $payload['saln_id'] = $past->id;
    $this->post(route('salns.store'), $payload)->assertSessionHasNoErrors()->assertRedirect();
    $this->get(route('salns.index', ['year' => 2023]))->assertOk()->assertSee('PAST-SECOND-DRAFT');
    $payload['saln_id'] = $current->id;
    $this->post(route('salns.store'), $payload)->assertStatus(422);
    expect($current->fresh()->declarant_info['family_name'])->toBe('CURRENT-SALN')
        ->and($employee->salns()->count())->toBe(2);
    $this->get(route('salns.index', ['year' => 2022]))->assertNotFound();
});

test('a new calendar year starts blank without clearing the previous PDS records', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-12-31', 'Asia/Manila'));
    [$owner, $employee, $hr] = yearlyDocumentActors();
    $employee->forceFill(['pds_record_year' => 2026])->save();
    $employee->pdsPersonal()->create(['surname' => 'PDS-2026', 'firstname' => 'Ana']);
    app(PdsSnapshotService::class)->ensureApprovedBaseline($employee);
    app(PdsSnapshotService::class)->syncLiveDraft($employee);
    $this->travelTo(\Carbon\Carbon::parse('2027-01-01', 'Asia/Manila'));
    $this->actingAs($owner)->get(route('pds.edit', ['year' => 2027]))->assertOk()
        ->assertViewHas('employee', fn ($draft) => $draft->pdsPersonal->surname === '');
    $this->put(route('pds.update'), ['year' => 2027, 'personal' => ['surname' => 'PDS-2027', 'firstname' => 'Ana']])
        ->assertSessionHasNoErrors()->assertRedirect();
    expect($employee->fresh()->pdsPersonal->surname)->toBe('PDS-2026');
    $this->get(route('pds.edit', ['year' => 2027]))->assertOk()->assertSee('PDS-2027');
    $this->get(route('pds.print', ['year' => 2027]))->assertOk()->assertDontSee('PDS-2027')->assertDontSee('PDS-2026');
    $this->actingAs($hr)->post(route('pds-reviews.store', $employee), ['year' => 2027, 'section_name' => 'Personal Information', 'status' => 'approved'])->assertRedirect();
    $this->actingAs($owner)->get(route('pds.print', ['year' => 2027]))->assertOk()->assertSee('PDS-2027');
    $this->get(route('pds.print', ['year' => 2026]))->assertOk()->assertSee('PDS-2026')->assertDontSee('PDS-2027');
    expect($employee->fresh()->pdsPersonal->surname)->toBe('PDS-2026');
});
