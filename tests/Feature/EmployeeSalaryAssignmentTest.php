<?php

use App\Models\Employee;
use App\Models\User;
use App\Models\SalaryRate;

test('HR assigns a salary when creating an employee and it fills the current PDS work record', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-11-21'));
    \Illuminate\Support\Facades\Mail::fake();
    $hr = User::factory()->create(['must_change_password' => false]);
    Employee::factory()->for($hr)->create(['account_role' => 'hrstaff']);
    SalaryRate::create(['year' => 2026, 'salary_grade' => 'SG 11', 'salary_step' => 'Step 1', 'monthly_salary' => 31705, 'is_active' => true]);
    SalaryRate::create(['year' => 2026, 'salary_grade' => 'SG 11', 'salary_step' => 'Step 2', 'monthly_salary' => 31820, 'is_active' => true]);
    $payload = ['lastname' => 'SalaryOwner', 'firstname' => 'New', 'contact_number' => '09123456789', 'notification_email' => 'salary-owner@example.test',
        'division' => 'Migrant Workers Processing Division', 'position' => 'Officer', 'account_role' => 'employee', 'employment_status' => 'Regular',
        'service_start_date' => '2026-10-01', 'hris_username' => 'salary.owner', 'salary_grade' => 'SG 11', 'salary_step' => 'Step 1', 'salary_schedule_year' => 2026, 'monthly_salary' => 1];
    $this->actingAs($hr)->get(route('employees.create'))->assertOk()->assertSee('name="monthly_salary"', false);
    $this->post(route('employees.store'), $payload)->assertSessionHasNoErrors();
    $employee = Employee::where('lastname', 'SalaryOwner')->firstOrFail();
    expect((float) $employee->monthly_salary)->toBe(31705.0)->and($employee->pdsWorkExperiences()->count())->toBe(1)
        ->and((float) $employee->pdsWorkExperiences()->first()->monthly_salary)->toBe(31705.0)
        ->and($employee->pdsWorkExperiences()->first()->date_to)->toBeNull();
    expect($employee->pdsPersonal->surname)->toBe('SalaryOwner')
        ->and($employee->pdsPersonal->mobile_no)->toBe('09123456789')
        ->and(\Carbon\Carbon::parse($employee->pdsWorkExperiences()->first()->date_from)->format('Y-m-d'))->toBe('2026-10-01');
    $this->put(route('employees.update', $employee), [...$payload, 'salary_step' => 'Step 2'])->assertSessionHasNoErrors();
    expect((float) $employee->fresh()->monthly_salary)->toBe(31820.0)->and($employee->pdsWorkExperiences()->count())->toBe(1)
        ->and((float) $employee->fresh()->pds_approved_data['relations']['pdsWorkExperiences'][0]['monthly_salary'])->toBe(31820.0);
    $employee->user->update(['must_change_password' => false, 'privacy_consent' => true]);
    $this->actingAs($employee->user)->get(route('pds.edit'))->assertOk()->assertSee('Step 2');
});

test('use CTO purpose is optional and earn COC requires supporting evidence', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-11-23'));
    \Illuminate\Support\Facades\Notification::fake();
    \Illuminate\Support\Facades\Storage::fake('public');
    \Illuminate\Support\Facades\Storage::fake('documents');
    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::factory()->for($user)->create(['account_role' => 'employee', 'cto_balance' => 8]);
    $this->actingAs($user)->post(route('my-cto.store'), ['type' => 'use', 'date_start' => '2026-11-24', 'hours' => 2])->assertSessionHasNoErrors();
    expect($employee->ctoRequests()->where('type', 'use')->firstOrFail()->purpose)->toBe('');
    $this->post(route('my-cto.store'), ['type' => 'earn', 'date_start' => '2026-11-20', 'hours' => 2])->assertSessionHasErrors('attachment');
    $this->post(route('my-cto.store'), ['type' => 'earn', 'date_start' => '2026-11-20', 'hours' => 2,
        'attachment' => \Illuminate\Http\UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf')])->assertSessionHasNoErrors();
    $earn = $employee->ctoRequests()->where('type', 'earn')->firstOrFail();
    $this->put(route('my-cto.update', $earn), ['type' => 'earn', 'date_start' => '2026-11-20', 'hours' => 3])->assertSessionHasNoErrors();
    expect($earn->fresh()->attachment_path)->not->toBeNull();
});

test('employee creation prefills personal and current DMW work details even without a salary', function () {
    \Illuminate\Support\Facades\Mail::fake();
    $hr = User::factory()->create(['must_change_password' => false]);
    Employee::factory()->for($hr)->create(['account_role' => 'hrstaff']);
    $payload = ['lastname' => 'Prefilled', 'firstname' => 'Ana', 'middlename' => 'L', 'contact_number' => '09123456789',
        'notification_email' => 'prefill@example.test', 'division' => 'Migrant Workers Processing Division', 'position' => 'Officer',
        'account_role' => 'employee', 'employment_status' => 'Regular', 'service_start_date' => '2026-10-01', 'hris_username' => 'prefilled.ana'];
    $this->actingAs($hr)->post(route('employees.store'), $payload)->assertRedirect()->assertSessionHasNoErrors();
    $employee = Employee::where('lastname', 'Prefilled')->firstOrFail();
    expect($employee->pdsPersonal->surname)->toBe('Prefilled')->and($employee->pdsPersonal->firstname)->toBe('Ana')
        ->and($employee->pdsPersonal->mobile_no)->toBe('09123456789')->and($employee->pdsPersonal->email_address)->toBe('prefill@example.test');
    $work = $employee->pdsWorkExperiences()->firstOrFail();
    expect($work->company)->toBe('Department of Migrant Workers')->and($work->position_title)->toBe('Officer')
        ->and($work->date_from)->toBe('2026-10-01')->and($work->date_to)->toBeNull()->and($work->monthly_salary)->toBeNull();
    $employee->pdsPersonal()->update(['blood_type' => 'O+']);
    $this->put(route('employees.update', $employee), [...$payload, 'contact_number' => '09987654321', 'position' => 'Senior Officer', 'service_start_date' => '2026-09-01'])->assertRedirect()->assertSessionHasNoErrors();
    $employee->refresh();
    expect($employee->pdsPersonal->mobile_no)->toBe('09987654321')->and($employee->pdsPersonal->blood_type)->toBe('O+')
        ->and($employee->pdsWorkExperiences()->count())->toBe(1)->and($employee->pdsWorkExperiences()->first()->date_from)->toBe('2026-09-01')
        ->and($employee->pdsWorkExperiences()->first()->position_title)->toBe('Senior Officer')
        ->and($employee->pds_approved_data['relations']['pdsPersonal']['blood_type'] ?? null)->toBeNull();
});

test('HR employment edits preserve pending PDS salary changes without publishing them', function () {
    $user = User::factory()->create();
    $employee = Employee::create(['user_id' => $user->id, 'firstname' => 'Pending', 'lastname' => 'Salary', 'account_role' => 'employee', 'position' => 'Officer', 'monthly_salary' => 30000, 'service_start_date' => '2026-01-01']);
    $service = app(\App\Services\EmployeeSalaryService::class);
    $service->syncPds($employee);
    $employee->pdsWorkExperiences()->first()->update(['monthly_salary' => 35000]);
    $employee->update(['position' => 'Senior Officer']);
    $service->syncPds($employee, ['position']);
    expect((float) $employee->pdsWorkExperiences()->first()->monthly_salary)->toBe(35000.0)
        ->and((float) $employee->fresh()->pds_approved_data['relations']['pdsWorkExperiences'][0]['monthly_salary'])->toBe(30000.0);
});

test('existing account contact details fill missing PDS fields without replacing entered values or older years', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-11-22'));
    $user = User::factory()->create(['must_change_password' => false, 'privacy_consent' => true]);
    $employee = Employee::create(['user_id' => $user->id, 'firstname' => 'Existing', 'lastname' => 'Contact', 'account_role' => 'employee',
        'contact_number' => '09123456789', 'notification_email' => 'existing@example.test', 'service_start_date' => '2025-01-01']);
    $employee->pdsPersonal()->create(['surname' => 'Contact', 'firstname' => 'Existing', 'mobile_no' => null, 'email_address' => null, 'blood_type' => 'O+']);
    $employee->forceFill(['pds_approved_data' => ['relations' => ['pdsPersonal' => ['surname' => 'Contact', 'blood_type' => 'A+']]]])->save();
    \App\Models\PdsSnapshot::create(['employee_id' => $employee->id, 'year' => 2025, 'data' => ['relations' => ['pdsPersonal' => ['mobile_no' => null]]]]);
    $this->actingAs($user)->get(route('pds.edit'))->assertOk()->assertViewHas('employee', fn ($draft) => $draft->pdsPersonal->mobile_no === '09123456789' && $draft->pdsPersonal->email_address === 'existing@example.test');
    expect($employee->fresh()->pds_approved_data['relations']['pdsPersonal']['blood_type'])->toBe('A+')
        ->and($employee->pdsSnapshots()->where('year', 2025)->first()->data['relations']['pdsPersonal']['mobile_no'])->toBeNull();
    $employee->pdsPersonal()->update(['email_address' => 'pds-choice@example.test']);
    app(\App\Services\EmployeeSalaryService::class)->prefillMissingContacts($employee->fresh());
    expect($employee->pdsPersonal()->first()->email_address)->toBe('pds-choice@example.test');
});

test('existing accounts get a current DMW work entry without creating a new account or duplicates', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-11-22'));
    $user = User::factory()->create(['must_change_password' => false, 'privacy_consent' => true]);
    $employee = Employee::create(['user_id' => $user->id, 'firstname' => 'Existing', 'lastname' => 'Work', 'account_role' => 'employee',
        'position' => 'Administrative Officer', 'employment_status' => 'Regular', 'service_start_date' => '2026-10-01', 'monthly_salary' => 30000]);
    $employee->pdsWorkExperiences()->create(['position_title' => 'Old job', 'company' => 'Previous Employer', 'date_from' => '2020-01-01', 'date_to' => '2025-12-31']);
    foreach ([1, 2] as $visit) $this->actingAs($user)->get(route('pds.edit'))->assertOk()->assertViewHas('employee', fn ($draft) => $draft->pdsWorkExperiences->contains('company', 'Department of Migrant Workers'));
    $work = $employee->pdsWorkExperiences()->where('hr_salary_assignment', true)->firstOrFail();
    expect($work->date_from)->toBe('2026-10-01')->and($work->date_to)->toBeNull()->and((float) $work->monthly_salary)->toBe(30000.0)
        ->and($employee->pdsWorkExperiences()->count())->toBe(2)->and(User::count())->toBe(1);
    $work->update(['monthly_salary' => 35000]);
    app(\App\Services\EmployeeSalaryService::class)->prefillMissingEmployment($employee->fresh());
    expect((float) $work->fresh()->monthly_salary)->toBe(35000.0);
});
