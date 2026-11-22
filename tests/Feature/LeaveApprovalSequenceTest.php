<?php

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LeaveCreditTransaction;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

function leaveSequenceFixture(): array
{
    Notification::fake();
    $users = [];
    foreach (['employee', 'hrstaff', 'chief', 'regionaldirector'] as $role) {
        $user = User::factory()->create(['must_change_password' => false]);
        Employee::create(['user_id' => $user->id, 'firstname' => 'Sequence', 'lastname' => $role, 'account_role' => $role, 'division' => 'Test Division', 'service_start_date' => '2025-01-01']);
        $users[$role] = $user->fresh();
    }
    $type = LeaveType::create(['name' => 'Vacation Leave', 'days_per_year' => 15, 'is_active' => true]);
    $leave = LeaveRequest::create(['employee_id' => $users['employee']->employee->id, 'leave_type_id' => $type->id,
        'date_filed' => now(), 'start_date' => now()->toDateString(), 'end_date' => now()->toDateString(),
        'status' => 'pending', 'is_paid' => true, 'reason' => 'Sequence test']);
    return [$users, $leave];
}

test('ordinary leave requires HR then Chief then RD and completed decisions cannot change', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-11-23 09:00:00', 'Asia/Manila'));
    [$users, $leave] = leaveSequenceFixture();
    $url = route('leave-applications.update', $leave);
    foreach (['chief', 'regionaldirector'] as $role) {
        foreach (['approved', 'rejected'] as $status) $this->actingAs($users[$role])->put($url, ['status' => $status])->assertForbidden();
    }
    expect($leave->fresh()->status)->toBe('pending');
    $this->actingAs($users['hrstaff'])->put($url, ['status' => 'rejected'])->assertRedirect();
    expect($leave->fresh()->hrstaff_status)->not->toBe('approved');
    $this->put($url, ['status' => 'approved'])->assertRedirect()->assertSessionHasNoErrors();
    $this->put($url, ['status' => 'approved'])->assertForbidden();
    $this->actingAs($users['regionaldirector'])->put($url, ['status' => 'approved'])->assertForbidden();
    $this->actingAs($users['chief'])->put($url, ['status' => 'approved'])->assertRedirect()->assertSessionHasNoErrors();
    $this->put($url, ['status' => 'rejected'])->assertForbidden();
    $this->actingAs($users['regionaldirector'])->put($url, ['status' => 'approved'])->assertRedirect()->assertSessionHasNoErrors();
    expect($leave->fresh()->status)->toBe('approved')
        ->and(LeaveCreditTransaction::where('leave_request_id', $leave->id)->where('type', 'usage')->count())->toBe(1);
    foreach (['hrstaff', 'chief', 'regionaldirector'] as $role) {
        foreach (['approved', 'rejected'] as $status) $this->actingAs($users[$role])->put($url, ['status' => $status])->assertForbidden();
    }
    expect($leave->fresh()->status)->toBe('approved')
        ->and(LeaveCreditTransaction::where('leave_request_id', $leave->id)->where('type', 'usage')->count())->toBe(1);
});

test('Chief and RD rejection are allowed only at their stage and remain final', function () {
    [$users, $leave] = leaveSequenceFixture();
    $url = route('leave-applications.update', $leave);
    $this->actingAs($users['hrstaff'])->put($url, ['status' => 'approved'])->assertRedirect();
    $this->actingAs($users['chief'])->put($url, ['status' => 'rejected', 'remarks' => 'Cannot recommend'])->assertRedirect();
    $this->actingAs($users['regionaldirector'])->put($url, ['status' => 'approved'])->assertForbidden();
    $this->actingAs($users['chief'])->put($url, ['status' => 'approved'])->assertForbidden();
    expect($leave->fresh()->status)->toBe('rejected');
    $second = $leave->replicate(['hrstaff_status', 'chief_status', 'rd_status', 'approved_by_hrstaff', 'approved_by_chief', 'approved_by_regionaldirector']);
    $second->status = 'pending';
    $second->save();
    $url = route('leave-applications.update', $second);
    $this->actingAs($users['hrstaff'])->put($url, ['status' => 'approved'])->assertRedirect();
    $this->actingAs($users['chief'])->put($url, ['status' => 'approved'])->assertRedirect();
    $this->actingAs($users['regionaldirector'])->put($url, ['status' => 'rejected', 'remarks' => 'Not approved'])->assertRedirect();
    $this->put($url, ['status' => 'approved'])->assertForbidden();
    expect($second->fresh()->status)->toBe('rejected');
});
