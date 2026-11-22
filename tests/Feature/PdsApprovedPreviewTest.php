<?php

use App\Models\Employee;
use App\Models\User;
use App\Services\PdsSnapshotService;
use Illuminate\Support\Facades\Notification;

test('saved PDS drafts remain on the locked form but do not publish until HR approves each section', function () {
    Notification::fake();
    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::factory()->for($user)->create(['account_role' => 'employee']);
    $employee->pdsPersonal()->create(['surname' => 'APPROVED-SURNAME', 'firstname' => 'Ana']);
    $employee->pdsFamily()->create(['spouse_surname' => 'APPROVED-FATHER']);
    $payload = ['personal' => ['surname' => 'DRAFT-SURNAME', 'firstname' => 'Ana'], 'family' => ['spouse_surname' => 'DRAFT-FATHER']];
    $this->actingAs($user)->get(route('pds.edit'))->assertOk()
        ->assertSee('editing: false', false)->assertSee(':disabled="!editing"', false)->assertSee('Save Data');
    $this->put(route('pds.update'), $payload)->assertSessionHasNoErrors()->assertRedirect(route('pds.edit'));
    $this->get(route('pds.edit'))->assertOk()->assertSee('DRAFT-SURNAME')->assertSee('DRAFT-FATHER')->assertSee('editing: false', false);
    $this->get(route('pds.print'))->assertOk()->assertSee('APPROVED-SURNAME')->assertSee('APPROVED-FATHER')
        ->assertDontSee('DRAFT-SURNAME')->assertDontSee('DRAFT-FATHER');
    $this->mock(\App\Services\PdsPdfExporter::class, function ($mock) {
        $mock->shouldReceive('download')->once()->withArgs(fn ($record, $disposition) =>
            $record->pdsPersonal->surname === 'APPROVED-SURNAME' && $disposition === 'attachment')
            ->andReturn(response('%PDF-test', 200, ['Content-Type' => 'application/pdf']));
    });
    $this->get(route('pds.download'))->assertOk()->assertHeader('Content-Type', 'application/pdf');
    $reviewUrl = route('pds-reviews.store', $employee);
    $this->post($reviewUrl, ['section_name' => 'Personal Information', 'status' => 'approved'])->assertForbidden();
    $hr = User::factory()->create(['must_change_password' => false]);
    Employee::factory()->for($hr)->create(['account_role' => 'hrstaff']);
    $this->actingAs($hr)->get(route('employees.pds.print', $employee))->assertOk()->assertSee('DRAFT-SURNAME');
    $this->post($reviewUrl, ['section_name' => 'Personal Information', 'status' => 'approved'])->assertRedirect();
    $this->post($reviewUrl, ['section_name' => 'Personal Information', 'status' => 'approved'])->assertStatus(409);
    $this->post($reviewUrl, ['section_name' => 'Personal Information', 'status' => 'rejected', 'remarks' => 'Repeat decision'])->assertStatus(409);
    $this->actingAs($user)->get(route('pds.print'))->assertOk()->assertSee('DRAFT-SURNAME')
        ->assertSee('APPROVED-FATHER')->assertDontSee('DRAFT-FATHER');
    $this->actingAs($hr)->post($reviewUrl, ['section_name' => 'Family Background', 'status' => 'rejected', 'remarks' => 'Please verify'])->assertRedirect();
    $this->actingAs($user)->get(route('pds.print'))->assertOk()->assertSee('APPROVED-FATHER')->assertDontSee('DRAFT-FATHER');
    $payload['family']['spouse_surname'] = 'DRAFT-FATHER-RESUBMITTED';
    $this->put(route('pds.update'), $payload)->assertRedirect();
    $this->actingAs($hr)->post(route('pds-reviews.approve-all', $employee))->assertRedirect();
    $this->actingAs($user)->get(route('pds.print'))->assertOk()->assertSee('DRAFT-FATHER');
    expect(data_get($employee->pdsSnapshots()->where('year', now()->year)->first()->data, 'relations.pdsFamily.spouse_surname'))->toBe('DRAFT-FATHER-RESUBMITTED');
    // A second save must not replace the previously approved baseline.
    $payload['personal']['surname'] = 'SECOND-DRAFT';
    $this->put(route('pds.update'), $payload)->assertRedirect();
    $this->get(route('pds.print'))->assertOk()->assertSee('DRAFT-SURNAME')->assertDontSee('SECOND-DRAFT');
    expect(app(PdsSnapshotService::class)->employeeForYear($employee->fresh(), now()->year)->pdsPersonal->surname)->toBe('DRAFT-SURNAME');
});

test('PDS highlights use every changed input and completed sections show no decision controls', function () {
    $hr = User::factory()->create(['must_change_password' => false]);
    Employee::factory()->for($hr)->create(['account_role' => 'hrstaff']);
    $owner = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::factory()->for($owner)->create(['account_role' => 'employee']);
    $employee->pdsPersonal()->create(['surname' => 'Test', 'firstname' => 'Ana', 'blood_type' => 'O+']);
    $employee->pdsSectionReviews()->create(['section_name' => 'Personal Information', 'status' => 'pending',
        'changed_fields' => ['surname', 'height', 'weight', 'blood_type', 'email_address'], 'remarks' => 'Bottom remarks test']);
    $employee->pdsSectionReviews()->create(['section_name' => 'Civil Service Eligibility', 'status' => 'pending',
        'changed_fields' => ['0.title', '0.rating', '0.license_number']]);
    $response = $this->actingAs($hr)->get(route('employees.pds.show', $employee))->assertOk();
    foreach (['personal[surname]', 'personal[height]', 'personal[weight]', 'personal[blood_type]', 'personal[email_address]', 'eligibility[0][title]', 'eligibility[0][rating]', 'eligibility[0][license_number]'] as $name) {
        $response->assertSee('#pds-data-fields [name="'.$name.'"]', false);
    }
    $this->post(route('pds-reviews.store', $employee), ['section_name' => 'Personal Information', 'status' => 'approved'])->assertRedirect();
    $response = $this->get(route('employees.pds.show', $employee))->assertOk();
    $dom = new DOMDocument;
    @$dom->loadHTML($response->getContent());
    $xpath = new DOMXPath($dom);
    $formQuery = '//form[input[@name="section_name" and @value="Personal Information"]]';
    expect($xpath->query($formQuery.'//button[@name="status"]')->length)->toBe(0);
    $this->actingAs($owner)->get(route('pds.edit'))->assertOk()
        ->assertSee(':disabled="editing"', false)->assertDontSee('x-show="!editing"', false)
        ->assertSee('font-size: 0.8125rem', false)
        ->assertSee('height: 2.25rem', false)
        ->assertSee('border: 1px solid #dc2626', false)
        ->assertDontSee('background-color: #fff1f2', false)
        ->assertSee('pds-work-table w-full table-fixed', false);
    $this->actingAs($hr)->get(route('employees.show', ['employee' => $employee, 'tab' => 'pds']))->assertOk()
        ->assertSee('id="employee-pds-year"', false)->assertSee('id="employee-saln-year"', false);
});

test('missing section review does not display a false pending badge', function () {
    $hr = User::factory()->create(['must_change_password' => false]);
    Employee::factory()->for($hr)->create(['account_role' => 'hrstaff']);
    $employee = Employee::factory()->create(['account_role' => 'employee']);
    $employee->pdsPersonal()->create(['surname' => 'Test', 'firstname' => 'Ana']);
    $response = $this->actingAs($hr)->get(route('employees.pds.show', $employee))->assertOk();
    $dom = new DOMDocument;
    @$dom->loadHTML($response->getContent());
    $xpath = new DOMXPath($dom);
    $form = $xpath->query('//form[input[@name="section_name" and @value="Personal Information"]]')->item(0);
    expect($form->textContent)->toContain('No pending updates')->not->toContain('Status: Pending');
    $employee->pdsSectionReviews()->create(['section_name' => 'Personal Information', 'status' => 'pending', 'changed_fields' => ['surname']]);
    $this->get(route('employees.pds.show', $employee))->assertOk()->assertSee('Status: Pending');
});

test('replacing a PDS signature retains the approved image until HR approval', function () {
    Notification::fake();
    \Illuminate\Support\Facades\Storage::fake('public');
    \Illuminate\Support\Facades\Storage::fake('documents');
    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::factory()->for($user)->create(['account_role' => 'employee', 'e_signature_path' => 'employee-signatures/approved.png']);
    \Illuminate\Support\Facades\Storage::disk('public')->put('employee-signatures/approved.png', 'existing image');
    $employee->pdsPersonal()->create(['surname' => 'Test', 'firstname' => 'Ana']);
    $employee->pdsGovId()->create(['signature_path' => 'employee-signatures/approved.png']);
    $this->actingAs($user)->put(route('pds.update'), [
        'personal' => ['surname' => 'Test', 'firstname' => 'Ana'],
        'pds_signature' => \Illuminate\Http\UploadedFile::fake()->image('new.png'),
    ])->assertSessionHasNoErrors()->assertRedirect();
    \Illuminate\Support\Facades\Storage::disk('public')->assertExists('employee-signatures/approved.png');
    $official = app(PdsSnapshotService::class)->employeeForYear($employee->fresh(), now()->year);
    expect($official->pdsGovId->signature_path)->toBe('employee-signatures/approved.png')
        ->and($employee->fresh()->pdsGovId->signature_path)->not->toBe('employee-signatures/approved.png');
});
