<?php

use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

function purposeTestEmployee(): array
{
    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::create([
        'firstname' => 'Anna',
        'lastname' => 'Garcia',
        'division' => 'Migrant Workers Processing Division',
        'position' => 'Regional Aide',
        'account_role' => 'employee',
        'employment_status' => 'Regular',
        'service_start_date' => now()->subYears(2),
        'user_id' => $user->id,
    ]);

    $employee->forceFill(['pds_approved_data'=>['relations'=>['pdsWorkExperiences'=>[['company'=>'DMW','is_gov_service'=>1,'monthly_salary'=>35000,'date_from'=>'2025-01-01']]]]])->save();
    return [$user->fresh(), $employee];
}

test('leave application form removes general reason and offers monetization as another purpose', function () {
    [$user] = purposeTestEmployee();
    LeaveType::create(['name' => 'Vacation Leave', 'days_per_year' => 15, 'is_active' => true]);

    $this->actingAs($user)
        ->get(route('leaves.create'))
        ->assertOk()
        ->assertDontSee('Reason for Leave')
        ->assertSee('Other Purpose')
        ->assertSee('Monetization of Leave Credits');
});

test('legacy date-based monetization requires the new financial fields', function () {
    Notification::fake();
    [$user, $employee] = purposeTestEmployee();
    $vacation = LeaveType::create(['name' => 'Vacation Leave', 'days_per_year' => 15, 'is_active' => true]);
    $date = now()->addWeekday()->toDateString();

    $this->actingAs($user)->post(route('leaves.store'), [
        'leave_type_id' => $vacation->id,
        'start_date' => $date,
        'end_date' => $date,
        'is_paid' => 1,
        'leave_detail_type' => 'within_philippines',
        'leave_detail' => 'Davao City',
        'monetization_requested' => 1,
        'commutation_requested' => 0,
    ])->assertSessionHasErrors(['monetization_basis', 'monetization_days']);
    expect($employee->leaveRequests()->count())->toBe(0);
});

test('ordinary sick leave derives its internal reason from the structured detail', function () {
    Notification::fake();
    [$user, $employee] = purposeTestEmployee();
    $sick = LeaveType::create(['name' => 'Sick Leave', 'days_per_year' => 15, 'is_active' => true]);
    $date = now()->subWeekday()->toDateString();

    $this->actingAs($user)->post(route('leaves.store'), [
        'leave_type_id' => $sick->id,
        'start_date' => $date,
        'end_date' => $date,
        'is_paid' => 1,
        'leave_detail_type' => 'out_patient',
        'leave_detail' => 'Flu',
        'commutation_requested' => 0,
    ])->assertRedirect(route('leaves.index'));

    expect($employee->leaveRequests()->firstOrFail()->reason)->toBe('Flu');
});

test('sick leave monetization cannot be submitted as an ordinary dated absence', function () {
    Notification::fake();
    [$user, $employee] = purposeTestEmployee();
    $sick = LeaveType::create(['name' => 'Sick Leave', 'days_per_year' => 15, 'is_active' => true]);
    $date = now()->subWeekday()->toDateString();

    $this->actingAs($user)->post(route('leaves.store'), [
        'leave_type_id' => $sick->id,
        'start_date' => $date,
        'end_date' => $date,
        'is_paid' => 1,
        'leave_detail_type' => 'out_patient',
        'leave_detail' => 'Medical consultation',
        'monetization_requested' => 1,
        'commutation_requested' => 0,
    ])->assertSessionHasErrors(['monetization_basis', 'monetization_days']);
    expect($employee->leaveRequests()->count())->toBe(0);
});

