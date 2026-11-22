<?php

use App\Models\Employee;
use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Support\Facades\Notification;

function tarRequirementActors(): array
{
    Notification::fake();
    $actors = [];
    foreach (['employee', 'recordofficer', 'regionaldirector'] as $role) {
        $user = User::factory()->create(['must_change_password' => false]);
        Employee::create(['user_id' => $user->id, 'firstname' => 'Test', 'lastname' => $role, 'account_role' => $role, 'division' => 'Migrant Workers Processing Division', 'employment_status' => 'Regular']);
        $actors[$role] = $user->fresh();
    }
    return $actors;
}

function pendingTarRequirementOrder(User $owner): TravelOrder
{
    return TravelOrder::create(['employee_id' => $owner->employee->id, 'ta_number' => 'TA-REQUIREMENT-TEST', 'travel_type' => 'local', 'travel_date_start' => '2026-12-03', 'travel_date_end' => '2026-12-05', 'places_of_travel' => 'Davao City', 'purpose' => 'Meeting', 'status' => 'pending', 'recordofficer_status' => 'pending', 'rd_status' => 'pending', 'tar_deadline' => '2026-12-10']);
}

test('Record Officer decides TAR requirement and deadline stays hidden until final approval', function (bool $required) {
    $actors = tarRequirementActors();
    $order = pendingTarRequirementOrder($actors['employee']);
    $this->actingAs($actors['employee'])->get(route('travel-orders.index'))->assertOk()->assertDontSee('Dec 10, 2026');
    expect($order->visible_tar_deadline)->toBeNull();
    $this->actingAs($actors['recordofficer'])->get(route('travel-orders.show', $order))->assertOk()->assertSee('TAR Submission Required')->assertSee('name="is_tar_required" value="0"', false);
    $this->put(route('travel-orders.update-status', $order), ['status' => 'approved', 'is_tar_required' => $required ? 1 : 0, 'remarks' => 'Internal approval note'])->assertSessionHasNoErrors()->assertRedirect();
    expect($order->fresh()->is_tar_required)->toBe($required)
        ->and($order->fresh()->status)->toBe('pending')
        ->and($order->fresh()->tar_deadline)->toBeNull()
        ->and($order->fresh()->visible_tar_deadline)->toBeNull();
    $this->actingAs($actors['employee'])->get(route('travel-orders.index'))->assertOk()->assertDontSee('Dec 10, 2026');
    $this->actingAs($actors['regionaldirector'])->put(route('travel-orders.update-status', $order), ['status' => 'approved', 'is_tar_required' => $required ? 0 : 1, 'remarks' => 'Final approval note'])->assertSessionHasNoErrors()->assertRedirect();
    expect($order->fresh()->is_tar_required)->toBe($required)
        ->and($order->fresh()->is_fully_approved)->toBeTrue()
        ->and($order->fresh()->visible_tar_deadline?->toDateString())->toBe($required ? '2026-12-10' : null);
    $response = $this->actingAs($actors['employee'])->get(route('travel-orders.index'))->assertOk();
    if ($required) $response->assertSee('Dec 10, 2026'); else $response->assertDontSee('Dec 10, 2026');
    $this->get(route('travel-orders.show', $order))->assertOk()->assertSee('Approver Remarks')->assertSee('Internal approval note')->assertSee('Final approval note');
    if (!$required) {
        $this->post(route('travel-orders.submit-tar', $order), [])->assertSessionHas('error');
        expect($order->fresh()->tar_submitted_at)->toBeNull();
    }
})->with(['required' => true, 'not required' => false]);

test('rejection displays its reason while hiding prior approval remarks and deadline', function () {
    $actors = tarRequirementActors();
    $order = pendingTarRequirementOrder($actors['employee']);
    $this->actingAs($actors['recordofficer'])->put(route('travel-orders.update-status', $order), ['status' => 'approved', 'is_tar_required' => 1, 'remarks' => 'RO approved for processing'])->assertSessionHasNoErrors();
    $this->actingAs($actors['regionaldirector'])->put(route('travel-orders.update-status', $order), ['status' => 'rejected', 'remarks' => 'Missing travel authority documents'])->assertSessionHasNoErrors();
    $this->actingAs($actors['employee'])->get(route('travel-orders.show', $order))->assertOk()->assertSee('Approver Remarks')->assertSee('Missing travel authority documents')->assertSee('RO approved for processing');
    expect($order->fresh()->visible_tar_deadline)->toBeNull();
});
