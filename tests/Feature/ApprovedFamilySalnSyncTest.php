<?php

use App\Models\Employee;
use App\Services\PdsSnapshotService;
use App\Services\SalnPdsDefaultsService;

it('publishes approved family data to SALN without reopening SALN approval or altering finances', function () {
    $employee = Employee::factory()->create();
    $employee->pdsFamily()->create(['spouse_surname' => 'Old', 'spouse_firstname' => 'Juan']);
    app(PdsSnapshotService::class)->ensureApprovedBaseline($employee);
    $saln = $employee->salns()->create(['declarant_info' => [], 'as_of_date' => now()->endOfYear()->toDateString(), 'type_of_filing' => 'annual_filing', 'filing_status' => 'joint', 'review_status' => 'approved', 'spouse_info' => ['family_name' => 'Old', 'id_no' => 'retained'], 'total_assets' => 100, 'total_liabilities' => 20, 'net_worth' => 80]);
    $saln->forceFill(['approved_data' => ['spouse_info' => json_encode(['family_name' => 'Old', 'id_no' => 'retained']), 'total_assets' => 100, 'net_worth' => 80]])->save();
    $employee->pdsFamily()->update(['spouse_surname' => 'New approved surname', 'spouse_occupation' => 'Officer']);
    $employee->pdsSectionReviews()->create(['section_name' => 'Family Background', 'status' => 'pending']);
    app(SalnPdsDefaultsService::class)->syncApprovedFamily($employee, (int) now()->year);
    expect($saln->fresh()->spouse_info['family_name'])->toBe('Old');
    $employee->pdsSectionReviews()->where('section_name', 'Family Background')->update(['status' => 'approved']);
    app(PdsSnapshotService::class)->publishSections($employee, ['Family Background']);
    $saln->refresh();
    expect($saln->spouse_info['family_name'])->toBe('New approved surname')->and($saln->spouse_info['position'])->toBe('Officer')
        ->and($saln->spouse_info['id_no'])->toBe('retained')->and($saln->review_status)->toBe('approved')
        ->and((float) $saln->total_assets)->toBe(100.0)->and((float) $saln->net_worth)->toBe(80.0)
        ->and(json_decode($saln->approved_data['spouse_info'], true)['family_name'])->toBe('New approved surname');
});
