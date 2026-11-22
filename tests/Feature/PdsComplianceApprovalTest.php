<?php

use App\Models\Employee;
use App\Http\Controllers\ReportController;
use App\Services\PdsCompletionService;

it('counts PDS and SALN compliance only after the required HR approvals', function () {
    $employee = Employee::factory()->create();
    $employee->pdsPersonal()->create(['surname' => 'Test']);
    $saln = $employee->salns()->create(['as_of_date' => now()->endOfYear()->toDateString(), 'type_of_filing' => 'annual_filing', 'filing_status' => 'not_applicable', 'declarant_info' => [], 'review_status' => 'pending']);
    $method = new ReflectionMethod(ReportController::class, 'employeeComplianceReport');
    $report = fn () => $method->invoke(app(ReportController::class), Employee::whereKey($employee->id)->get(), (int) now()->year);
    $initial = $report();
    expect($initial['rows'][0]['cells'][3])->toBe('Incomplete')->and($initial['rows'][0]['cells'][5])->toBe('Incomplete');
    foreach (PdsCompletionService::SECTIONS as $names) $employee->pdsSectionReviews()->create(['section_name' => $names[0], 'status' => 'approved']);
    $approved = $report();
    expect($approved['rows'][0]['cells'][3])->toBe('Complete')->and($approved['rows'][0]['cells'][5])->toBe('Incomplete');
    $saln->update(['review_status' => 'approved']);
    expect($report()['rows'][0]['cells'][5])->toBe('Complete');
    $employee->pdsSectionReviews()->where('section_name', 'Family Background')->update(['status' => 'pending']);
    expect($report()['rows'][0]['cells'][3])->toBe('Incomplete');
});
