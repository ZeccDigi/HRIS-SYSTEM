<?php

use App\Models\Employee;
use App\Models\User;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeaveCredit;
use App\Models\LeaveCreditTransaction;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Notification;

function safeguardsUser(string $role, string $division = 'Division A'): User
{
    $user = User::factory()->create(['must_change_password' => false]);
    Employee::create(['user_id' => $user->id, 'firstname' => 'Access', 'lastname' => $role, 'account_role' => $role, 'division' => $division, 'service_start_date' => '2026-01-01']);
    return $user->fresh();
}

test('HR-only pages and credit mutations do not implicitly allow Chiefs or RD', function () {
    foreach (['chief', 'regionaldirector', 'employee'] as $role) {
        $user = safeguardsUser($role);
        foreach (['audit-logs.login', 'audit-logs.activity', 'archive.index', 'reports.index'] as $route) $this->actingAs($user)->get(route($route))->assertForbidden();
        $this->post(route('employees.leave-credit-adjustments.store', $user->employee), [])->assertForbidden();
    }
    foreach (['hrstaff', 'admin'] as $role) $this->actingAs(safeguardsUser($role))->get(route('audit-logs.login'))->assertOk();
});

test('private attachments require ownership or authorized review and migration removes old public copies', function () {
    Storage::fake('public');
    Storage::fake('documents');
    $owner = safeguardsUser('employee');
    $other = safeguardsUser('employee');
    $chief = safeguardsUser('chief');
    $outsideChief = safeguardsUser('chief', 'Division B');
    $type = LeaveType::create(['name' => 'Sick Leave', 'is_active' => true]);
    $path = 'leave-attachments/protected.pdf';
    LeaveRequest::create(['employee_id' => $owner->employee->id, 'leave_type_id' => $type->id, 'date_filed' => now(), 'start_date' => now(), 'end_date' => now(), 'status' => 'pending', 'reason' => 'Medical', 'attachment_path' => $path, 'attachment_original_name' => 'medical.pdf']);
    Storage::disk('public')->put($path, '%PDF-private');
    $this->artisan('documents:protect')->assertExitCode(0);
    Storage::disk('public')->assertMissing($path);
    Storage::disk('documents')->assertExists($path);
    $url = route('supporting-documents.open', ['path' => $path]);
    $this->get($url)->assertRedirect(route('login'));
    $this->actingAs($owner)->get($url)->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
    $this->actingAs($other)->get($url)->assertForbidden();
    $this->actingAs($outsideChief)->get($url)->assertForbidden();
    $this->actingAs($chief)->get($url)->assertOk();
    $this->actingAs(safeguardsUser('hrstaff'))->get($url)->assertOk();
    $this->get(route('supporting-documents.open', ['path' => '../.env']))->assertNotFound();
});

test('approved PDS salary works without salary grade step or year while drafts remain excluded', function () {
    $employee = safeguardsUser('employee')->employee;
    $employee->forceFill(['pds_approved_data' => ['relations' => ['pdsWorkExperiences' => [
        ['date_from' => '2025-01-01', 'is_gov_service' => 1, 'monthly_salary' => 35000],
    ]]]])->save();
    $salary = app(\App\Services\SalaryScheduleService::class)->approvedSalary($employee);
    expect($salary['amount'])->toBe(35000.0)->and($salary['grade'])->toBeNull()->and($salary['year'])->toBeNull();
});

test('credit deductions reject zero and insufficient balances without changing history then post exact deductions once', function () {
    Notification::fake();
    $hr = safeguardsUser('hrstaff');
    $employee = safeguardsUser('employee')->employee;
    $type = LeaveType::create(['name' => 'Vacation Leave', 'is_active' => true]);
    $credit = LeaveCredit::create(['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'year' => now()->year, 'balance' => 0, 'earned' => 0, 'used' => 0, 'adjusted' => 0]);
    $url = route('employees.leave-credit-adjustments.store', $employee);
    $payload = ['leave_credit_id' => $credit->id, 'operation' => 'deduct', 'amount' => 1];
    $this->actingAs($hr)->post($url, $payload)->assertSessionHasErrors('amount');
    expect((float) $credit->fresh()->balance)->toBe(0.0)->and(LeaveCreditTransaction::count())->toBe(0);
    $this->post($url, [...$payload, 'operation' => 'add', 'amount' => 2])->assertRedirect();
    $this->post($url, [...$payload, 'amount' => 3])->assertSessionHasErrors('amount');
    expect((float) $credit->fresh()->balance)->toBe(2.0)->and(LeaveCreditTransaction::count())->toBe(1);
    $this->post($url, [...$payload, 'amount' => 2])->assertRedirect();
    $this->post($url, $payload)->assertSessionHasErrors('amount');
    expect((float) $credit->fresh()->balance)->toBe(0.0)->and(LeaveCreditTransaction::count())->toBe(2);
});

test('HR and employees see every recorded type of leave credit movement', function () {
    $user = safeguardsUser('employee');
    $hr = safeguardsUser('hrstaff');
    $type = LeaveType::create(['name' => 'Vacation Leave', 'is_active' => true]);
    foreach (['accrual', 'usage', 'monetization', 'carryover', 'adjustment', 'entitlement'] as $kind) LeaveCreditTransaction::create([
        'employee_id' => $user->employee->id, 'leave_type_id' => $type->id, 'type' => $kind,
        'credit_year' => 2026, 'amount' => in_array($kind, ['usage', 'monetization']) ? -1 : 1,
        'balance_after' => 5, 'effective_date' => '2026-11-22', 'description' => $kind,
    ]);
    $this->actingAs($user)->getJson(route('leaves.credit-history', ['leave_type_id' => $type->id, 'year' => 2026]))->assertOk()->assertJsonPath('total', 6);
    $this->actingAs($hr)->getJson(route('employees.leave-credit-history', ['employee' => $user->employee, 'leave_type_id' => $type->id, 'year' => 2026]))->assertOk()->assertJsonPath('total', 6);
});

test('historical PDS attachments remain available only to the owner and HR', function () {
    Storage::fake('documents');
    $owner = safeguardsUser('employee');
    $other = safeguardsUser('employee');
    $path = 'pds-training-attachments/historical.pdf';
    Storage::disk('documents')->put($path, '%PDF-private');
    \App\Models\PdsSnapshot::create(['employee_id' => $owner->employee->id, 'year' => 2025,
        'data' => ['relations' => ['pdsTrainings' => [['attachment_path' => $path]]]],
    ]);
    $url = route('supporting-documents.open', ['path' => $path]);
    $this->actingAs($owner)->get($url)->assertOk();
    $this->actingAs($other)->get($url)->assertForbidden();
    $this->actingAs(safeguardsUser('hrstaff'))->get($url)->assertOk();
});

test('announcement files use private storage and preserve their audience restriction', function () {
    Storage::fake('public');
    Storage::fake('documents');
    $hr = safeguardsUser('hrstaff');
    $owner = safeguardsUser('employee');
    $outside = safeguardsUser('employee', 'Division B');
    $path = 'announcement-attachments/order.pdf';
    $announcement = \App\Models\Announcement::create(['title' => 'Private office order', 'content' => 'Division only', 'author_id' => $hr->id,
        'is_published' => true, 'audience_division' => 'Division A', 'attachment_path' => $path]);
    Storage::disk('public')->put($path, '%PDF-order');
    $this->artisan('documents:protect')->assertExitCode(0);
    Storage::disk('public')->assertMissing($path);
    $url = route('announcements.attachment', $announcement);
    $this->actingAs($owner)->get($url)->assertOk();
    $this->actingAs($outside)->get($url)->assertForbidden();
});
