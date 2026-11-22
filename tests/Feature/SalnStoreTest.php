<?php

use App\Models\Employee;
use App\Models\Saln;
use App\Models\User;

function createApprovedEmployeeUser(array $employeeOverrides = []): array
{
    $user = User::factory()->create(['is_approved' => true]);
    $employee = Employee::create(array_merge([
        'user_id' => $user->id,
        'lastname' => 'Reyes',
        'firstname' => 'Maria',
        'middlename' => 'Bautista',
        'division' => 'HRIS',
        'position' => 'HR Officer',
        'account_role' => 'EMPLOYEE',
    ], $employeeOverrides));

    return compact('user', 'employee');
}

test('saln create page uses NA when no PDS declarant fields exist for the year', function () {
    ['user' => $user] = createApprovedEmployeeUser();

    $this->actingAs($user)
        ->get(route('salns.create'))
        ->assertOk()
        ->assertSee('SALN Update')
        ->assertSee('name="declarant_info[family_name]" value="N/A"', false)
        ->assertSee('name="declarant_info[first_name]" value="N/A"', false);
});

test('employee can submit a complete saln form', function () {
    ['user' => $user, 'employee' => $employee] = createApprovedEmployeeUser();

    $payload = [
        'type_of_filing' => 'annual_filing',
        'as_of_date' => '2026-12-31',
        'declarant_info' => [
            'family_name' => 'Reyes',
            'first_name' => 'Maria',
            'middle_initial' => 'B',
            'position' => 'HR Officer',
            'agency' => 'HRIS',
            'office_address' => 'Manila',
            'multiple_spouses' => ['', ''],
            'multiple_marriages_not_applicable' => '1',
        ],
        'spouse_info' => [
            'family_name' => 'Reyes',
            'first_name' => 'Juan',
        ],
        'filing_status' => 'joint',
        'children' => [
            ['name' => 'Ana Reyes', 'age' => 10],
        ],
        'real_properties' => [
            [
                'description' => 'House and Lot',
                'kind' => 'Residential',
                'location' => 'Quezon City',
                'assessed_value' => 1000000,
                'fair_market_value' => 1500000,
                'acquisition_year' => '2018',
                'acquisition_mode' => 'Purchase',
                'acquisition_cost' => 1200000,
            ],
        ],
        'personal_properties' => [
            [
                'description' => 'Savings Account',
                'acquisition_year' => '2020',
                'acquisition_cost' => 250000,
            ],
        ],
        'liabilities' => [
            [
                'nature' => 'Housing Loan',
                'creditor' => 'Bank',
                'outstanding_balance' => 500000,
            ],
        ],
        'has_business_interests' => 0,
        'has_relatives_in_gov' => 0,
    ];

    $response = $this->actingAs($user)->post(route('salns.store'), $payload);

    $response->assertRedirect();

    $saln = Saln::first();

    expect($saln)->not->toBeNull()
        ->and($saln->employee_id)->toBe($employee->id)
        ->and((float) $saln->total_assets)->toBe(1450000.0)
        ->and((float) $saln->total_liabilities)->toBe(500000.0)
        ->and((float) $saln->net_worth)->toBe(950000.0)
        ->and($saln->children)->toHaveCount(1)
        ->and($saln->real_properties)->toHaveCount(1);
});

test('saln submission requires business rows when business interests is checked', function () {
    ['user' => $user] = createApprovedEmployeeUser();

    $payload = [
        'type_of_filing' => 'annual_filing',
        'as_of_date' => '2026-12-31',
        'declarant_info' => [
            'family_name' => 'Reyes',
            'first_name' => 'Maria',
            'position' => 'HR Officer',
            'agency' => 'HRIS',
            'office_address' => 'Manila',
        ],
        'filing_status' => 'not_applicable',
        'has_business_interests' => 1,
        'business_interests' => [],
        'has_relatives_in_gov' => 0,
    ];

    $this->actingAs($user)
        ->from(route('salns.create'))
        ->post(route('salns.store'), $payload)
        ->assertSessionHasErrors('business_interests');
});

test('saln pdf download returns a pdf file', function () {
    ['user' => $user, 'employee' => $employee] = createApprovedEmployeeUser();

    $saln = Saln::create([
        'review_status' => 'approved',
        'employee_id' => $employee->id,
        'type_of_filing' => 'annual_filing',
        'as_of_date' => '2026-12-31',
        'declarant_info' => [
            'family_name' => 'Reyes',
            'first_name' => 'Maria',
            'position' => 'HR Officer',
            'agency' => 'HRIS',
            'office_address' => 'Manila',
        ],
        'spouse_info' => [],
        'filing_status' => 'not_applicable',
        'children' => [],
        'real_properties' => [],
        'personal_properties' => [],
        'liabilities' => [],
        'has_business_interests' => false,
        'has_relatives_in_gov' => false,
        'total_assets' => 0,
        'total_liabilities' => 0,
        'net_worth' => 0,
    ]);

    $this->actingAs($user)
        ->get(route('salns.download', $saln))
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});
