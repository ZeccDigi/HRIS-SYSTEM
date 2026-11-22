<?php

use App\Models\Employee;
use App\Models\Saln;
use App\Models\User;

test('SALN records show filing date and filter by review status within the selected year', function () {
    $hr = User::factory()->create(['must_change_password' => false]);
    Employee::factory()->for($hr)->create(['account_role' => 'hrstaff']);
    $employee = Employee::factory()->create(['account_role' => 'employee', 'service_start_date' => '2025-01-01']);
    foreach (['pending' => 100, 'approved' => 200, 'returned' => 300] as $status => $amount) {
        $employee->salns()->create(['type_of_filing' => 'annual_filing', 'as_of_date' => '2026-12-31',
            'declarant_info' => [], 'filing_status' => 'not_applicable', 'review_status' => $status,
            'submitted_at' => '2026-11-18 10:00:00', 'total_assets' => $amount]);
    }
    foreach (['all' => 3, 'pending' => 1, 'approved' => 1, 'returned' => 1] as $status => $count) {
        $this->actingAs($hr)->get(route('employees.show', ['employee' => $employee, 'tab' => 'saln', 'saln_year' => 2026, 'saln_status' => $status]))
            ->assertOk()->assertSee('Date Filed')->assertSee('Nov 18, 2026')->assertSee('Archive SALN')
            ->assertViewHas('salnRecords', fn ($records) => $records->count() === $count && ($status === 'all' || $records->every(fn ($record) => $record->review_status === $status)));
    }
});

test('only HR and Admin archive SALN records and restoration preserves their approval and values', function () {
    $hr = User::factory()->create(['must_change_password' => false]);
    Employee::factory()->for($hr)->create(['account_role' => 'hrstaff']);
    $employee = Employee::factory()->create(['account_role' => 'employee']);
    $saln = $employee->salns()->create(['type_of_filing' => 'annual_filing', 'as_of_date' => now()->year.'-12-31',
        'declarant_info' => ['family_name' => 'Archived SALN'], 'filing_status' => 'not_applicable', 'review_status' => 'approved', 'net_worth' => 1234.56]);
    $this->actingAs($employee->user)->delete(route('salns.destroy', $saln))->assertForbidden();
    $this->actingAs($hr)->delete(route('salns.destroy', $saln))->assertRedirect();
    $this->assertSoftDeleted($saln);
    expect($employee->salns()->count())->toBe(0);
    $this->get(route('archive.index', ['tab' => 'saln']))->assertOk()->assertSee('SALN '.now()->year)->assertSee('Restore record');
    $this->patch(route('archive.restore', ['category' => 'saln', 'record' => $saln->id]))->assertRedirect();
    expect($saln->fresh()->deleted_at)->toBeNull()->and($saln->fresh()->review_status)->toBe('approved')
        ->and($saln->fresh()->declarant_info['family_name'])->toBe('Archived SALN')
        ->and((float) $saln->fresh()->net_worth)->toBe(1234.56)
        ->and($employee->salns()->count())->toBe(1);
    $this->assertDatabaseHas('activity_audits', ['action' => 'Archive SALN', 'user_id' => $hr->id]);
});

test('a saved SALN remains visible but cannot print until HR approves it', function () {
    $owner = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::factory()->for($owner)->create(['account_role' => 'employee', 'service_start_date' => '2025-01-01']);
    $employee->salns()->create(['type_of_filing' => 'annual_filing', 'as_of_date' => '2025-12-31',
        'declarant_info' => ['family_name' => 'SAVED DRAFT'], 'filing_status' => 'not_applicable', 'review_status' => 'pending']);

    $this->actingAs($owner)->get(route('salns.index', ['year' => 2025]))
        ->assertOk()->assertSee('SAVED DRAFT')->assertDontSee('Print PDF');
    $this->get(route('salns.print-year', ['year' => 2025]))->assertForbidden();
});

test('SALN print appears only after HR approval and uses approved values', function () {
    $owner = User::factory()->create(['must_change_password' => false]);
    $employee = Employee::factory()->for($owner)->create(['account_role' => 'employee', 'service_start_date' => '2025-01-01']);
    $record = $employee->salns()->create(['type_of_filing' => 'annual_filing', 'as_of_date' => '2025-12-31',
        'declarant_info' => ['family_name' => 'UNAPPROVED'], 'filing_status' => 'not_applicable', 'review_status' => 'approved',
        'approved_data' => ['declarant_info' => json_encode(['family_name' => 'APPROVED'])]]);
    $record->forceFill(['approved_data' => ['declarant_info' => json_encode(['family_name' => 'APPROVED'])]])->save();

    $this->mock(\App\Services\SalnPdfExporter::class, function ($mock) {
        $mock->shouldReceive('download')->once()->withArgs(fn ($saln, $inline) =>
            $inline === true && $saln->as_of_date->year === 2025 && ($saln->declarant_info['family_name'] ?? null) === 'APPROVED')
            ->andReturn(response('%PDF-test', 200, ['Content-Type' => 'application/pdf']));
    });
    $this->actingAs($owner)->get(route('salns.index', ['year' => 2025]))->assertOk()->assertSee('Print PDF');
    $this->get(route('salns.print-year', ['year' => 2025]))->assertOk()->assertHeader('Content-Type', 'application/pdf');
});
