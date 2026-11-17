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

test('vacation leave monetization is stored for reports and the official form', function () {
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
    ])->assertRedirect(route('leaves.index'));

    $leave = $employee->leaveRequests()->firstOrFail();
    expect($leave->reason)->toBe('Monetization of Leave Credits')
        ->and($leave->leave_detail)->toBe('Davao City');

    $this->actingAs($user)
        ->get(route('leaves.print', $leave))
        ->assertOk()
        ->assertSee('Monetization of Leave Credits');
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

test('sick leave can also be filed for monetization', function () {
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
    ])->assertRedirect(route('leaves.index'));

    expect($employee->leaveRequests()->firstOrFail()->reason)->toBe('Monetization of Leave Credits');
});

