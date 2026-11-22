<?php

use App\Models\Employee;
use App\Models\User;
use App\Services\PdsSnapshotService;
use Illuminate\Support\Facades\Notification;

function emailSyncEmployee(): Employee
{
    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::factory()->for($user)->create(['account_role' => 'employee', 'notification_email' => 'old@example.test', 'pds_record_year' => now()->year]);
    $employee->pdsPersonal()->create(['surname' => 'Test', 'firstname' => 'Anna', 'email_address' => 'old@example.test']);
    app(PdsSnapshotService::class)->ensureApprovedBaseline($employee);
    return $employee;
}

it('syncs account email to the PDS draft without changing approved print data or login', function () {
    $employee = emailSyncEmployee();
    $login = $employee->user->email;
    $this->actingAs($employee->user)->patch(route('profile.update'), ['name' => 'Anna Test', 'notification_email' => ' NEW@example.test '])->assertSessionHasNoErrors();
    $employee->refresh();
    expect($employee->notification_email)->toBe('new@example.test')
        ->and($employee->pdsPersonal->email_address)->toBe('new@example.test')
        ->and($employee->user->email)->toBe($login)
        ->and($employee->pds_approved_data['relations']['pdsPersonal']['email_address'])->toBe('old@example.test');
    expect($employee->pdsSectionReviews()->where('section_name', 'Personal Information')->first()->changed_fields)->toContain('email_address');
    expect($employee->pdsSnapshots()->where('year', now()->year)->first()->draft_data['relations']['pdsPersonal']['email_address'])->toBe('new@example.test');
    $this->get(route('personal-information.show'))->assertOk()->assertSee('new@example.test');
});

it('syncs a current PDS email to account recovery and notifications immediately', function () {
    Notification::fake();
    $employee = emailSyncEmployee();
    $this->actingAs($employee->user)->put(route('pds.update'), ['personal' => ['surname' => 'Test', 'firstname' => 'Anna', 'email_address' => ' PDS@example.test ']])->assertSessionHasNoErrors();
    $employee->refresh();
    expect($employee->notification_email)->toBe('pds@example.test')
        ->and($employee->user->routeNotificationForMail())->toBe('pds@example.test')
        ->and($employee->pdsPersonal->email_address)->toBe('pds@example.test')
        ->and($employee->pds_approved_data['relations']['pdsPersonal']['email_address'])->toBe('old@example.test');
});

it('rejects another employees recovery email without partially updating the PDS', function () {
    $employee = emailSyncEmployee();
    Employee::factory()->create(['notification_email' => 'taken@example.test']);
    $this->actingAs($employee->user)->put(route('pds.update'), ['personal' => ['email_address' => 'taken@example.test']])->assertSessionHasErrors('personal.email_address');
    expect($employee->fresh()->notification_email)->toBe('old@example.test')
        ->and($employee->pdsPersonal()->first()->email_address)->toBe('old@example.test');
});

it('does not change the recovery email when an older PDS year is edited', function () {
    Notification::fake();
    $employee = emailSyncEmployee();
    $employee->update(['service_start_date' => now()->subYears(2)->startOfYear()->toDateString()]);
    $year = (int) now()->year - 1;
    app(PdsSnapshotService::class)->capture($employee, $year);
    $this->actingAs($employee->user)->put(route('pds.update'), ['year' => $year, 'personal' => ['email_address' => 'historical@example.test']])->assertSessionHasNoErrors();
    expect($employee->fresh()->notification_email)->toBe('old@example.test')
        ->and($employee->pdsPersonal()->first()->email_address)->toBe('old@example.test');
});

it('keeps older live PDS data intact when account email changes', function () {
    $employee = emailSyncEmployee();
    $employee->forceFill(['pds_record_year' => (int) now()->year - 1])->save();
    $this->actingAs($employee->user)->patch(route('profile.update'), ['name' => 'Anna Test', 'notification_email' => 'current@example.test'])->assertSessionHasNoErrors();
    expect($employee->fresh()->notification_email)->toBe('current@example.test')
        ->and($employee->pdsPersonal()->first()->email_address)->toBe('old@example.test')
        ->and($employee->pdsSnapshots()->where('year', now()->year)->first()->draft_data['relations']['pdsPersonal']['email_address'])->toBe('current@example.test');
});
