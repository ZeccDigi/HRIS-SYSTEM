<?php
use App\Models\User;
use App\Models\Employee;
use App\Models\LocatorSlip;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;

it('requires a nonblank reason for rejection but permits approval without remarks', function () {
    Notification::fake(); Mail::fake();
    $chief = User::factory()->create(['must_change_password' => false]);
    Employee::factory()->for($chief)->create(['account_role' => 'chief', 'division' => 'Test Division']);
    $owner = User::factory()->create();
    $employee = Employee::factory()->for($owner)->create(['division' => 'Test Division']);
    $slip = LocatorSlip::create(['employee_id' => $employee->id, 'date_covered' => '2026-11-24', 'destination' => 'Test office', 'purpose' => 'Test purpose', 'type' => 'Official Business', 'status' => 'pending']);
    $this->actingAs($chief);
    foreach ([null, '', '   '] as $remarks) {
        $this->patchJson(route('locator-slips.reject', $slip), ['remarks' => $remarks])->assertUnprocessable()->assertJsonValidationErrors('remarks');
        expect($slip->fresh()->status)->toBe('pending');
        expect($slip->fresh()->chief_remarks)->toBeNull();
    }
    Notification::assertNothingSent();
    $this->patch(route('locator-slips.reject', $slip), ['remarks' => '  Please correct the destination.  '])->assertSessionHasNoErrors()->assertRedirect();
    expect($slip->fresh()->status)->toBe('rejected');
    expect($slip->fresh()->chief_remarks)->toBe('Please correct the destination.');
    $second = LocatorSlip::create(['employee_id' => $employee->id, 'date_covered' => '2026-11-24', 'destination' => 'Test office', 'purpose' => 'Test purpose', 'type' => 'Official Business', 'status' => 'pending']);
    $this->patch(route('locator-slips.approve', $second), [])->assertSessionHasNoErrors()->assertRedirect();
    expect($second->fresh()->status)->toBe('approved');
    expect($second->fresh()->chief_remarks)->toBeNull();
});
