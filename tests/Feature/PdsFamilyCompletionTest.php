<?php

use App\Models\Employee;
use App\Models\User;
use App\Services\PdsCompletionService;
use App\Services\PdsSnapshotService;
use App\Services\SalnPdsDefaultsService;
use Illuminate\Support\Facades\Notification;

it('saves child birthdays and returns date-only values for date inputs', function () {
    Notification::fake();
    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::factory()->for($user)->create(['account_role' => 'employee']);
    $this->actingAs($user)->put(route('pds.update'), ['children' => [['fullname' => 'Test Child', 'date_of_birth' => '2020-02-03']]])->assertSessionHasNoErrors();
    $child = $employee->pdsChildren()->firstOrFail();
    expect($child->date_of_birth->format('Y-m-d'))->toBe('2020-02-03')
        ->and($child->toArray()['date_of_birth'])->toBe('2020-02-03');
    $this->get(route('pds.edit'))->assertOk()->assertSee('2020-02-03');
    $this->put(route('pds.update'), ['children' => [['fullname' => '', 'date_of_birth' => '2020-02-03']]])->assertSessionHasErrors('children.0.fullname');
    expect($employee->pdsChildren()->count())->toBe(1);
});

it('refreshes SALN spouse defaults from saved PDS family changes', function () {
    $employee = Employee::factory()->create();
    $employee->pdsFamily()->create(['spouse_surname' => 'Old', 'spouse_firstname' => 'Juan']);
    app(PdsSnapshotService::class)->ensureApprovedBaseline($employee);
    $employee->pdsFamily()->update(['spouse_surname' => 'Updated', 'spouse_middlename' => 'Reyes', 'spouse_occupation' => 'Officer', 'spouse_employer' => 'Test Agency', 'spouse_business_address' => 'Test Address']);
    $service = app(SalnPdsDefaultsService::class);
    expect($service->defaults($employee, (int) now()->year)['spouse_info']['family_name'])->toBe('Old');
    $employee->pdsSectionReviews()->create(['section_name' => 'Family Background', 'status' => 'approved']);
    app(PdsSnapshotService::class)->publishSections($employee, ['Family Background']);
    $defaults = $service->defaults($employee, (int) now()->year);
    $merged = $service->merge(['family_name' => 'N/A', 'position' => 'Old position', 'id_no' => 'kept'], $defaults['spouse_info']);
    expect($merged['family_name'])->toBe('Updated')->and($merged['middle_initial'])->toBe('R.')
        ->and($merged['position'])->toBe('Officer')->and($merged['agency'])->toBe('Test Agency')
        ->and($merged['office_address'])->toBe('Test Address')->and($merged['id_no'])->toBe('kept');
    expect($employee->fresh()->pds_approved_data['relations']['pdsFamily']['spouse_surname'])->toBe('Updated');
});

it('requires approval of all PDS sections including declaration before completion', function () {
    $service = app(PdsCompletionService::class);
    expect($service->summary(collect())['status'])->toBe('pending');
    $reviews = collect(PdsCompletionService::SECTIONS)->map(fn ($names) => (object) ['section_name' => $names[0], 'status' => 'approved']);
    expect($service->summary($reviews)['status'])->toBe('approved')->and($service->summary($reviews)['percentage'])->toBe(100);
    $reviews->last()->status = 'pending';
    expect($service->summary($reviews)['status'])->toBe('pending')->and($service->summary($reviews)['percentage'])->toBeLessThan(100);
    $reviews->last()->status = 'rejected';
    expect($service->summary($reviews)['status'])->toBe('rejected');
});

it('shows complete only while all nine PDS section reviews are approved', function () {
    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::factory()->for($user)->create(['account_role' => 'employee']);
    foreach (PdsCompletionService::SECTIONS as $names) {
        $employee->pdsSectionReviews()->create(['section_name' => $names[0], 'status' => 'approved']);
    }
    $this->actingAs($user)->get(route('pds.index'))->assertOk()->assertSee('Complete — HR Approved')->assertSee('9 of 9 sections approved by HR.');
    $employee->pdsSectionReviews()->where('section_name', 'Family Background')->update(['status' => 'pending']);
    $this->get(route('pds.index'))->assertOk()->assertDontSee('Complete — HR Approved')->assertSee('8 of 9 sections approved by HR.');
});
