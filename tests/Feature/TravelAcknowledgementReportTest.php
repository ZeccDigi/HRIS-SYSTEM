<?php

use App\Models\Employee;
use App\Models\TravelOrder;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

function travelReportEmployee(string $firstName = 'Anna', string $role = 'employee'): array
{
    $user = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::create([
        'firstname' => $firstName,
        'lastname' => 'Garcia',
        'division' => 'Migrant Workers Processing Division',
        'position' => $role === 'chief' ? 'Division Chief' : 'Regional Aide',
        'account_role' => $role,
        'employment_status' => 'Regular',
        'user_id' => $user->id,
    ]);

    return [$user->fresh(), $employee];
}

test('employee submits structured post activity report and views the official form', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-11-17 09:00:00', 'Asia/Manila'));
    Notification::fake();
    Storage::fake('public');
    [$user, $employee] = travelReportEmployee();
    travelReportEmployee('Division Head', 'chief');

    $travelOrder = TravelOrder::create([
        'employee_id' => $employee->id,
        'ta_number' => 'TA-2026-11-03-001',
        'travel_type' => 'local',
        'travel_date_start' => '2026-11-04',
        'travel_date_end' => '2026-11-05',
        'places_of_travel' => 'Davao City',
        'purpose' => 'Regional coordination meeting',
        'status' => 'approved',
        'recordofficer_status' => 'approved',
        'rd_status' => 'approved',
        'tar_status' => 'pending',
    ]);

    $this->actingAs($user)->get(route('travel-orders.index'))
        ->assertOk()
        ->assertSee('Overdue')
        ->assertSee('Upload overdue TAR');

    $response = $this->actingAs($user)->post(route('travel-orders.submit-tar', $travelOrder), [
        'tar_activity' => 'Tampered activity value',
        'tar_activity_date' => '2026-12-31',
        'tar_venue' => 'Tampered venue value',
        'tar_participants_present' => 'Twenty regional representatives',
        'tar_dmw_participants' => 'Tampered participant value',
        'tar_objectives' => 'Align regional implementation procedures.',
        'tar_highlights' => 'The group reviewed the implementation timeline.',
        'tar_results' => 'A common implementation plan was adopted.',
        'tar_recommendations' => 'Conduct a follow-up review next quarter.',
        'tar_commitments' => 'Submit the consolidated action matrix.',
        'tar_attachments' => ['photo_documentation', 'program_flow', 'others'],
        'tar_other_attachment' => 'Signed attendance sheet',
        'tar_remarks' => 'Completed on schedule.',
        'tar_attachment' => UploadedFile::fake()->create('supporting-document.pdf', 120, 'application/pdf'),
    ]);

    $response->assertRedirect(route('travel-orders.index'));

    $travelOrder->refresh();
    expect($travelOrder->tar_status)->toBe('submitted')
        ->and($travelOrder->tar_review_status)->toBe('pending_hr')
        ->and($travelOrder->tar_activity)->toBe('Regional coordination meeting')
        ->and($travelOrder->tar_activity_date?->toDateString())->toBe('2026-11-05')
        ->and($travelOrder->tar_venue)->toBe('Davao City')
        ->and($travelOrder->tar_dmw_participants)->toBe('Anna Garcia')
        ->and($travelOrder->tar_attachments)->toBe(['photo_documentation', 'program_flow', 'others'])
        ->and($travelOrder->tar_other_attachment)->toBe('Signed attendance sheet');

    $this->actingAs($user)
        ->get(route('travel-orders.ar.show', $travelOrder))
        ->assertOk()
        ->assertSee('TRAVEL AUTHORITY REPORT (TAR)')
        ->assertSee('Regional Coordination Meeting')
        ->assertSee('Align regional implementation procedures.')
        ->assertSee('Signed attendance sheet')
        ->assertSee('Supporting Document')
        ->assertSee(route('travel-orders.ar.file', $travelOrder), false);
});

test('submitted TAR cannot be overwritten unless returned for correction', function () {
    Notification::fake();
    [$user, $employee] = travelReportEmployee('Protected Report');

    $travelOrder = TravelOrder::create([
        'employee_id' => $employee->id,
        'ta_number' => 'TA-2026-11-05-101',
        'travel_type' => 'local',
        'travel_date_start' => '2026-11-05',
        'travel_date_end' => '2026-11-06',
        'places_of_travel' => 'Davao City',
        'purpose' => 'Protected activity',
        'status' => 'approved',
        'tar_status' => 'submitted',
        'tar_submitted_at' => now(),
        'tar_review_status' => 'pending_hr',
        'tar_objectives' => 'Original objective',
    ]);

    $this->actingAs($user)->post(route('travel-orders.submit-tar', $travelOrder), [
        'tar_participants_present' => 'Participants',
        'tar_objectives' => 'Overwritten objective',
        'tar_highlights' => 'Highlights',
        'tar_results' => 'Results',
        'tar_recommendations' => 'Recommendations',
        'tar_commitments' => 'Commitments',
    ])->assertSessionHas('error');

    expect($travelOrder->refresh()->tar_objectives)->toBe('Original objective');
});

test('returned TAR can be corrected and resubmitted', function () {
    Notification::fake();
    [$user, $employee] = travelReportEmployee('Returned Report');

    $travelOrder = TravelOrder::create([
        'employee_id' => $employee->id,
        'ta_number' => 'TA-2026-11-05-102',
        'travel_type' => 'local',
        'travel_date_start' => '2026-11-05',
        'travel_date_end' => '2026-11-06',
        'places_of_travel' => 'Davao City',
        'purpose' => 'Corrected activity',
        'status' => 'approved',
        'tar_status' => 'submitted',
        'tar_submitted_at' => now()->subDay(),
        'tar_review_status' => 'returned',
        'tar_hr_status' => 'returned',
    ]);

    $this->actingAs($user)->post(route('travel-orders.submit-tar', $travelOrder), [
        'tar_participants_present' => 'Participants',
        'tar_objectives' => 'Corrected objective',
        'tar_highlights' => 'Corrected highlights',
        'tar_results' => 'Corrected results',
        'tar_recommendations' => 'Corrected recommendations',
        'tar_commitments' => 'Corrected commitments',
    ])->assertRedirect(route('travel-orders.index'));

    $travelOrder->refresh();
    expect($travelOrder->tar_review_status)->toBe('pending_hr')
        ->and($travelOrder->tar_hr_status)->toBe('pending')
        ->and($travelOrder->tar_objectives)->toBe('Corrected objective');
});

test('regional director cannot approve TA before record officer review', function () {
    Notification::fake();
    [$employeeUser, $employee] = travelReportEmployee('TA Owner');
    [$rdUser] = travelReportEmployee('Early Director', 'regionaldirector');

    $travelOrder = TravelOrder::create([
        'employee_id' => $employee->id,
        'ta_number' => 'TA-2026-11-05-103',
        'travel_type' => 'local',
        'travel_date_start' => '2026-11-05',
        'travel_date_end' => '2026-11-06',
        'places_of_travel' => 'Davao City',
        'purpose' => 'Approval order test',
        'status' => 'pending',
        'recordofficer_status' => 'pending',
        'rd_status' => 'pending',
    ]);

    $this->actingAs($rdUser)->put(route('travel-orders.update-status', $travelOrder), [
        'status' => 'approved',
    ])->assertSessionHas('error');

    $travelOrder->refresh();
    expect($travelOrder->status)->toBe('pending')
        ->and($travelOrder->rd_status)->toBe('pending')
        ->and($travelOrder->approved_by_regionaldirector)->toBeNull();
});

test('post activity report follows hr division chief and regional director approval', function () {
    Notification::fake();
    [$employeeUser, $employee] = travelReportEmployee('Report Owner');
    [$hrUser, $hrEmployee] = travelReportEmployee('HR Reviewer', 'hrstaff');
    [$chiefUser, $chiefEmployee] = travelReportEmployee('Division Chief', 'chief');
    [$rdUser, $rdEmployee] = travelReportEmployee('Regional Director', 'regionaldirector');

    $travelOrder = TravelOrder::create([
        'employee_id' => $employee->id,
        'ta_number' => 'TA-2026-11-04-099',
        'travel_type' => 'local',
        'travel_date_start' => '2026-11-01',
        'travel_date_end' => '2026-11-02',
        'places_of_travel' => 'Davao City',
        'purpose' => 'Coordination meeting',
        'status' => 'approved',
        'tar_status' => 'submitted',
        'tar_submitted_at' => now(),
        'tar_review_status' => 'pending_hr',
    ]);

    $this->actingAs($hrUser)->patch(route('travel-orders.tar.review', $travelOrder), [
        'action' => 'approve',
        'remarks' => 'Documents are complete.',
    ])->assertRedirect(route('travel-orders.ar.show', $travelOrder));

    $travelOrder->refresh();
    expect($travelOrder->tar_hr_status)->toBe('approved')
        ->and($travelOrder->tar_reviewed_by_hr)->toBe($hrEmployee->id)
        ->and($travelOrder->tar_review_status)->toBe('pending_chief');

    $this->actingAs($chiefUser)->patch(route('travel-orders.tar.review', $travelOrder), [
        'action' => 'approve',
    ])->assertRedirect(route('travel-orders.ar.show', $travelOrder));

    $travelOrder->refresh();
    expect($travelOrder->tar_chief_status)->toBe('approved')
        ->and($travelOrder->tar_reviewed_by_chief)->toBe($chiefEmployee->id)
        ->and($travelOrder->tar_review_status)->toBe('pending_rd');

    $this->actingAs($rdUser)->patch(route('travel-orders.tar.review', $travelOrder), [
        'action' => 'approve',
        'remarks' => 'Approved.',
    ])->assertRedirect(route('travel-orders.ar.show', $travelOrder));

    $travelOrder->refresh();
    expect($travelOrder->tar_rd_status)->toBe('approved')
        ->and($travelOrder->tar_reviewed_by_rd)->toBe($rdEmployee->id)
        ->and($travelOrder->tar_review_status)->toBe('approved')
        ->and($travelOrder->tar_approved_at)->not->toBeNull();
});

test('division chief cannot review a report from another division', function () {
    Notification::fake();
    [$employeeUser, $employee] = travelReportEmployee('Report Owner');
    [$chiefUser, $chief] = travelReportEmployee('Other Chief', 'chief');
    $chief->update(['division' => 'Finance and Administrative Division']);

    $travelOrder = TravelOrder::create([
        'employee_id' => $employee->id,
        'ta_number' => 'TA-2026-11-04-100',
        'travel_type' => 'local',
        'travel_date_start' => '2026-11-01',
        'travel_date_end' => '2026-11-02',
        'places_of_travel' => 'Davao City',
        'purpose' => 'Coordination meeting',
        'status' => 'approved',
        'tar_status' => 'submitted',
        'tar_submitted_at' => now(),
        'tar_review_status' => 'pending_chief',
        'tar_hr_status' => 'approved',
    ]);

    $this->actingAs($chiefUser)->patch(route('travel-orders.tar.review', $travelOrder), [
        'action' => 'approve',
    ])->assertForbidden();
});

test('travel authority created in the modal redirects the parent page', function () {
    Notification::fake();
    [$user] = travelReportEmployee('Modal');
    $travelStart = now()->addWeek()->startOfWeek();
    $travelEnd = $travelStart->copy()->addDay();

    $response = $this->actingAs($user)->post(route('travel-orders.store', ['modal' => 1]), [
        'travel_type' => 'local',
        'travel_date_start' => $travelStart->toDateString(),
        'travel_date_end' => $travelEnd->toDateString(),
        'places_of_travel' => 'Cebu City',
        'purpose' => 'Official coordination meeting',
    ]);

    $response->assertOk()
        ->assertSee('window.parent.location.replace', false)
        ->assertSee('travel-orders', false);

    $this->assertDatabaseHas('travel_orders', [
        'places_of_travel' => 'Cebu City',
        'purpose' => 'Official coordination meeting',
    ]);
});
