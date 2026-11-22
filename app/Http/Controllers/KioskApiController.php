<?php

namespace App\Http\Controllers;

use App\Models\DtrRecord;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KioskApiController extends Controller
{
    public static function serverId(): string
    {
        return DB::table('kiosk_server_identity')->where('id', 1)->value('server_id');
    }

    public function status()
    {
        return response()->json(['success' => true, 'server_id' => self::serverId(), 'server_time' => now('Asia/Manila')->toIso8601String(), 'protocol' => 1]);
    }

    public function employees()
    {
        $employees = Employee::with('coreTimeSchedules')->whereHas('user', fn ($q) => $q->where('account_status', '!=', 'disabled')->where('is_approved', true))->whereNotNull('rfid_number')->where('rfid_number', '!=', '')->orderBy('id')->get();

        return response()->json(['success' => true, 'server_id' => self::serverId(), 'employees' => $employees->map(fn ($e) => [
            'id' => $e->id, 'firstname' => $e->firstname, 'middlename' => $e->middlename, 'lastname' => $e->lastname, 'position' => $e->position, 'division' => $e->division, 'rfid_number' => $e->rfid_number,
            'core_time_start_date' => $e->core_time_start_date?->toDateString(), 'core_time_end_date' => $e->core_time_end_date?->toDateString(), 'core_time_schedule' => $e->core_time_schedule,
            'core_time_history' => $e->coreTimeSchedules->map(fn ($v) => ['id' => $v->id, 'applies_from' => $v->applies_from?->toDateString(), 'core_time_start_date' => $v->core_time_start_date?->toDateString(), 'core_time_end_date' => $v->core_time_end_date?->toDateString(), 'core_time_schedule' => $v->core_time_schedule])->values(),
        ])->values()]);
    }

    public function attendance(Request $request)
    {
        $data = $request->validate(['server_id' => 'required|uuid', 'records' => 'required|array|min:1|max:50', 'records.*.employee_id' => 'required|integer', 'records.*.rfid_number' => 'required|string|max:100', 'records.*.date' => 'required|date_format:Y-m-d|before_or_equal:today', 'records.*.time_in' => 'nullable|date_format:Y-m-d H:i:s', 'records.*.am_out' => 'nullable|date_format:Y-m-d H:i:s', 'records.*.pm_in' => 'nullable|date_format:Y-m-d H:i:s', 'records.*.time_out' => 'nullable|date_format:Y-m-d H:i:s']);
        if ($data['server_id'] !== self::serverId()) {
            return response()->json(['success' => false, 'message' => 'This queue belongs to a different HRIS. Reconnect the original HRIS.'], 409);
        }
        $count = 0;
        foreach (collect($data['records'])->sortBy(fn ($r) => sprintf('%020d', $r['employee_id']).$r['date']) as $row) {
            $employee = Employee::whereKey($row['employee_id'])->where('rfid_number', $row['rfid_number'])->whereHas('user', fn ($q) => $q->where('account_status', '!=', 'disabled')->where('is_approved', true))->lockForUpdate()->first();
            if (! $employee) {
                throw ValidationException::withMessages(['records' => 'An employee is inactive, archived, or no longer matches the RFID. Refresh employee sync.']);
            }
            $record = DtrRecord::where('employee_id', $employee->id)->whereDate('date', $row['date'])->lockForUpdate()->first();
            $updates = [];
            foreach (['time_in', 'am_out', 'pm_in', 'time_out'] as $slot) {
                if (empty($row[$slot])) {
                    continue;
                }
                $at = Carbon::createFromFormat('Y-m-d H:i:s', $row[$slot], 'Asia/Manila');
                if ($at->toDateString() !== $row['date'] || $at->gt(now('Asia/Manila')->addMinutes(2))) {
                    throw ValidationException::withMessages(['records' => 'A punch date or kiosk clock is invalid.']);
                }
                $clock = $at->format('H:i:s');
                if ($record?->{$slot} && substr((string) $record->{$slot}, 0, 8) !== $clock) {
                    throw ValidationException::withMessages(['records' => "An existing $slot punch differs. HR must review it; the kiosk did not overwrite it."]);
                }
                $updates[$slot] = $clock;
            }
            if (! $updates) {
                throw ValidationException::withMessages(['records' => 'At least one attendance punch is required.']);
            }
            $record ? $record->update($updates) : DtrRecord::create($updates + ['employee_id' => $employee->id, 'date' => $row['date'], 'status' => 'Present']);
            $count++;
        }

        return response()->json(['success' => true, 'count' => $count, 'message' => 'Attendance saved in the HRIS.']);
    }
}
