<?php

use App\Models\CtoRequest;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LocatorSlip;
use App\Models\TravelOrder;
use App\Models\User;

test('owners can edit pending applications only before the first review', function (string $form) {
    $this->travelTo(\Carbon\Carbon::parse('2026-11-17 09:00:00', 'Asia/Manila'));
    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::factory()->for($user)->create(['account_role' => 'employee', 'employment_status' => 'Regular']);
    $date = '2026-11-17';
    $sick = LeaveType::create(['name' => 'Sick Leave', 'days_per_year' => 15, 'is_active' => true]);

    [$model, $fields, $firstReview] = match ($form) {
        'leaves' => [LeaveRequest::class, [
            'leave_type_id' => $sick->id, 'start_date' => $date, 'end_date' => $date,
            'is_paid' => 0, 'commutation_requested' => 0, 'leave_detail_type' => 'out_patient',
            'leave_detail' => 'Updated leave detail',
        ], ['hrstaff_status' => 'approved']],
        'locator-slips' => [LocatorSlip::class, [
            'date_covered' => $date, 'destination' => 'Regional Office', 'type' => 'Official Business',
            'purpose' => 'Updated application',
        ], ['status' => 'approved']],
        'travel-orders' => [TravelOrder::class, [
            'travel_type' => 'local', 'travel_date_start' => $date, 'travel_date_end' => $date,
            'places_of_travel' => 'Davao', 'purpose' => 'Updated application',
        ], ['recordofficer_status' => 'approved']],
        'my-cto' => [CtoRequest::class, [
            'type' => 'earn', 'date_start' => $date, 'hours' => 2, 'purpose' => 'Updated application',
        ], ['hrstaff_status' => 'approved']],
    };
    $initial = match ($form) {
        'leaves' => ['date_filed' => now()],
        'travel-orders' => ['ta_number' => 'TA-2026-11-17-001'],
        'my-cto' => ['date_end' => $date, 'attachment_path' => 'cto-attachments/approved-overtime.pdf'],
        default => [],
    };
    $request = $model::create([...$fields, ...$initial, 'employee_id' => $employee->id, 'status' => 'pending', $form === 'leaves' ? 'reason' : 'purpose' => 'Original application']);
    $edit = route("$form.edit", $request);
    $update = route("$form.update", $request);

    $other = User::factory()->create(['must_change_password' => false]);
    Employee::factory()->for($other)->create(['account_role' => 'employee']);
    $this->actingAs($other)->get($edit)->assertForbidden();
    $this->put($update, $fields)->assertForbidden();

    $this->actingAs($user)->get($edit)->assertOk();
    $this->put($update, $fields)->assertSessionHasNoErrors()->assertRedirect();
    expect($request->fresh()->{$form === 'leaves' ? 'reason' : 'purpose'})
        ->toBe($form === 'leaves' ? 'Updated leave detail' : ($form === 'my-cto' ? 'Original application' : 'Updated application'));

    $request->forceFill($firstReview)->save();
    $this->get($edit)->assertForbidden();
    $this->put($update, $fields)->assertForbidden();
})->with(['leaves', 'locator-slips', 'travel-orders', 'my-cto']);

