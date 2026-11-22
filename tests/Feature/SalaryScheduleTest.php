<?php

use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\SalaryRate;
use App\Models\User;
use App\Services\SalaryScheduleService;

test('HR manages unique yearly grade step rates and unauthorized employees cannot change them', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-11-21'));
    $hr = User::factory()->create(['must_change_password' => false]);
    Employee::create(['user_id' => $hr->id, 'firstname' => 'Salary', 'lastname' => 'HR', 'account_role' => 'hrstaff']);
    $data = ['year' => 2026, 'salary_grade' => 'SG 11', 'salary_step' => 'Step 1', 'monthly_salary' => '31705.00', 'is_active' => 1];
    $this->actingAs($hr)->post(route('utilities.salary-rates.store'), $data)->assertSessionHasNoErrors();
    $this->post(route('utilities.salary-rates.store'), $data)->assertSessionHasErrors('salary_grade');
    $rate = SalaryRate::firstOrFail();
    $this->put(route('utilities.salary-rates.update', $rate), [...$data, 'monthly_salary' => '32000.00'])->assertSessionHasNoErrors();
    $this->get(route('utilities.index', ['tab' => 'salary-schedule']))->assertOk()->assertSee('32,000.00');
    $rows = app(SalaryScheduleService::class)->normalizeWorkRows([['salary_grade' => 'SG 11', 'salary_step' => 'Step 1', 'salary_schedule_year' => 2026, 'monthly_salary' => 999999]]);
    expect((float) $rows[0]['monthly_salary'])->toBe(32000.0);
    $user = User::factory()->create(['must_change_password' => false]);
    Employee::create(['user_id' => $user->id, 'firstname' => 'Salary', 'lastname' => 'Employee', 'account_role' => 'employee']);
    $this->actingAs($user)->post(route('utilities.salary-rates.store'), [...$data, 'year' => 2025])->assertForbidden();
});

test('monetization uses approved government PDS salary and snapshots it against later changes', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-11-21'));
    \Illuminate\Support\Facades\Notification::fake();
    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::create(['user_id' => $user->id, 'firstname' => 'Salary', 'lastname' => 'Owner', 'account_role' => 'employee', 'employment_status' => 'Regular', 'service_start_date' => '2025-01-01']);
    $vl = LeaveType::create(['name' => 'Vacation Leave', 'is_active' => true, 'days_per_year' => 15]);
    $employee->ensureLeaveCredits(2026);
    $employee->leaveCredits()->where('leave_type_id', $vl->id)->update(['adjusted' => 30]);
    $row = ['company' => 'Department of Migrant Workers', 'date_from' => '2025-01-01', 'salary_grade' => 'SG 11', 'salary_step' => 'Step 1', 'salary_schedule_year' => 2026, 'monthly_salary' => 31705, 'is_gov_service' => 1];
    $employee->forceFill(['pds_approved_data' => ['relations' => ['pdsWorkExperiences' => [$row, [...$row, 'is_gov_service' => 0, 'monthly_salary' => 999999]]]]])->save();
    $employee->pdsWorkExperiences()->create([...$row, 'position_title' => 'Pending draft', 'company' => 'DMW', 'monthly_salary' => 888888]);
    expect(app(SalaryScheduleService::class)->approvedSalary($employee)['amount'])->toBe(31705.0);
    $this->actingAs($user)->post(route('leaves.store'), ['leave_type_id' => $vl->id, 'monetization_requested' => 1, 'monetization_basis' => 'regular', 'monetization_days' => 10, 'monetization_salary' => 1])->assertSessionHasNoErrors();
    $leave = $employee->leaveRequests()->firstOrFail();
    expect((float) $leave->monetization_salary)->toBe(31705.0)->and($leave->monetization_salary_step)->toBe('Step 1');
    $employee->forceFill(['pds_approved_data' => ['relations' => ['pdsWorkExperiences' => [[...$row, 'monthly_salary' => 35000]]]]])->save();
    expect((float) $leave->fresh()->monetization_salary)->toBe(31705.0);
    $this->get(route('leaves.create'))->assertOk()->assertSee('HR-approved PDS');
    $employee->forceFill(['pds_approved_data' => ['relations' => ['pdsWorkExperiences' => []]]])->save();
    $user->unsetRelation('employee');
    $this->get(route('leaves.create'))->assertOk()->assertSee('No salary recorded in the PDS');
    $this->post(route('leaves.store'), ['leave_type_id' => $vl->id, 'monetization_requested' => 1])->assertSessionHasErrors('monetization_salary');
});

test('PDS saves the configured step amount as a draft until HR publishes work experience', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-11-21'));
    \Illuminate\Support\Facades\Notification::fake();
    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::create(['user_id' => $user->id, 'firstname' => 'Step', 'lastname' => 'Owner', 'account_role' => 'employee']);
    $page = $this->actingAs($user)->get(route('pds.edit'))->assertOk();
    preg_match('/<select[^>]*x-model="w.salary_schedule_year".*?<\/select>/s', $page->getContent(), $yearSelect);
    expect($yearSelect[0] ?? '')->toContain('value="2026"', 'value="2025"', 'Select year');
    SalaryRate::create(['year' => 2026, 'salary_grade' => 'SG 11', 'salary_step' => 'Step 2', 'monthly_salary' => 31820, 'is_active' => true]);
    $row = ['date_from' => '2026-01-01', 'position_title' => 'Officer', 'company' => 'DMW', 'salary_grade' => 'SG 11', 'salary_step' => 'Step 2', 'salary_schedule_year' => 2026, 'monthly_salary' => 1, 'is_gov_service' => 1];
    $this->actingAs($user)->put(route('pds.update'), ['work_experience' => [$row]])->assertSessionHasNoErrors();
    expect((float) $employee->pdsWorkExperiences()->firstOrFail()->monthly_salary)->toBe(31820.0)
        ->and(app(SalaryScheduleService::class)->approvedSalary($employee->fresh()))->toBeNull();
    app(\App\Services\PdsSnapshotService::class)->publishSections($employee->fresh(), ['Work Experience']);
    expect(app(SalaryScheduleService::class)->approvedSalary($employee->fresh())['amount'])->toBe(31820.0);
    $this->get(route('pds.edit'))->assertOk()->assertSee('Salary Year')->assertSee('salaryRateFor');
    $this->put(route('pds.update'), ['work_experience' => [[...$row, 'salary_step' => 'Step 8']]])->assertSessionHasErrors('work_experience.0.salary_step');
});

test('monetization selects the highest approved government salary excluding future work and private employment', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-11-21'));
    $user = User::factory()->create();
    $employee = Employee::create(['user_id' => $user->id, 'firstname' => 'Current', 'lastname' => 'Salary', 'account_role' => 'employee']);
    $base = ['company' => 'DMW Region XI', 'date_from' => '2026-01-01', 'date_to' => null, 'is_gov_service' => 1, 'salary_grade' => 'SG 11', 'salary_step' => 'Step 1', 'salary_schedule_year' => 2026, 'monthly_salary' => 31000];
    $rows = [
        [...$base, 'company' => 'Other Government Agency', 'monthly_salary' => 90000],
        [...$base, 'date_from' => '2025-01-01', 'date_to' => '2026-01-01', 'monthly_salary' => 80000],
        [...$base, 'date_from' => '2027-01-01', 'monthly_salary' => 170000],
        [...$base, 'is_gov_service' => 0, 'monthly_salary' => 180000],
        [...$base, 'date_from' => '2026-06-01', 'monthly_salary' => 32000],
        $base,
    ];
    $employee->forceFill(['pds_approved_data' => ['relations' => ['pdsWorkExperiences' => $rows]]])->save();
    expect(app(SalaryScheduleService::class)->approvedSalary($employee)['amount'])->toBe(90000.0);
    $rows[] = [...$base, 'date_from' => '2026-10-01', 'monthly_salary' => null];
    $employee->forceFill(['pds_approved_data' => ['relations' => ['pdsWorkExperiences' => $rows]]])->save();
    expect(app(SalaryScheduleService::class)->approvedSalary($employee)['amount'])->toBe(90000.0);
    $employee->forceFill(['pds_approved_data' => ['relations' => ['pdsWorkExperiences' => []]]])->save();
    expect(app(SalaryScheduleService::class)->approvedSalary($employee))->toBeNull();
});

test('salary schedule searches filters and sorts amounts numerically across all records', function () {
    $hr = User::factory()->create(['must_change_password' => false]);
    Employee::create(['user_id' => $hr->id, 'firstname' => 'Rates', 'lastname' => 'HR', 'account_role' => 'hrstaff']);
    foreach ([[2026, 'SG 1', 'Step 1', 14634, true], [2026, 'SG 12', 'Step 2', 34069, true], [2025, 'SG 12', 'Step 2', 32000, false]] as [$year, $grade, $step, $amount, $active]) {
        SalaryRate::create(['year' => $year, 'salary_grade' => $grade, 'salary_step' => $step, 'monthly_salary' => $amount, 'is_active' => $active]);
    }
    $this->actingAs($hr)->get(route('utilities.index', ['tab' => 'salary-schedule']))->assertOk()
        ->assertViewHas('salaryRates', fn ($rates) => (float) $rates->first()->monthly_salary === 14634.0);
    $this->get(route('utilities.index', ['tab' => 'salary-schedule', 'salary_sort' => 'salary_desc']))->assertOk()
        ->assertViewHas('salaryRates', fn ($rates) => (float) $rates->first()->monthly_salary === 34069.0);
    $this->get(route('utilities.index', ['tab' => 'salary-schedule', 'salary_search' => '34,069', 'salary_year' => 2026, 'salary_grade' => 'SG 12', 'salary_step' => 'Step 2', 'salary_status' => 'active']))->assertOk()
        ->assertViewHas('salaryRates', fn ($rates) => $rates->total() === 1 && (float) $rates->first()->monthly_salary === 34069.0);
    $this->get(route('utilities.index', ['tab' => 'salary-schedule', 'salary_status' => 'inactive']))->assertOk()
        ->assertViewHas('salaryRates', fn ($rates) => $rates->total() === 1 && $rates->first()->year === 2025);
});
