<?php

use App\Models\Employee;
use App\Models\User;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LocatorSlip;
use App\Models\TravelOrder;
use App\Models\CtoRequest;

test('HR always sees every application archive tab even when empty', function () {
    $hr = User::factory()->create(['must_change_password' => false]);
    Employee::factory()->for($hr)->create(['account_role' => 'hrstaff']);
    foreach (['leave', 'locator-slips', 'travel-orders', 'cto'] as $tab) {
        $this->actingAs($hr)->get(route('archive.index', ['tab' => $tab]))->assertOk()
            ->assertSee('Leave Applications')->assertSee('Locator Slips')
            ->assertSee('Travel Authorities')->assertSee('CTO/COC')
            ->assertSee('No archived records in this category.');
    }
});

test('application archive retains the record and displays it only in its matching tab', function (string $kind) {
    $hr = User::factory()->create(['must_change_password' => false]);
    Employee::factory()->for($hr)->create(['account_role' => 'hrstaff']);
    $employee = Employee::factory()->create(['account_role' => 'employee']);
    $attributes = ['employee_id' => $employee->id, 'status' => 'pending'];
    [$record, $route, $tab] = match ($kind) {
        'leave' => [LeaveRequest::create($attributes + [
            'leave_type_id' => LeaveType::create(['name' => 'Vacation Leave', 'is_active' => true])->id,
            'start_date' => '2026-11-18', 'end_date' => '2026-11-18', 'date_filed' => '2026-11-18', 'reason' => 'ARCHIVE-LEAVE-TEST',
        ]), 'leave-applications.destroy', 'leave'],
        'locator' => [LocatorSlip::create($attributes + ['date_covered' => '2026-11-18', 'destination' => 'Davao', 'type' => 'personal', 'purpose' => 'ARCHIVE-LOCATOR-TEST']), 'hr.locator-slips.destroy', 'locator-slips'],
        'ta' => [TravelOrder::create($attributes + ['ta_number' => 'ARCHIVE-TA-TEST', 'travel_type' => 'local', 'travel_date_start' => '2026-11-18', 'travel_date_end' => '2026-11-18', 'places_of_travel' => 'Davao', 'purpose' => 'Meeting']), 'hr.travel-orders.destroy', 'travel-orders'],
        'cto' => [CtoRequest::create($attributes + ['type' => 'use', 'date_start' => '2026-11-18', 'date_end' => '2026-11-18', 'hours' => 1, 'purpose' => 'ARCHIVE-CTO-TEST']), 'hr.cto.destroy', 'cto'],
    };
    $url = route($route, $record);
    $this->actingAs($employee->user)->delete($url)->assertForbidden();
    $this->actingAs($hr)->delete($url)->assertRedirect();
    $this->assertSoftDeleted($record);
    expect($record->newQuery()->find($record->id))->toBeNull();
    $this->get(route('archive.index', ['tab' => $tab]))->assertOk()
        ->assertSee($employee->firstname)->assertDontSee('No archived records in this category.');
    $this->get(route('archive.index', ['tab' => 'announcements']))->assertOk()
        ->assertSee('No archived records in this category.');
    $this->actingAs($employee->user)->get(route('archive.index', ['tab' => $tab]))->assertForbidden();
    $restoreUrl = route('archive.restore', ['category' => $tab, 'record' => $record->id]);
    $this->patch($restoreUrl)->assertForbidden();
    $before = $record->getAttributes();
    $this->actingAs($hr)->patch($restoreUrl)->assertRedirect(route('archive.index', ['tab' => $tab]));
    $restored = $record->fresh();
    expect($restored->deleted_at)->toBeNull()->and($restored->status)->toBe('pending');
    foreach ($before as $key => $value) {
        if (!in_array($key, ['deleted_at', 'updated_at'])) expect($restored->getRawOriginal($key))->toBe($value);
    }
    $this->assertDatabaseHas('activity_audits', ['action' => 'Restore Archived Record', 'user_id' => $hr->id]);
    $this->get(route('archive.index', ['tab' => $tab]))->assertOk()->assertSee('No archived records in this category.');
    $this->patch($restoreUrl)->assertNotFound();
})->with(['leave', 'locator', 'ta', 'cto']);

test('restore rejects unknown categories and active records', function () {
    $hr = User::factory()->create(['must_change_password' => false]);
    Employee::factory()->for($hr)->create(['account_role' => 'hrstaff']);
    $record = \App\Models\ActivityAudit::create(['user_id' => $hr->id, 'user_name' => 'HR', 'user_role' => 'hrstaff', 'action' => 'Page Visit', 'module' => 'PDS', 'description' => 'Viewed PDS', 'duration_seconds' => 120]);
    $this->actingAs($hr)->patch(route('archive.restore', ['category' => 'employees', 'record' => $record->id]))->assertNotFound();
    $this->patch(route('archive.restore', ['category' => 'logs', 'record' => $record->id]))->assertNotFound();
    $record->delete();
    $this->patch(route('archive.restore', ['category' => 'logs', 'record' => $record->id]))->assertRedirect();
    expect($record->fresh()->deleted_at)->toBeNull()->and($record->fresh()->duration_seconds)->toBe(120);
});
