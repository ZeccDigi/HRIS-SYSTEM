<?php

use App\Models\Employee;
use App\Models\PdsSnapshot;
use App\Models\User;

test('PDS records show their filed date and filter by review status', function () {
    $hr = User::factory()->create(['must_change_password' => false]);
    Employee::factory()->for($hr)->create(['account_role' => 'hrstaff']);
    $employee = Employee::factory()->create(['account_role' => 'employee', 'service_start_date' => '2025-01-01']);
    $employee->pdsSnapshots()->create([
        'year' => 2025,
        'data' => ['employee' => [], 'relations' => []],
        'draft_data' => ['employee' => [], 'relations' => []],
        'reviews' => [['section_name' => 'Personal Information', 'status' => 'pending', 'changed_fields' => ['surname']]],
        'submitted_at' => '2026-11-18 09:30:00',
    ]);

    $this->actingAs($hr)
        ->get(route('employees.show', ['employee' => $employee, 'tab' => 'pds', 'pds_year' => 2025, 'pds_status' => 'pending']))
        ->assertOk()
        ->assertSee('Date Filed')
        ->assertSee('Nov 18, 2026')
        ->assertSee('Archive PDS');

    $this->actingAs($hr)
        ->get(route('employees.show', ['employee' => $employee, 'tab' => 'pds', 'pds_year' => 2025, 'pds_status' => 'approved']))
        ->assertOk()
        ->assertSee('No PDS records match the selected year and status.');
});

test('HR can archive and restore a yearly PDS without changing its saved values or reviews', function () {
    $hr = User::factory()->create(['must_change_password' => false]);
    Employee::factory()->for($hr)->create(['account_role' => 'hrstaff']);
    $employee = Employee::factory()->create(['account_role' => 'employee']);
    $snapshot = $employee->pdsSnapshots()->create([
        'year' => now()->year,
        'data' => ['employee' => ['firstname' => 'Official'], 'relations' => []],
        'draft_data' => ['employee' => ['firstname' => 'Draft'], 'relations' => []],
        'reviews' => [['section_name' => 'Personal Information', 'status' => 'pending', 'changed_fields' => ['firstname']]],
        'submitted_at' => now(),
    ]);
    $before = $snapshot->only(['data', 'draft_data', 'reviews', 'submitted_at']);

    $this->actingAs($employee->user)
        ->delete(route('employees.pds.archive', ['employee' => $employee, 'year' => now()->year]))
        ->assertForbidden();

    $this->actingAs($hr)
        ->delete(route('employees.pds.archive', ['employee' => $employee, 'year' => now()->year]))
        ->assertRedirect();

    $this->assertSoftDeleted('pds_snapshots', ['id' => $snapshot->id]);
    $this->get(route('archive.index', ['tab' => 'pds']))
        ->assertOk()
        ->assertSee('PDS '.now()->year)
        ->assertSee('Restore record');

    $this->patch(route('archive.restore', ['category' => 'pds', 'record' => $snapshot->id]))
        ->assertRedirect();

    $restored = PdsSnapshot::findOrFail($snapshot->id);
    expect($restored->deleted_at)->toBeNull()
        ->and($restored->only(['data', 'draft_data', 'reviews', 'submitted_at']))->toEqual($before);
    $this->assertDatabaseHas('activity_audits', ['action' => 'Archive PDS', 'user_id' => $hr->id]);
});
