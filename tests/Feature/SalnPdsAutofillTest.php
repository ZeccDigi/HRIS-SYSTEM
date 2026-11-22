<?php

use App\Models\Employee;
use App\Models\User;

test('SALN autofills PDS identity and spouse information and saves comma formatted money correctly', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-11-21'));
    \Illuminate\Support\Facades\Notification::fake();
    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::factory()->for($user)->create(['account_role' => 'employee', 'service_start_date' => '2025-01-01']);
    $employee->pdsPersonal()->create(['surname' => 'Garcia', 'firstname' => 'Anna', 'middlename' => 'Vera']);
    $employee->pdsFamily()->create(['spouse_surname' => 'Santos', 'spouse_firstname' => 'Juan', 'spouse_middlename' => 'Reyes', 'spouse_occupation' => 'Officer', 'spouse_employer' => 'Agency', 'spouse_business_address' => 'Davao']);
    $employee->pdsWorkExperiences()->create(['date_from' => '2026-01-01', 'date_to' => null, 'position_title' => 'Regional Aide', 'company' => 'DMW', 'is_gov_service' => 1]);
    $this->actingAs($user)->get(route('salns.create'))->assertOk()->assertSee('name="declarant_info[family_name]" value="Garcia"', false)->assertSee('name="spouse_info[family_name]" value="Santos"', false);
    $this->get(route('salns.index'))->assertOk()->assertSee('Garcia')->assertSee('Santos');
    $this->post(route('salns.store'), ['type_of_filing' => 'annual_filing', 'as_of_date' => '2026-12-31', 'filing_status' => 'joint', 'declarant_info' => [], 'spouse_info' => [],
        'real_properties' => [['description' => 'House', 'assessed_value' => '900,000.00', 'fair_market_value' => '1,200,000.50', 'acquisition_cost' => '1,000,000.25']],
        'personal_properties' => [['description' => 'Savings', 'acquisition_cost' => '31,705.00']],
        'liabilities' => [['nature' => 'Loan', 'creditor' => 'Bank', 'outstanding_balance' => '100,000.50']],
        'has_business_interests' => 0, 'has_relatives_in_gov' => 0])->assertSessionHasNoErrors();
    $saln = $employee->salns()->firstOrFail();
    expect($saln->declarant_info['family_name'])->toBe('Garcia')->and($saln->declarant_info['position'])->toBe('Regional Aide')
        ->and($saln->declarant_info['office_address'])->toBe('N/A')->and($saln->spouse_info['family_name'])->toBe('Santos')
        ->and((float) $saln->total_assets)->toBe(1031705.25)->and((float) $saln->net_worth)->toBe(931704.75);
    $this->get(route('salns.index'))->assertOk()->assertSee('1,000,000.25');
});

test('PDS salary accepts commas and work end date is optional', function () {
    \Illuminate\Support\Facades\Notification::fake();
    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::factory()->for($user)->create(['account_role' => 'employee']);
    $this->actingAs($user)->put(route('pds.update'), ['work_experience' => [['date_from' => '2025-01-01', 'date_to' => '', 'position_title' => 'Officer', 'company' => 'DMW', 'monthly_salary' => '31,705.00', 'is_gov_service' => 1]]])->assertSessionHasNoErrors();
    $row = $employee->pdsWorkExperiences()->firstOrFail();
    expect((float) $row->monthly_salary)->toBe(31705.0)->and($row->date_to)->toBeNull();
});
