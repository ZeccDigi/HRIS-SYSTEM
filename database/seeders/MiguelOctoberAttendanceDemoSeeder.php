<?php

namespace Database\Seeders;

use App\Listeners\AuditLogListener;
use App\Models\ActivityAudit;
use App\Models\CtoRequest;
use App\Models\DtrRecord;
use App\Models\Employee;
use App\Models\LeaveCreditTransaction;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\LocatorSlip;
use App\Models\TravelOrder;
use App\Services\AttendanceCalculator;
use App\Services\LeaveCreditService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MiguelOctoberAttendanceDemoSeeder extends Seeder
{
    public const TAG = '[DEMO-OCT-2026-MIGUEL]';

    public function run(): void
    {
        $employee = Employee::whereRaw('LOWER(firstname) = ?', ['miguel'])->whereRaw('LOWER(lastname) = ?', ['aquino'])->where('account_role', 'chief')->sole();
        DB::transaction(function () use ($employee) {
            $employee = Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
            if (ActivityAudit::withTrashed()->where('action', 'Create October Attendance Demo')->where('description', 'like', self::TAG.'%')->exists()) {
                $this->command?->info('October demonstration already exists. No records or balances changed.');
                return;
            }
            $hr = Employee::where('account_role', 'hrstaff')->firstOrFail();
            $rd = Employee::where('account_role', 'regionaldirector')->firstOrFail();
            $ro = Employee::where('account_role', 'recordofficer')->firstOrFail();
            $sl = LeaveType::where('name', 'Sick Leave')->firstOrFail();
            $creditService = app(LeaveCreditService::class);
            $before = [
                'employee' => $employee->only(['id', 'cto_balance', 'core_time_start_date', 'core_time_end_date', 'core_time_schedule']),
                'history' => $employee->coreTimeSchedules->toArray(),
                'credits' => $employee->leaveCredits->toArray(),
                'attendance' => DtrRecord::where('employee_id', $employee->id)->whereBetween('date', ['2026-10-01', '2026-10-31'])->get()->toArray(),
            ];
            $days = [
                '01' => ['08:00', '12:00', '13:00', '17:00'],
                '02' => ['06:00', '12:00', '12:00:05', '17:00'],
                '05' => ['08:15', '12:00', '13:15', '17:00'],
                '06' => ['08:30', '12:00', '13:25', '17:00'],
                '07' => ['08:00', '11:45', '13:00', '16:30'],
                '08' => ['08:00', null, null, null],
                '09' => ['08:00', null, null, '17:00'],
                '12' => [null, null, '13:00', '17:00'],
                '14' => ['08:30', null, null, null],
                '16' => ['07:50', null, null, null],
                '19' => ['08:00', '12:00', '13:00', '17:00'],
                '20' => ['08:30', '12:00', '13:00', '17:00'],
                '21' => ['08:00', '12:00', '13:00', '17:00'],
                '22' => ['08:00', '12:00', '13:00', '21:00'],
                '23' => ['08:00', '12:00', '13:00', '21:00'],
                '26' => ['08:05', null, null, null],
                '29' => ['08:00', null, null, null],
                '30' => ['08:00', '12:00', '13:00', null],
            ];
            // Include application-only dates in the collision check too.
            $reserved = collect(array_keys($days))->merge(['13', '15', '28', '29'])->map(fn ($day) => '2026-10-'.$day)->all();
            if (DtrRecord::where('employee_id', $employee->id)->whereIn('date', $reserved)->exists()) throw new \RuntimeException('A sample date already has attendance; nothing was overwritten.');
            foreach ([LeaveRequest::class => ['start_date', 'end_date'], TravelOrder::class => ['travel_date_start', 'travel_date_end'], CtoRequest::class => ['date_start', 'date_end']] as $model => [$start, $end]) {
                if ($model::where('employee_id', $employee->id)->whereDate($start, '<=', '2026-10-31')->whereDate($end, '>=', '2026-10-01')->exists()) throw new \RuntimeException('October already has applications; nothing was overwritten.');
            }
            if (LocatorSlip::where('employee_id', $employee->id)->whereBetween('date_covered', ['2026-10-01', '2026-10-31'])->exists()) throw new \RuntimeException('October already has locator slips.');
            $companionTravel = TravelOrder::whereHas('companions', fn ($query) => $query->where('employees.id', $employee->id))
                ->whereDate('travel_date_start', '<=', '2026-10-31')->whereDate('travel_date_end', '>=', '2026-10-01')->get();
            foreach ($companionTravel as $travelOrder) {
                foreach (['2026-10-02', '2026-10-09', '2026-10-26'] as $newTravelDate) {
                    if ($newTravelDate >= $travelOrder->travel_date_start->toDateString() && $newTravelDate <= $travelOrder->travel_date_end->toDateString()) {
                        throw new \RuntimeException('A planned demo TA conflicts with existing companion travel.');
                    }
                }
            }
            $before['companion_travel_ids'] = $companionTravel->pluck('id')->all();
            $creditService->ensureCredits($employee, 2026, now('Asia/Manila'));
            $slBefore = (float) $employee->leaveCredits()->where('year', 2026)->where('leave_type_id', $sl->id)->value('balance');
            if ($slBefore < 3) throw new \RuntimeException('At least three Sick Leave credits are required for the requested actual deductions.');
            if (!app()->runningUnitTests()) {
                File::ensureDirectoryExists(storage_path('app/private/attendance-demos'));
                File::put(storage_path('app/private/attendance-demos/miguel-october-2026-before.json'), json_encode($before, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            }
            $week = array_fill_keys(['monday', 'tuesday', 'wednesday', 'thursday', 'friday'], ['am_start' => '08:00', 'am_end' => '12:00', 'pm_start' => '13:00', 'pm_end' => '17:00']);
            $employee->coreTimeSchedules()->create(['applies_from' => '2026-10-01', 'core_time_start_date' => '2026-10-01', 'core_time_end_date' => '2026-10-31', 'core_time_schedule' => $week]);
            // Restore the existing version after the demonstration month, without editing it.
            $employee->coreTimeSchedules()->create([...$employee->only(['core_time_start_date', 'core_time_end_date', 'core_time_schedule']), 'applies_from' => '2026-11-01']);
            foreach ($days as $day => $punches) {
                $date = '2026-10-'.$day;
                $values = array_combine(['time_in', 'am_out', 'pm_in', 'time_out'], array_map(fn ($time) => $time ? Carbon::parse($time)->format('H:i:s') : null, $punches));
                DtrRecord::create(['employee_id' => $employee->id, 'date' => $date, ...$values, 'status' => 'present', 'created_at' => $date.' 18:00:00', 'updated_at' => $date.' 18:00:00']);
            }
            $approval = ['hrstaff_status' => 'approved', 'chief_status' => 'approved', 'rd_status' => 'approved', 'approved_by_hrstaff' => $hr->id, 'approved_by_regionaldirector' => $rd->id,
                'hrstaff_remarks' => self::TAG.' simulated verification', 'chief_remarks' => self::TAG.' simulated recommendation; no actual officer action', 'rd_remarks' => self::TAG.' simulated final approval'];
            $leaves = [];
            foreach (['08', '13', '26'] as $day) {
                $date = '2026-10-'.$day;
                $leave = LeaveRequest::create(['employee_id' => $employee->id, 'leave_type_id' => $sl->id, 'start_date' => $date, 'end_date' => $date, 'date_filed' => $date.' 07:00:00', 'reason' => self::TAG.' Sample sick leave'.($day === '26' ? ' — overlap demonstration' : ''), 'status' => 'approved', 'is_paid' => true, ...$approval, 'created_at' => $date.' 07:00:00', 'updated_at' => $date.' 08:00:00']);
                // Recompute from approved requests once; do not add usage a second time.
                $creditService->ensureCredits($employee, 2026, now('Asia/Manila'));
                $balance = (float) $employee->leaveCredits()->where('year', 2026)->where('leave_type_id', $sl->id)->value('balance');
                LeaveCreditTransaction::create(['employee_id' => $employee->id, 'leave_type_id' => $sl->id, 'leave_request_id' => $leave->id, 'type' => 'usage', 'amount' => -1, 'balance_after' => $balance, 'effective_date' => $date, 'description' => self::TAG.' Requested actual one-day deduction', 'metadata' => ['demo' => true, 'tag' => self::TAG]]);
                $leaves[] = $leave->id;
            }
            LeaveRequest::create(['employee_id' => $employee->id, 'leave_type_id' => $sl->id, 'start_date' => '2026-10-29', 'end_date' => '2026-10-29', 'date_filed' => '2026-10-28 08:00:00', 'reason' => self::TAG.' Pending leave does not cover attendance or deduct credits', 'status' => 'pending', 'is_paid' => true, 'created_at' => '2026-10-28 08:00:00', 'updated_at' => '2026-10-28 08:00:00']);
            $travel = [];
            foreach (['02', '09', '26'] as $day) {
                $date = '2026-10-'.$day;
                $ta = TravelOrder::create(['employee_id' => $employee->id, 'ta_number' => 'TA-DEMO-2026-10-'.$day, 'travel_type' => 'local', 'travel_date_start' => $date, 'travel_date_end' => $date, 'places_of_travel' => 'Davao City — sample visit', 'purpose' => self::TAG.' Sample official travel'.($day === '26' ? ' — overlap demonstration' : ''), 'notes_remarks' => self::TAG.' Demonstration, not a real travel authority', 'requesting_office' => 'Regional Office XI', 'status' => 'approved', 'recordofficer_status' => 'approved', 'approved_by_recordofficer' => $ro->id, 'recordofficer_remarks' => self::TAG.' Simulated verification', 'rd_status' => 'approved', 'approved_by_regionaldirector' => $rd->id, 'rd_remarks' => self::TAG.' Simulated final approval', 'tar_deadline' => Carbon::parse($date)->addDays(5)->toDateString(), 'created_at' => Carbon::parse($date)->subDay()->setTime(8, 0), 'updated_at' => $date.' 08:00:00']);
                $travel[] = $ta->id;
            }
            $locators = [];
            foreach ([['19', 'Pass Slip', '10:00:00', '11:00:00'], ['20', 'Official Business', '14:00:00', '15:30:00'], ['21', 'Pass Slip', '14:00:00', null], ['22', 'Official Business', null, null]] as [$day, $type, $out, $in]) {
                $date = '2026-10-'.$day;
                $slip = LocatorSlip::create(['employee_id' => $employee->id, 'date_covered' => $date, 'destination' => 'Davao City — sample errand', 'purpose' => self::TAG.' '.($out ? ($in ? 'Confirmed departure and return' : 'Confirmed OUT, missing IN') : 'Approved but never scanned OUT'), 'type' => $type, 'time_from' => $out, 'time_to' => $in, 'out_scanned_at' => $out ? $date.' '.$out : null, 'status' => 'approved', 'chief_remarks' => self::TAG.' Simulated approval', 'created_at' => $date.' 07:00:00', 'updated_at' => $date.' 18:00:00']);
                $locators[] = $slip->id;
                $slip->forceFill(['chief_approval_status' => 'approved', 'approved_by_chief_name' => 'DEMO — simulated approval', 'chief_approval_date' => $date.' 07:30:00'])->save();
            }
            $cto = [];
            foreach ([['22', 'earn', 4], ['23', 'earn', 4], ['28', 'use', 8]] as [$day, $type, $hours]) {
                $date = '2026-10-'.$day;
                $beforeCto = (float) $employee->fresh()->cto_balance;
                if ($type === 'use' && $beforeCto < $hours) throw new \RuntimeException('Insufficient CTO credits for the demonstration.');
                $afterCto = $type === 'earn' ? $beforeCto + $hours : $beforeCto - $hours;
                $request = CtoRequest::create(['employee_id' => $employee->id, 'type' => $type, 'date_start' => $date, 'date_end' => $date, 'hours' => $hours, 'purpose' => self::TAG.($type === 'earn' ? ' Sample overtime processing, 5 PM–9 PM' : ' Sample full-day Use CTO'), 'status' => 'approved', ...$approval, 'hr_verified_at' => $date.' 21:30:00', 'cto_balance_before' => $beforeCto, 'cto_balance_after' => $afterCto, 'created_at' => $date.' 08:00:00', 'updated_at' => $date.' 21:30:00']);
                $employee->forceFill(['cto_balance' => $afterCto])->save();
                AuditLogListener::logActivity($type === 'earn' ? 'Demo Earn COC Credits' : 'Demo Use CTO Deduction', 'CTO', self::TAG.' '.$request->reference.' '.$beforeCto.' → '.$afterCto.' hours');
                $cto[] = $request->id;
            }
            $employee->refresh();
            $slAfter = (float) $employee->leaveCredits()->where('year', 2026)->where('leave_type_id', $sl->id)->value('balance');
            if (abs(($slBefore - $slAfter) - 3) > 0.001) throw new \RuntimeException('Leave deduction verification failed; all changes rolled back.');
            $manifest = ['tag' => self::TAG, 'employee_id' => $employee->id, 'month' => '2026-10', 'attendance_added' => count($days), 'existing_attendance_preserved' => array_column($before['attendance'], 'id'), 'leave_ids' => $leaves, 'travel_ids' => $travel, 'locator_ids' => $locators, 'cto_ids' => $cto, 'sick_leave_before' => $slBefore, 'sick_leave_after' => $slAfter, 'cto_before' => (float) $before['employee']['cto_balance'], 'cto_after' => (float) $employee->cto_balance];
            AuditLogListener::logActivity('Create October Attendance Demo', 'Attendance', self::TAG.' '.json_encode($manifest, JSON_THROW_ON_ERROR));
            if (!app()->runningUnitTests()) File::put(storage_path('app/private/attendance-demos/miguel-october-2026-manifest.json'), json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
            $this->command?->info(json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
        });
    }
}
