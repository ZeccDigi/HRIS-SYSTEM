<?php

use App\Models\ActivityAudit;
use App\Models\CtoRequest;
use App\Models\DtrRecord;
use App\Models\Employee;
use App\Models\LeaveCreditTransaction;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\AttendanceCalculator;
use Database\Seeders\MiguelOctoberAttendanceDemoSeeder;

test('October demo preserves existing records and November schedule and posts credits exactly once', function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-11-18', 'Asia/Manila'));
    $user = User::factory()->create(['must_change_password' => false]);
    $week = array_fill_keys(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], ['am_start' => '08:00', 'am_end' => '12:00', 'pm_start' => '13:00', 'pm_end' => '17:00']);
    $employee = Employee::create(['user_id' => $user->id, 'firstname' => 'Miguel', 'lastname' => 'Aquino', 'account_role' => 'chief', 'service_start_date' => '2026-01-01', 'core_time_start_date' => '2026-11-12', 'core_time_end_date' => '2027-11-12', 'core_time_schedule' => $week]);
    foreach (['hrstaff', 'regionaldirector', 'recordofficer'] as $role) {
        $approverUser = User::factory()->create();
        Employee::create(['firstname' => 'Sample', 'lastname' => $role, 'user_id' => $approverUser->id, 'account_role' => $role]);
    }
    $sl = LeaveType::create(['name' => 'Sick Leave', 'is_active' => true, 'days_per_year' => 15]);
    $travelOwner = Employee::create(['firstname' => 'Existing', 'lastname' => 'Traveler']);
    $existingTravel = \App\Models\TravelOrder::create(['employee_id' => $travelOwner->id, 'ta_number' => 'TA-EXISTING-COMPANION', 'travel_type' => 'local', 'travel_date_start' => '2026-10-14', 'travel_date_end' => '2026-10-16', 'places_of_travel' => 'Davao City', 'purpose' => 'Existing travel', 'status' => 'approved']);
    $existingTravel->companions()->attach($employee->id);
    $existing = DtrRecord::create(['employee_id' => $employee->id, 'date' => '2026-10-27', 'time_in' => '10:48:21', 'am_out' => '10:50:23', 'status' => 'present']);
    $this->seed(MiguelOctoberAttendanceDemoSeeder::class);
    expect($existing->fresh()->time_in)->toBe('10:48:21')
        ->and($employee->fresh()->core_time_start_date->toDateString())->toBe('2026-11-12')
        ->and((float) $employee->fresh()->cto_balance)->toBe(0.0);
    $calculator = new AttendanceCalculator;
    expect($calculator->schedule($employee->fresh(), '2026-10-01')['am_start'])->toBe('08:00')
        ->and($calculator->schedule($employee->fresh(), '2026-11-18')['am_start'])->toBe('08:00');
    $usage = LeaveCreditTransaction::where('employee_id', $employee->id)->where('type', 'usage')->get();
    expect($usage)->toHaveCount(3)->and((float) $usage->sum('amount'))->toBe(-3.0);
    expect(CtoRequest::where('employee_id', $employee->id)->where('type', 'earn')->sum('hours'))->toEqual(8)
        ->and(CtoRequest::where('employee_id', $employee->id)->where('type', 'use')->sum('hours'))->toEqual(8);
    $balance = $employee->leaveCredits()->where('leave_type_id', $sl->id)->value('balance');
    $recordCount = DtrRecord::where('employee_id', $employee->id)->count();
    $this->seed(MiguelOctoberAttendanceDemoSeeder::class);
    expect($employee->leaveCredits()->where('leave_type_id', $sl->id)->value('balance'))->toBe($balance)
        ->and(DtrRecord::where('employee_id', $employee->id)->count())->toBe($recordCount)
        ->and(ActivityAudit::where('action', 'Create October Attendance Demo')->count())->toBe(1);
    $this->actingAs($user)->get(route('my-dtr.index', ['month' => 10, 'year' => 2026]))
        ->assertOk()->assertSee('TA-DEMO-2026-10-09')->assertSee('TA-EXISTING-COMPANION')->assertSee('Incomplete')->assertSee('25.00 min');
});
