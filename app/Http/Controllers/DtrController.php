<?php

namespace App\Http\Controllers;

use App\Imports\AttendanceImport;
use App\Models\DtrRecord;
use App\Models\CtoRequest;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LocatorSlip;
use App\Models\TravelOrder;
use App\Services\DtrPdfExporter;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

    public function employeeDtr(Request $request, Employee $employee): View
    {
        return $this->dtrView($request, false, $employee);
    }

    public function printMyDtr(Request $request, DtrPdfExporter $exporter): Response
    {
        $dtrView = $this->dtrView($request, true);
        $data = $dtrView->getData();

        return $exporter->stream(
            $data,
            (int) $data['selectedMonth'],
            (int) $data['selectedYear']
        );
    }

    public function printEmployeeDtr(
        Request $request,
        Employee $employee,
        DtrPdfExporter $exporter
    ): Response {
        $dtrView = $this->dtrView($request, false, $employee);
        $data = $dtrView->getData();

        return $exporter->stream(
            $data,
            (int) $data['selectedMonth'],
            (int) $data['selectedYear']
        );
    }


    private function dtrView(
        Request $request,
        bool $personalOnly,
        ?Employee $selectedEmployee = null
    ): View
    {
        $userRole = strtoupper(auth()->user()->role);
        $adminRoles = ['ADMIN', 'HRSTAFF', 'CHIEF', 'REGIONALDIRECTOR'];
        $isAdministrativeDtr = ! $personalOnly
            && $selectedEmployee === null
            && in_array($userRole, $adminRoles, true);

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
                    'slot_labels' => $approvedEvent['slot_labels'] ?? [],
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
                            $item['time_in'] ? Carbon::parse($item['time_in'])->format('h:i A') : ($item['slot_labels']['time_in'] ?? 'N/A'),
                            $item['time_out'] ? Carbon::parse($item['time_out'])->format('h:i A') : ($item['slot_labels']['time_out'] ?? 'N/A'),
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

        $today = now('Asia/Manila');
        $selectedMonth = (int) $request->query('month', $today->month);
        $selectedYear = (int) $request->query('year', $today->year);
        $selectedMonth = $selectedMonth >= 1 && $selectedMonth <= 12 ? $selectedMonth : $today->month;
        $selectedYear = $selectedYear >= 2000 && $selectedYear <= 2100 ? $selectedYear : $today->year;

        $selectedDate = Carbon::create($selectedYear, $selectedMonth, 1, 0, 0, 0, 'Asia/Manila');
        $dtrMonthReleased = $selectedDate->lt($today->copy()->startOfMonth());
        $isPersonal = $selectedEmployee === null;
        $showAttendance = ! $isPersonal || $dtrMonthReleased;
        $employee = $selectedEmployee ?? $request->user()->employee;
        $oldestDtrDate = $employee
            ? DtrRecord::where('employee_id', $employee->id)
                ->when($isPersonal, fn ($query) => $query->where('date', '<', $today->copy()->startOfMonth()->toDateString()))
                ->min('date')
            : null;
        $firstDtrYear = min($today->year - 3, $selectedYear, $oldestDtrDate ? Carbon::parse($oldestDtrDate)->year : $today->year);

        $dtrRecords = $showAttendance && $employee
            ? DtrRecord::where('employee_id', $employee->id)
                ->whereBetween('date', [$selectedDate->toDateString(), $selectedDate->copy()->endOfMonth()->toDateString()])
                ->orderBy('date')->orderBy('time_in')->get()
            : collect();

        $attendanceEventsByDate = collect();
        if ($employee && $showAttendance) {
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
            'employee_no' => $employee?->employee_no
                ?? ($employee?->id ? 'EMP-'.str_pad((string) $employee->id, 4, '0', STR_PAD_LEFT) : 'N/A'),
        ];

        $dtrEmployee = $employee ?? null;

        return view('dtr.index', compact(
            'dtrRecords',
            'isPersonal',
            'selectedMonth',
            'selectedYear',
            'dtrSummary',
            'lateRecords',
            'attendanceEventsByDate',
            'dtrEmployee',
            'dtrMonthReleased',
            'firstDtrYear'
        ));
    }


    /**
     * Return approved leave, travel-authority, locator-slip, and Use CTO events keyed by employee ID and date.
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
            $leaveReference = $this->leaveReferenceNumber($leave);
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
                    $leaveReference,
                    'Leave',
                    $leaveReference
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
            $travelReference = $travelOrder->ta_number
                ?: sprintf('TA-%s-%03d', Carbon::parse($travelOrder->created_at ?? $travelOrder->travel_date_start)->format('Y-m-d'), $travelOrder->id);
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
                        $travelReference,
                        'TA',
                        $travelReference
                    );
                }
            }
        }

        $locatorSlips = LocatorSlip::query()
            ->where('status', 'approved')
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('date_covered', [$start->toDateString(), $end->toDateString()])
            ->get();

        foreach ($locatorSlips as $locatorSlip) {
            // A locator slip affects the DTR only after the employee's QR OUT
            // scan is confirmed. The return QR IN scan is useful when available,
            // but a missed return scan does not invalidate an approved slip.
            // Without the OUT scan, the slip must not fill DTR slots or remarks.
            if (! $locatorSlip->time_from || ! $locatorSlip->out_scanned_at) {
                continue;
            }

            $locatorType = $locatorSlip->type === 'Official Business'
                ? 'Official Business'
                : 'Pass Slip';

            $this->addAttendanceEvent(
                $events,
                (int) $locatorSlip->employee_id,
                Carbon::parse($locatorSlip->date_covered)->toDateString(),
                $locatorType,
                $locatorType,
                $locatorType,
                $this->locatorSlipSlotLabels($locatorSlip, $locatorType)
            );
        }

        // An approved Use CTO application fills only missing DTR slots. Actual
        // biometric punches remain visible and the approved CTO reference is
        // retained in the DTR remarks for auditability.
        $ctoRequests = CtoRequest::query()
            ->where('type', 'use')
            ->where('status', 'approved')
            ->whereIn('employee_id', $employeeIds)
            ->whereDate('date_start', '<=', $end->toDateString())
            ->whereDate('date_end', '>=', $start->toDateString())
            ->get();

        foreach ($ctoRequests as $ctoRequest) {
            $rangeStart = Carbon::parse($ctoRequest->date_start)->max($start);
            $rangeEnd = Carbon::parse($ctoRequest->date_end)->min($end);
            $reference = $ctoRequest->reference;

            foreach (CarbonPeriod::create($rangeStart, $rangeEnd) as $date) {
                if ($date->isWeekend() || $holidayDates->has($date->toDateString())) {
                    continue;
                }

                $this->addAttendanceEvent(
                    $events,
                    (int) $ctoRequest->employee_id,
                    $date->toDateString(),
                    'Use CTO',
                    $reference,
                    $reference
                );
            }
        }

        return $events;
    }

    private function leaveReferenceNumber(LeaveRequest $leave): string
    {
        $referenceDate = $leave->date_filed ?? $leave->created_at ?? $leave->start_date;

        return sprintf('LR-%s-%04d', Carbon::parse($referenceDate)->format('Y'), $leave->id);
    }

    private function locatorSlipReferenceNumber(LocatorSlip $locatorSlip): string
    {
        return sprintf(
            'LS-%s-%04d',
            Carbon::parse($locatorSlip->date_covered ?? $locatorSlip->created_at)->format('Y'),
            $locatorSlip->id
        );
    }

    private function locatorSlipSlotLabels(LocatorSlip $locatorSlip, string $reference): array
    {
        if (! $locatorSlip->time_from || ! $locatorSlip->time_to) {
            return array_fill_keys(['time_in', 'am_out', 'pm_in', 'time_out'], $reference);
        }

        $from = Carbon::parse($locatorSlip->time_from);
        $to = Carbon::parse($locatorSlip->time_to);
        $fromMinutes = ($from->hour * 60) + $from->minute;
        $toMinutes = ($to->hour * 60) + $to->minute;

        if ($toMinutes < $fromMinutes) {
            $toMinutes += 24 * 60;
        }

        $slotMinutes = [
            'time_in' => 8 * 60,
            'am_out' => 12 * 60,
            'pm_in' => 13 * 60,
            'time_out' => 17 * 60,
        ];

        return collect($slotMinutes)
            ->filter(fn (int $minutes) => $minutes >= $fromMinutes && $minutes <= $toMinutes)
            ->mapWithKeys(fn (int $minutes, string $slot) => [$slot => $reference])
            ->all();
    }

    private function addAttendanceEvent(
        Collection $events,
        int $employeeId,
        string $date,
        string $status,
        string $remarks,
        string $printLabel,
        ?array $slotLabels = null
    ): void {
        $key = $employeeId . '|' . $date;
        $existing = $events->get($key);
        $slotLabels ??= array_fill_keys(['time_in', 'am_out', 'pm_in', 'time_out'], $printLabel);

        if (! $existing) {
            $events->put($key, [
                'status' => $status,
                'remarks' => $remarks,
                'print_label' => $printLabel,
                'slot_labels' => $slotLabels,
            ]);

            return;
        }

        $mergedSlotLabels = $existing['slot_labels'] ?? [];
        foreach ($slotLabels as $slot => $label) {
            $mergedSlotLabels[$slot] = isset($mergedSlotLabels[$slot])
                ? $mergedSlotLabels[$slot] . ' / ' . $label
                : $label;
        }

        $events->put($key, [
            'status' => $existing['status'] . ' / ' . $status,
            'remarks' => $existing['remarks'] . '; ' . $remarks,
            'print_label' => $existing['print_label'] . ' / ' . $printLabel,
            'slot_labels' => $mergedSlotLabels,
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
