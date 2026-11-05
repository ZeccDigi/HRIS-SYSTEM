<?php

namespace App\Http\Controllers;

use App\Imports\AttendanceImport;
use App\Models\DtrRecord;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\TravelOrder;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;

class DtrController extends Controller
{
    public function index(Request $request): View
    {
        return $this->dtrView($request, false);
    }

    public function myDtr(Request $request): View
    {
        return $this->dtrView($request, true);
    }

    private function dtrView(Request $request, bool $personalOnly): View
    {
        $userRole = strtoupper(auth()->user()->role);
        $adminRoles = ['ADMIN', 'HRSTAFF', 'CHIEF', 'REGIONALDIRECTOR'];
        $isAdministrativeDtr = ! $personalOnly && in_array($userRole, $adminRoles, true);

        if ($isAdministrativeDtr) {
            $selectedDate = $request->query('date', now()->format('Y-m-d'));

            try {
                Carbon::parse($selectedDate);
            } catch (\Throwable $exception) {
                $selectedDate = now()->format('Y-m-d');
            }

            $search = trim((string) $request->query('search', ''));
            $divisionFilter = (string) $request->query('division', 'all');
            $employmentStatusFilter = (string) $request->query('employment_status', 'all');

            $employeesQuery = \App\Models\Employee::query();

            if ($search !== '') {
                $employeesQuery->where(function ($query) use ($search) {
                    $query->where('firstname', 'like', "%{$search}%")
                        ->orWhere('lastname', 'like', "%{$search}%")
                        ->orWhere('division', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%")
                        ->orWhere('employment_status', 'like', "%{$search}%")
                        ->orWhere('rfid_number', 'like', "%{$search}%");
                });
            }

            if ($divisionFilter !== 'all') {
                $employeesQuery->where('division', $divisionFilter);
            }

            if ($employmentStatusFilter !== 'all') {
                $employeesQuery->where('employment_status', $employmentStatusFilter);
            }

            $employees = $employeesQuery->orderBy('lastname')->orderBy('firstname')->get();

            $divisions = \App\Models\Employee::whereNotNull('division')
                ->where('division', '!=', '')
                ->distinct()
                ->orderBy('division')
                ->pluck('division');

            $employmentStatuses = \App\Models\Employee::whereNotNull('employment_status')
                ->where('employment_status', '!=', '')
                ->distinct()
                ->orderBy('employment_status')
                ->pluck('employment_status');

            $dtrRecords = DtrRecord::whereDate('date', $selectedDate)->get()->groupBy('employee_id');
            $approvedEvents = $this->approvedAttendanceEvents(
                Carbon::parse($selectedDate),
                Carbon::parse($selectedDate),
                $employees->pluck('id')->all()
            );

            $stats = [
                'scanned' => 0,
                'present' => 0,
                'late' => 0,
                'in_office' => 0,
                'completed' => 0,
                'no_record' => 0,
                'total' => $employees->count(),
            ];

            $attendanceRecords = $employees->map(function ($employee) use ($dtrRecords, $approvedEvents, $selectedDate, &$stats) {
                $record = $dtrRecords->get($employee->id)?->first();
                $approvedEvent = $approvedEvents->get($employee->id . '|' . $selectedDate);

                $timeIn = $record?->time_in;
                $amOut = $record?->am_out;
                $pmIn = $record?->pm_in;
                $timeOut = $record?->time_out;
                $remarks = $approvedEvent['remarks'] ?? ($record?->status ?? '');

                $hasScan = $timeIn || $amOut || $pmIn || $timeOut;
                $lateMinutes = $this->lateMinutes($record);
                $workedMinutes = $this->actualWorkedMinutes($record);
                $undertimeMinutes = $this->undertimeMinutes($record);
                $hoursWorked = number_format($workedMinutes / 60, 2);

                if ($approvedEvent) {
                    $status = $approvedEvent['status'];
                    $lateMinutes = 0;
                    $undertimeMinutes = 0;
                    $hoursWorked = number_format($workedMinutes / 60, 2);
                } elseif ($hasScan) {
                    $stats['scanned']++;

                    if ($lateMinutes > 0) {
                        $status = 'Late';
                        $stats['late']++;
                    } else {
                        $status = 'In Office';
                        $stats['present']++;
                    }

                    if ($timeOut) {
                        $status = $lateMinutes > 0 ? 'Late' : 'Completed';
                        $stats['completed']++;
                    } else {
                        $stats['in_office']++;
                    }
                } else {
                    $status = $approvedEvent['status'] ?? 'Not Yet In';
                    $stats['no_record']++;
                }

                return [
                    'employee' => $employee,
                    'time_in' => $timeIn,
                    'am_out' => $amOut,
                    'pm_in' => $pmIn,
                    'time_out' => $timeOut,
                    'status' => $status,
                    'remarks' => $remarks,
                    'approved_absence' => $approvedEvent !== null,
                    'hours_worked' => $hoursWorked,
                    'late_minutes' => $lateMinutes,
                    'undertime_minutes' => $undertimeMinutes,
                ];
            })->sort(function (array $left, array $right) {
                $timeA = $left['time_in'];
                $timeB = $right['time_in'];

                if ($timeA && $timeB) {
                    return strcmp($timeA, $timeB);
                }

                if ($timeA) {
                    return -1;
                }

                if ($timeB) {
                    return 1;
                }

                return strcmp(
                    strtolower($left['employee']->lastname . ' ' . $left['employee']->firstname),
                    strtolower($right['employee']->lastname . ' ' . $right['employee']->firstname)
                );
            })->values();

            if ($request->query('export') === 'csv') {
                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => 'attachment; filename="daily_attendance_' . $selectedDate . '.csv"',
                ];

                $callback = function () use ($attendanceRecords) {
                    $file = fopen('php://output', 'w');
                    fputcsv($file, ['Employee Name', 'Employee No', 'Department', 'Position', 'Employment Status', 'First In', 'Last Out', 'Actual Hours Worked', 'Late Minutes', 'Undertime Minutes', 'Status', 'Remarks']);

                    foreach ($attendanceRecords as $item) {
                        $employee = $item['employee'];

                        fputcsv($file, [
                            trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? '')),
                            $employee->employee_no ?? ('EMP-' . str_pad((string) $employee->id, 4, '0', STR_PAD_LEFT)),
                            $employee->division ?? 'N/A',
                            $employee->position ?? 'N/A',
                            $employee->employment_status ?? 'N/A',
                            $item['time_in'] ? Carbon::parse($item['time_in'])->format('h:i A') : ($item['approved_absence'] ? $item['status'] : 'N/A'),
                            $item['time_out'] ? Carbon::parse($item['time_out'])->format('h:i A') : ($item['approved_absence'] ? $item['status'] : 'N/A'),
                            $item['hours_worked'],
                            number_format((float) ($item['late_minutes'] ?: 0), 3, '.', ''),
                            number_format((float) ($item['undertime_minutes'] ?: 0), 3, '.', ''),
                            $item['status'],
                            $item['remarks'] ?: 'N/A',
                        ]);
                    }

                    fclose($file);
                };

                return response()->stream($callback, 200, $headers);
            }

            $isPersonal = false;

            return view('dtr.hrview', compact(
                'attendanceRecords',
                'isPersonal',
                'selectedDate',
                'stats',
                'divisions',
                'divisionFilter',
                'employmentStatuses',
                'employmentStatusFilter',
                'search'
            ));
        }

        $selectedMonth = (int) $request->query('month', now()->month);
        $selectedYear = (int) $request->query('year', now()->year);
        $selectedMonth = $selectedMonth >= 1 && $selectedMonth <= 12 ? $selectedMonth : now()->month;
        $selectedYear = $selectedYear >= 2000 && $selectedYear <= 2100 ? $selectedYear : now()->year;

        $query = DtrRecord::with('employee')
            ->whereMonth('date', $selectedMonth)
            ->whereYear('date', $selectedYear);

        if ($personalOnly || ! in_array($userRole, $adminRoles, true)) {
            $employee = auth()->user()->employee;

            if ($employee) {
                $query->where('employee_id', $employee->id);
            } else {
                $query->where('employee_id', 0);
            }
        }

        $dtrRecords = $query->orderBy('date')->orderBy('time_in')->get();

        $attendanceEventsByDate = collect();
        if (isset($employee) && $employee) {
            $monthStart = Carbon::create($selectedYear, $selectedMonth, 1)->startOfDay();
            $monthEnd = $monthStart->copy()->endOfMonth();
            $attendanceEventsByDate = $this->approvedAttendanceEvents($monthStart, $monthEnd, [$employee->id])
                ->mapWithKeys(function (array $event, string $key) {
                    return [substr($key, strpos($key, '|') + 1) => $event];
                });
        }

        $actualMinutes = $dtrRecords->sum(fn ($record) => $this->actualWorkedMinutes($record));

        $lateRecords = $dtrRecords
            ->filter(fn ($record) => $this->lateMinutes($record) > 0)
            ->values();

        $selectedDate = Carbon::create($selectedYear, $selectedMonth, 1);
        $period = $selectedDate->format('F j') . ' to ' . $selectedDate->copy()->endOfMonth()->format('j, Y');
        $dtrSummary = [
            'month' => $selectedDate->format('F'),
            'year' => $selectedYear,
            'period' => $period,
            'records' => $dtrRecords->count(),
            'present' => $dtrRecords->where('status', 'Present')->count(),
            'late' => $lateRecords->count(),
            'absent' => $dtrRecords->where('status', 'Absent')->count(),
            'actual_hours' => number_format($actualMinutes / 60, 2),
            'employee_no' => auth()->user()->employee?->id ?? 'N/A',
        ];

        $isPersonal = $personalOnly;

        return view('dtr.index', compact(
            'dtrRecords',
            'isPersonal',
            'selectedMonth',
            'selectedYear',
            'dtrSummary',
            'lateRecords',
            'attendanceEventsByDate'
        ));
    }

    /**
     * Return approved leave and travel-authority events keyed by employee ID and date.
     * Biometric punches remain untouched; these values are presentation-only DTR remarks.
     */
    private function approvedAttendanceEvents(Carbon $start, Carbon $end, array $employeeIds): Collection
    {
        if ($employeeIds === []) {
            return collect();
        }

        $events = collect();
        $holidayDates = Holiday::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->mapWithKeys(fn ($date) => [Carbon::parse($date)->toDateString() => true]);

        $leaves = LeaveRequest::query()
            ->with('leaveType:id,name')
            ->where('status', 'approved')
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get();

        foreach ($leaves as $leave) {
            $leaveLabel = $this->leaveDtrLabel($leave->leaveType?->name);
            $rangeStart = Carbon::parse($leave->start_date)->max($start);
            $rangeEnd = Carbon::parse($leave->end_date)->min($end);

            foreach (CarbonPeriod::create($rangeStart, $rangeEnd) as $date) {
                $dateKey = $date->toDateString();
                if ($date->isWeekend() || $holidayDates->has($dateKey)) {
                    continue;
                }

                $this->addAttendanceEvent(
                    $events,
                    (int) $leave->employee_id,
                    $dateKey,
                    $leaveLabel,
                    $leaveLabel,
                    strtoupper($leaveLabel)
                );
            }
        }

        $travelOrders = TravelOrder::query()
            ->with('companions:id')
            ->where('status', 'approved')
            ->whereDate('travel_date_start', '<=', $end->toDateString())
            ->whereDate('travel_date_end', '>=', $start->toDateString())
            ->where(function ($query) use ($employeeIds) {
                $query->whereIn('employee_id', $employeeIds)
                    ->orWhereHas('companions', fn ($companions) => $companions->whereIn('employees.id', $employeeIds));
            })
            ->get();

        foreach ($travelOrders as $travelOrder) {
            $coveredEmployeeIds = collect([(int) $travelOrder->employee_id])
                ->merge($travelOrder->companions->pluck('id')->map(fn ($id) => (int) $id))
                ->intersect($employeeIds)
                ->unique();
            $rangeStart = Carbon::parse($travelOrder->travel_date_start)->max($start);
            $rangeEnd = Carbon::parse($travelOrder->travel_date_end)->min($end);

            foreach ($coveredEmployeeIds as $employeeId) {
                foreach (CarbonPeriod::create($rangeStart, $rangeEnd) as $date) {
                    $this->addAttendanceEvent(
                        $events,
                        $employeeId,
                        $date->toDateString(),
                        'TA',
                        'TA',
                        'TA'
                    );
                }
            }
        }

        return $events;
    }

    private function leaveDtrLabel(?string $leaveType): string
    {
        $abbreviation = match ($leaveType) {
            'Vacation Leave' => 'VL',
            'Mandatory/Force Leave' => 'MFL',
            'Sick Leave' => 'SL',
            'Maternity Leave' => 'ML',
            'Paternity Leave' => 'PL',
            'Special Privilege Leave' => 'SPL',
            'Solo Parent Leave' => 'Solo PL',
            'Study Leave' => 'Study L',
            'VAWC Leave' => 'VAWC',
            'Rehabilitation Leave' => 'RL',
            'Special Leave Benefits for Women' => 'SLBW',
            'Special Emergency (Calamity) Leave' => 'SECL',
            'Adoption Leave' => 'AL',
            'Wellness Leave' => 'WL',
            default => 'Approved',
        };

        return $abbreviation . ' Leave';
    }

    private function addAttendanceEvent(
        Collection $events,
        int $employeeId,
        string $date,
        string $status,
        string $remarks,
        string $printLabel
    ): void {
        $key = $employeeId . '|' . $date;
        $existing = $events->get($key);

        if (! $existing) {
            $events->put($key, [
                'status' => $status,
                'remarks' => $remarks,
                'print_label' => $printLabel,
            ]);

            return;
        }

        $events->put($key, [
            'status' => $existing['status'] . ' / ' . $status,
            'remarks' => $existing['remarks'] . '; ' . $remarks,
            'print_label' => $existing['print_label'] . ' / ' . $printLabel,
        ]);
    }

    private function actualWorkedMinutes(?DtrRecord $record): int
    {
        if (! $record) {
            return 0;
        }

        $minutes = 0;

        if ($record->time_in && $record->am_out) {
            $minutes += Carbon::parse($record->time_in)->diffInMinutes(Carbon::parse($record->am_out));
        }

        if ($record->pm_in && $record->time_out) {
            $minutes += Carbon::parse($record->pm_in)->diffInMinutes(Carbon::parse($record->time_out));
        }

        if ($minutes === 0 && $record->time_in && $record->time_out) {
            $minutes = Carbon::parse($record->time_in)->diffInMinutes(Carbon::parse($record->time_out));

            if ($minutes > 240) {
                $minutes -= 60;
            }
        }

        return max(0, $minutes);
    }

    private function lateMinutes(?DtrRecord $record): int
    {
        if (! $record) {
            return 0;
        }

        $minutes = 0;

        if ($record->time_in) {
            $minutes += $this->minutesAfterExpectedIn($record->time_in, '08:00:00');
        }

        if ($record->pm_in) {
            $minutes += $this->minutesAfterExpectedIn($record->pm_in, '13:00:00');
        }

        return $minutes;
    }

    private function undertimeMinutes(?DtrRecord $record): int
    {
        if (! $record) {
            return 0;
        }

        $minutes = 0;

        if ($record->am_out) {
            $minutes += $this->minutesBeforeExpectedOut($record->am_out, '12:00:00');
        }

        if ($record->time_out) {
            $minutes += $this->minutesBeforeExpectedOut($record->time_out, '17:00:00');
        }

        return $minutes;
    }

    private function minutesAfterExpectedIn(string $time, string $expectedIn): int
    {
        $actual = Carbon::parse($time);
        $expected = Carbon::parse($expectedIn);

        if ($actual->format('H:i:s') <= $expected->format('H:i:s')) {
            return 0;
        }

        return $expected->diffInMinutes($actual);
    }

    private function minutesBeforeExpectedOut(string $time, string $expectedOut): int
    {
        $actual = Carbon::parse($time);
        $expected = Carbon::parse($expectedOut);

        if ($actual->format('H:i:s') >= $expected->format('H:i:s')) {
            return 0;
        }

        return $actual->diffInMinutes($expected);
    }

    public function syncFromFile(): RedirectResponse
    {
        $absPath = storage_path('app/attendance/dtr.xlsx');

        if (! file_exists($absPath)) {
            return back()->with('error', 'Biometric file not found in: ' . $absPath);
        }

        try {
            Excel::import(new AttendanceImport, $absPath);

            return back()->with('success', 'Attendance records synced successfully from biometric file.');
        } catch (\Throwable $exception) {
            return back()->with('error', 'Error syncing file: ' . $exception->getMessage());
        }
    }
}
