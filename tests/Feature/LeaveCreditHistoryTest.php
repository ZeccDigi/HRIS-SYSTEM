<?php

use App\Models\Employee;
use App\Models\User;
use App\Models\LeaveType;
use App\Models\LeaveCreditTransaction;

test('credit history shows only the signed in employee selected type and year', function () {
    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::create(['firstname' => 'History', 'lastname' => 'Owner', 'account_role' => 'employee', 'user_id' => $user->id]);
    $other = Employee::create(['firstname' => 'Other', 'lastname' => 'Employee']);
    $vl = LeaveType::create(['name' => 'Vacation Leave', 'is_active' => true]);
    $sl = LeaveType::create(['name' => 'Sick Leave', 'is_active' => true]);
    $this->actingAs($user)->get(route('leaves.index', ['tab' => 'credits']))
        ->assertOk()->assertSee('Days Left')->assertSee('open-credit-history');
    LeaveCreditTransaction::query()->delete();
    $values = ['employee_id' => $employee->id, 'leave_type_id' => $vl->id, 'type' => 'adjustment', 'amount' => 2, 'balance_after' => 3.25, 'effective_date' => '2026-11-21', 'description' => 'Verified correction'];
    LeaveCreditTransaction::create($values);
    LeaveCreditTransaction::create([...$values, 'employee_id' => $other->id, 'description' => 'Private other employee entry']);
    LeaveCreditTransaction::create([...$values, 'leave_type_id' => $sl->id]);
    LeaveCreditTransaction::create([...$values, 'effective_date' => '2025-11-21']);
    $this->actingAs($user)->getJson(route('leaves.credit-history', ['leave_type_id' => $vl->id, 'year' => 2026, 'employee_id' => $other->id]))
        ->assertOk()->assertJsonPath('total', 1)->assertJsonCount(1, 'rows')
        ->assertJsonPath('rows.0.remarks', 'Verified correction')->assertJsonPath('rows.0.operation', 'add')
        ->assertDontSee('Private other employee entry');
});

test('HR can trace employee adjustments and COC postings while employee access stays private', function () {
    $hr = User::factory()->create(['must_change_password' => false]);
    Employee::create(['firstname' => 'Credit', 'lastname' => 'Officer', 'user_id' => $hr->id, 'account_role' => 'hrstaff']);
    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::create(['firstname' => 'Credit', 'lastname' => 'Owner', 'user_id' => $user->id, 'account_role' => 'employee']);
    $other = Employee::create(['firstname' => 'Private', 'lastname' => 'Other']);
    $vl = LeaveType::create(['name' => 'Vacation Leave', 'is_active' => true]);
    LeaveCreditTransaction::create(['employee_id' => $employee->id, 'leave_type_id' => $vl->id, 'type' => 'adjustment', 'amount' => -1, 'balance_after' => 4, 'effective_date' => '2026-11-21', 'description' => 'Verified deduction', 'metadata' => ['year' => 2025, 'updated_by' => $hr->id]]);
    $base = ['employee_id' => $employee->id, 'type' => 'earn', 'date_start' => '2026-10-01', 'date_end' => '2026-10-01', 'hours' => 3, 'purpose' => '', 'status' => 'approved', 'cto_balance_after' => 3, 'credit_source' => 'hr_direct', 'recorded_by' => $hr->id, 'credits_posted_at' => '2026-11-21 10:00:00'];
    \App\Models\CtoRequest::create($base);
    \App\Models\CtoRequest::create([...$base, 'type' => 'use', 'hours' => 1, 'cto_balance_after' => 2]);
    \App\Models\CtoRequest::create([...$base, 'status' => 'pending', 'cto_balance_after' => null]);
    \App\Models\CtoRequest::create([...$base, 'employee_id' => $other->id]);
    $this->actingAs($hr)->getJson(route('employees.leave-credit-history', ['employee' => $employee, 'leave_type_id' => $vl->id, 'year' => 2025]))
        ->assertOk()->assertJsonPath('total', 1)->assertJsonPath('rows.0.amount', 1)->assertJsonPath('rows.0.operation', 'deduct')->assertJsonPath('rows.0.schedule', 'Nov 21, 2026');
    $this->actingAs($hr)->getJson(route('employees.coc-credit-history', ['employee' => $employee, 'year' => 2026]))
        ->assertOk()->assertJsonPath('total', 2)->assertJsonPath('rows.0.operation', 'deduct')
        ->assertJsonPath('rows.0.date', 'Nov 21, 2026 · 10:00:00 AM')->assertJsonPath('rows.0.schedule', 'CTO: Oct 01, 2026');
    $this->actingAs($hr)->get(route('employees.show', $employee))->assertOk()->assertSee('hr-coc-credit-history')->assertSee('hr-leave-credit-history');
    $this->actingAs($user)->getJson(route('my-cto.credit-history', ['year' => 2026, 'employee_id' => $other->id]))
        ->assertOk()->assertJsonPath('total', 2);
    $this->actingAs($user)->get(route('my-cto.index'))->assertOk()->assertSee('open-coc-credit-history');
    $this->actingAs($user)->getJson(route('employees.coc-credit-history', ['employee' => $other, 'year' => 2026]))->assertForbidden();
    $this->actingAs($user)->getJson(route('employees.leave-credit-history', ['employee' => $other, 'year' => 2026, 'leave_type_id' => $vl->id]))->assertForbidden();
});
