<?php

use App\Http\Controllers\ReportController;
use App\Models\Employee;
use App\Models\PdsPersonalInformation;
use App\Models\DtrRecord;
use App\Models\CtoRequest;
use App\Models\LeaveType;
use App\Models\LeaveRequest;
use App\Models\TravelOrder;
use Illuminate\Http\Request;

test('reports default to year to date and expose existing attendance leave overtime and PDS birthdays', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-11-21 09:00:00', 'Asia/Manila'));
    $employee = Employee::create(['firstname' => 'Report', 'lastname' => 'Employee', 'service_start_date' => '2025-01-01', 'division' => 'Test Division', 'employment_status' => 'Contract of Service']);
    PdsPersonalInformation::create(['employee_id' => $employee->id, 'date_of_birth' => '1990-06-11']);
    DtrRecord::create(['employee_id' => $employee->id, 'date' => '2026-10-20', 'time_in' => '08:30:00', 'am_out' => '12:00:00', 'pm_in' => '13:00:00', 'time_out' => '17:00:00']);
    CtoRequest::create(['employee_id' => $employee->id, 'type' => 'earn', 'date_start' => '2026-10-20', 'date_end' => '2026-10-20', 'hours' => 2, 'purpose' => '', 'status' => 'approved']);
    $type = LeaveType::create(['name' => 'Vacation Leave', 'is_active' => true]);
    LeaveRequest::create(['employee_id' => $employee->id, 'leave_type_id' => $type->id, 'date_filed' => '2026-10-01', 'start_date' => '2026-10-22', 'end_date' => '2026-10-22', 'status' => 'approved', 'is_paid' => true, 'reason' => 'Test leave']);
    $controller = app(ReportController::class);
    TravelOrder::create(['employee_id' => $employee->id, 'ta_number' => 'TA-REPORT-001', 'travel_type' => 'local', 'travel_date_start' => '2026-10-20', 'travel_date_end' => '2026-10-21', 'places_of_travel' => 'Davao', 'purpose' => 'Report test', 'status' => 'approved', 'recordofficer_status' => 'approved', 'rd_status' => 'approved', 'is_tar_required' => true, 'tar_status' => 'pending']);
    $data = $controller->index(Request::create('/reports'))->getData()['reportData'];
    expect($data['filters']['from'])->toBe('2026-01-01')->and($data['filters']['to'])->toBe('2026-11-21');
    expect($data['reports']['travel_authority']['rows'])->toHaveCount(1)
        ->and($data['reports']['travel_tar']['rows'][0]['cells'][7])->toBe('Overdue')
        ->and($data['reports']['travel_tar']['rows'][0]['cells'][3])->toBe('Oct 26, 2026');
    foreach (['hr_overview', 'attendance_summary', 'attendance_tardiness', 'leave_report', 'overtime', 'employee_birthdays'] as $key) {
        expect($data['reports'][$key]['rows'])->not->toBeEmpty();
    }
    $november = $controller->index(Request::create('/reports', 'GET', ['from' => '2026-11-01', 'to' => '2026-11-21']))->getData()['reportData'];
    expect($november['reports']['employee_birthdays']['rows'])->toBeEmpty();
    $fullYear = $controller->index(Request::create('/reports', 'GET', ['from' => '2026-01-01', 'to' => '2026-12-31', 'employment_status' => 'Contract of Service']))->getData()['reportData'];
    expect($fullYear['reports']['hr_overview']['rows'])->toHaveCount(1)
        ->and($fullYear['reports']['employee_birthdays']['rows'])->toHaveCount(1);
});

test('report filters reject future years including birthday date ranges', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-11-21 09:00:00', 'Asia/Manila'));
    $data = app(ReportController::class)->index(Request::create('/reports', 'GET', [
        'type' => 'employee_birthdays', 'from' => '2027-01-01', 'to' => '2028-12-31', 'year' => 2028,
    ]))->getData()['reportData'];
    expect($data['filters']['from'])->toBe('2026-12-31')
        ->and($data['filters']['to'])->toBe('2026-12-31')
        ->and($data['filters']['year'])->toBe(2026);
});
