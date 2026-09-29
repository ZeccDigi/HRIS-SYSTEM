<?php

namespace App\Http\Controllers;

use App\Models\CtoRequest;
use App\Models\DtrRecord;
use App\Models\Employee;
use App\Models\LeaveCredit;
use App\Models\LeaveRequest;
use App\Models\LocatorSlip;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $today = now()->toDateString();
        $firstDay = now()->startOfMonth()->toDateString();

        $selectedType = $request->query('type', 'employee_report');
        $from = $this->validDate($request->query('from'), $firstDay);
        $to = $this->validDate($request->query('to'), $today);
        $division = trim((string) $request->query('division', 'All Divisions'));
        $division = $division !== '' ? $division : 'All Divisions';
        $employmentStatus = $request->query('employment_status', 'All Employment Status');
        $databaseEmploymentStatus = $this->databaseEmploymentStatus($employmentStatus);
        $reportDivision = $division;
        $reportEmploymentStatus = $databaseEmploymentStatus;

        if (Carbon::parse($from)->gt(Carbon::parse($to))) {
            [$from, $to] = [$to, $from];
        }

        $divisions = Employee::query()
            ->whereNotNull('division')
            ->where('division', '!=', '')
            ->distinct()
            ->orderBy('division')
            ->pluck('division')
            ->map(fn ($division) => trim((string) $division))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if ($division !== 'All Divisions' && ! in_array($division, $divisions, true)) {
            $divisions[] = $division;
            sort($divisions);
        }

        $employmentStatuses = Employee::query()
            ->whereNotNull('employment_status')
            ->where('employment_status', '!=', '')
            ->distinct()
            ->orderBy('employment_status')
            ->pluck('employment_status')
            ->map(fn ($status) => $this->displayEmploymentStatus($status))
            ->unique()
            ->values()
            ->all();

        $employees = $this->employeeBaseQuery($reportDivision, $reportEmploymentStatus)
            ->with(['user', 'pdsPersonal', 'leaveCredits.leaveType'])
            ->orderBy('lastname')
            ->orderBy('firstname')
            ->get();

        $reportData = [
            'selectedType' => in_array($selectedType, array_column($this->reportTypes(), 'value'), true) ? $selectedType : 'employee_report',
            'filters' => [
                'from' => $from,
                'to' => $to,
                'division' => $division,
                'employmentStatus' => $this->displayEmploymentStatus($employmentStatus),
            ],
            'divisions' => $divisions,
            'employmentStatuses' => $employmentStatuses,
            'reportTypes' => $this->reportTypes(),
            'reports' => [
                'employee_report' => $this->employeeReport($employees),
                'attendance_report' => $this->attendanceReport($employees, $from, $to),
                'leave_report' => $this->leaveReport($reportDivision, $reportEmploymentStatus, $from, $to),
                'leave_balance' => $this->leaveBalanceReport($employees),
                'locator_slip' => $this->locatorSlipReport($reportDivision, $reportEmploymentStatus, $from, $to),
                'overtime' => $this->overtimeReport($reportDivision, $reportEmploymentStatus, $from, $to),
                'cto' => $this->ctoReport($employees),
            ],
        ];

        $reportData['reports']['overtime_cto'] = $reportData['reports']['overtime'];

        return view('reports.index', compact('reportData'));
    }

    private function employeeBaseQuery(string $division, string $employmentStatus)
    {
        return Employee::query()
            ->when($division !== 'All Divisions', fn ($query) => $this->applyDivisionFilter($query, $division))
            ->when($employmentStatus !== 'All Employment Status', fn ($query) => $this->applyEmploymentStatusFilter($query, $employmentStatus));
    }

    private function reportTypes(): array
    {
        return [
            ['value' => 'employee_report', 'label' => 'Employee Report', 'filters' => ['dateRange', 'division']],
            ['value' => 'attendance_report', 'label' => 'Attendance Report', 'filters' => ['dateRange', 'division']],
            ['value' => 'leave_report', 'label' => 'Leave Report', 'filters' => ['dateRange', 'division']],
            ['value' => 'leave_balance', 'label' => 'Leave Balance', 'filters' => ['dateRange', 'division']],
            ['value' => 'locator_slip', 'label' => 'Locator Slip Report', 'filters' => ['dateRange', 'division']],
            ['value' => 'overtime_cto', 'label' => 'Overtime & CTO', 'filters' => ['dateRange', 'division']],
        ];
    }

    private function employeeReport(Collection $employees): array
    {
        $activeAccounts = $employees->filter(fn (Employee $employee) => strtolower((string) $employee->user?->account_status) === 'active')->count();
        $regular = $employees->filter(fn (Employee $employee) => $this->isRegularStatus($employee->employment_status))->count();

        return $this->reportPayload(
            'Employee Report',
            [
                $this->metric('Total Employees', $employees->count(), 'blue', 'users'),
                $this->metric('Active Accounts', $activeAccounts, 'green', 'check'),
                $this->metric('Divisions', $employees->pluck('division')->filter()->unique()->count(), 'blue', 'chart'),
                $this->metric('Regular', $regular, 'blue', 'chart'),
            ],
            'Employees by Division',
            $this->bars($employees->countBy(fn (Employee $employee) => $employee->division ?: 'Unassigned')),
            ['Employee ID', 'Employee Name', 'Division', 'Position', 'Employment Status'],
            $employees->map(fn (Employee $employee) => $this->row([
                $employee->rfid_number ?: 'N/A',
                $this->employeeName($employee),
                $employee->division ?: 'N/A',
                $employee->position ?: 'N/A',
                $this->displayEmploymentStatus($employee->employment_status),
            ], $employee))->values()->all(),
            $employees,
            [
                $this->chart('Employees by Division', 'bar', $this->bars($employees->countBy(fn (Employee $employee) => $employee->division ?: 'Unassigned'))),
                $this->chart('Gender Distribution', 'doughnut', [
                    ['label' => 'Male', 'value' => $this->genderBreakdown($employees)['male'], 'color' => '#0038a8'],
                    ['label' => 'Female', 'value' => $this->genderBreakdown($employees)['female'], 'color' => '#ec4899'],
                ]),
                $this->chart('Employment Status', 'doughnut', collect($this->employmentBreakdown($employees)['items'])->map(fn ($item) => [
                    'label' => $item['label'],
                    'shortLabel' => $item['shortLabel'],
                    'value' => $item['value'],
                    'color' => $item['color'],
                ])->all()),
            ]
        );
    }

    private function attendanceReport(Collection $employees, string $from, string $to): array
    {
        $records = DtrRecord::query()
            ->whereBetween('date', [$from, $to])
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->groupBy('employee_id');

        $rows = $employees->map(function (Employee $employee) use ($records) {
            $employeeRecords = $records->get($employee->id, collect());
            $workingDays = $employeeRecords->pluck('date')->unique()->count();
            $late = $employeeRecords->filter(fn ($record) => strtolower((string) $record->status) === 'late' || ($record->time_in && Carbon::parse($record->time_in)->format('H:i:s') > '08:00:00'))->count();
            $absent = $employeeRecords->filter(fn ($record) => strtolower((string) $record->status) === 'absent')->count();
            $present = max($employeeRecords->count() - $absent, 0);
            $undertime = $employeeRecords->filter(fn ($record) => $record->time_out && Carbon::parse($record->time_out)->format('H:i:s') < '17:00:00')->count();
            $hours = $employeeRecords->sum(fn ($record) => $this->hoursWorked($record->time_in, $record->time_out));

            return $this->row([
                $this->employeeName($employee),
                $employee->division ?: 'N/A',
                (string) $workingDays,
                (string) $present,
                (string) $absent,
                (string) $late,
                (string) $undertime,
                number_format($hours, 2),
            ], $employee);
        })->values();
        $dailyRecords = DtrRecord::query()
            ->whereBetween('date', [$from, $to])
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->groupBy(fn (DtrRecord $record) => $this->formatDate($record->date));

        return $this->reportPayload(
            'Attendance Report',
            [
                $this->metric('Total Employees', $employees->count(), 'blue', 'users'),
                $this->metric('Present', $rows->sum(fn ($row) => (int) $row['cells'][3]), 'green', 'check'),
                $this->metric('Absent', $rows->sum(fn ($row) => (int) $row['cells'][4]), 'red', 'x'),
                $this->metric('Late', $rows->sum(fn ($row) => (int) $row['cells'][5]), 'amber', 'clock'),
                $this->metric('Undertime', $rows->sum(fn ($row) => (int) $row['cells'][6]), 'blue', 'chart'),
            ],
            'Attendance by Division',
            $this->bars($rows->countBy('division')),
            ['Employee', 'Division', 'Working Days', 'Present', 'Absent', 'Late', 'Undertime', 'Total Hours'],
            $rows->all(),
            $employees,
            [
                $this->chart('Daily Attendance Trend', 'line', $dailyRecords->map(fn ($items, $date) => ['label' => $date, 'value' => $items->count()])->values()->all()),
                $this->chart('Attendance Status', 'doughnut', [
                    ['label' => 'Present', 'value' => $rows->sum(fn ($row) => (int) $row['cells'][3]), 'color' => '#10b981'],
                    ['label' => 'Absent', 'value' => $rows->sum(fn ($row) => (int) $row['cells'][4]), 'color' => '#ef4444'],
                    ['label' => 'Late', 'value' => $rows->sum(fn ($row) => (int) $row['cells'][5]), 'color' => '#f59e0b'],
                    ['label' => 'Undertime', 'value' => $rows->sum(fn ($row) => (int) $row['cells'][6]), 'color' => '#3b82f6'],
                ]),
                $this->chart('Attendance by Division', 'bar', $this->bars($rows->countBy('division'))),
            ]
        );
    }

    private function leaveReport(string $division, string $employmentStatus, string $from, string $to): array
    {
        $requests = LeaveRequest::query()
            ->with(['employee.pdsPersonal', 'leaveType', 'regionalDirector', 'hrstaff', 'chief'])
            ->whereBetween('start_date', [$from, $to])
            ->whereHas('employee', fn ($query) => $this->applyEmployeeFilters($query, $division, $employmentStatus))
            ->latest('start_date')
            ->get();

        $rows = $requests->map(function (LeaveRequest $leave) {
            $approver = $leave->regionalDirector ?? $leave->hrstaff ?? $leave->chief;

            return $this->row([
                $this->employeeName($leave->employee),
                $leave->employee?->division ?: 'N/A',
                $leave->leaveType?->name ?: 'N/A',
                optional($leave->start_date)->format('M d, Y') ?: 'N/A',
                optional($leave->end_date)->format('M d, Y') ?: 'N/A',
                (string) $leave->duration,
                ucfirst((string) $leave->status),
                $approver ? $this->employeeName($approver) : 'N/A',
            ], $leave->employee, optional($leave->start_date)->toDateString());
        })->values();

        return $this->reportPayload(
            'Leave Report',
            [
                $this->metric('Filed', $requests->count(), 'blue', 'calendar'),
                $this->metric('Approved', $requests->where('status', 'approved')->count(), 'green', 'check'),
                $this->metric('Pending', $requests->where('status', 'pending')->count(), 'amber', 'clock'),
                $this->metric('Rejected', $requests->where('status', 'rejected')->count(), 'red', 'x'),
                $this->metric('Leave Days', $requests->sum(fn (LeaveRequest $leave) => $leave->duration), 'blue', 'chart'),
            ],
            'Leave by Type',
            $this->bars($requests->countBy(fn (LeaveRequest $leave) => $leave->leaveType?->name ?: 'Unspecified')),
            ['Employee', 'Division', 'Leave Type', 'Date From', 'Date To', 'Days', 'Status', 'Approved By'],
            $rows->all(),
            $requests->pluck('employee')->filter(),
            [
                $this->chart('Leave Applications by Type', 'bar', $this->bars($requests->countBy(fn (LeaveRequest $leave) => $leave->leaveType?->name ?: 'Unspecified'))),
                $this->chart('Leave Status', 'doughnut', [
                    ['label' => 'Approved', 'value' => $requests->where('status', 'approved')->count(), 'color' => '#10b981'],
                    ['label' => 'Pending', 'value' => $requests->where('status', 'pending')->count(), 'color' => '#f59e0b'],
                    ['label' => 'Rejected', 'value' => $requests->where('status', 'rejected')->count(), 'color' => '#ef4444'],
                ]),
                $this->chart('Leave Applications by Division', 'bar', $this->bars($requests->countBy(fn (LeaveRequest $leave) => $leave->employee?->division ?: 'Unassigned'))),
            ]
        );
    }

    private function leaveBalanceReport(Collection $employees): array
    {
        $currentYear = now()->year;
        $credits = LeaveCredit::query()
            ->with(['employee.pdsPersonal', 'leaveType'])
            ->where('year', $currentYear)
            ->whereIn('employee_id', $employees->pluck('id'))
            ->get()
            ->groupBy('employee_id');

        $rows = $employees->map(function (Employee $employee) use ($credits) {
            $employeeCredits = $credits->get($employee->id, collect());
            $vacation = $this->creditBalance($employeeCredits, 'Vacation');
            $sick = $this->creditBalance($employeeCredits, 'Sick');
            $other = $employeeCredits->reject(fn (LeaveCredit $credit) => str_contains(strtolower($credit->leaveType?->name ?? ''), 'vacation') || str_contains(strtolower($credit->leaveType?->name ?? ''), 'sick'))->sum('balance');

            return $this->row([
                $this->employeeName($employee),
                number_format((float) $vacation, 2),
                number_format((float) $sick, 2),
                number_format((float) $other, 2),
            ], $employee);
        })->values();

        return $this->reportPayload(
            'Leave Balance',
            [
                $this->metric('Employees', $employees->count(), 'blue', 'users'),
                $this->metric('Avg Vacation', number_format((float) $rows->avg(fn ($row) => (float) $row['cells'][1]), 2), 'blue', 'chart'),
                $this->metric('Avg Sick', number_format((float) $rows->avg(fn ($row) => (float) $row['cells'][2]), 2), 'green', 'check'),
                $this->metric('Low Credits', $rows->filter(fn ($row) => ((float) $row['cells'][1] + (float) $row['cells'][2]) < 5)->count(), 'amber', 'clock'),
                $this->metric('Year', (string) $currentYear, 'blue', 'calendar'),
            ],
            'Leave Credits by Division',
            $this->bars($employees->countBy(fn (Employee $employee) => $employee->division ?: 'Unassigned')),
            ['Employee', 'Vacation Leave Balance', 'Sick Leave Balance', 'Other Leave Balance'],
            $rows->all(),
            $employees,
            [
                $this->chart('Leave Balance by Division', 'bar', $this->bars($employees->countBy(fn (Employee $employee) => $employee->division ?: 'Unassigned'))),
                $this->chart('Leave Credit Type Totals', 'doughnut', [
                    ['label' => 'Vacation', 'value' => $rows->sum(fn ($row) => (float) $row['cells'][1]), 'color' => '#0038a8'],
                    ['label' => 'Sick', 'value' => $rows->sum(fn ($row) => (float) $row['cells'][2]), 'color' => '#10b981'],
                    ['label' => 'Other', 'value' => $rows->sum(fn ($row) => (float) $row['cells'][3]), 'color' => '#f59e0b'],
                ]),
                $this->chart('Employees with Leave Credits', 'bar', $this->bars($employees->countBy(fn (Employee $employee) => $employee->division ?: 'Unassigned'))),
            ]
        );
    }

    private function locatorSlipReport(string $division, string $employmentStatus, string $from, string $to): array
    {
        $slips = LocatorSlip::query()
            ->with(['employee.pdsPersonal'])
            ->whereBetween('date_covered', [$from, $to])
            ->whereHas('employee', fn ($query) => $this->applyEmployeeFilters($query, $division, $employmentStatus))
            ->latest('date_covered')
            ->get();

        $rows = $slips->map(fn (LocatorSlip $slip) => $this->row([
            $this->employeeName($slip->employee),
            $slip->employee?->division ?: 'N/A',
            $this->locatorType($slip->type),
            $this->formatDate($slip->date_covered),
            $slip->purpose ?: 'N/A',
            $slip->destination ?: 'N/A',
            $slip->time_from ? Carbon::parse($slip->time_from)->format('h:i A') : 'N/A',
            $slip->time_to ? Carbon::parse($slip->time_to)->format('h:i A') : 'N/A',
            ucfirst((string) $slip->status),
        ], $slip->employee, $this->dateKey($slip->date_covered)))->values();

        return $this->reportPayload(
            'Locator Slip Report',
            [
                $this->metric('Filed', $slips->count(), 'blue', 'chart'),
                $this->metric('Official Business', $slips->filter(fn ($slip) => str_contains(strtolower((string) $slip->type), 'official'))->count(), 'blue', 'check'),
                $this->metric('Pass Slip', $slips->filter(fn ($slip) => str_contains(strtolower((string) $slip->type), 'pass'))->count(), 'amber', 'clock'),
                $this->metric('Approved', $slips->where('status', 'approved')->count(), 'green', 'check'),
                $this->metric('Pending', $slips->where('status', 'pending')->count(), 'amber', 'clock'),
            ],
            'Locator Slips by Division',
            $this->bars($slips->countBy(fn (LocatorSlip $slip) => $slip->employee?->division ?: 'Unassigned')),
            ['Employee', 'Division', 'Type', 'Date', 'Purpose', 'Destination', 'Time Out', 'Time In', 'Status'],
            $rows->all(),
            $slips->pluck('employee')->filter(),
            [
                $this->chart('Locator Slips by Type', 'doughnut', $this->locatorTypeChart($slips)),
                $this->chart('Locator Slips by Division', 'bar', $this->bars($slips->countBy(fn (LocatorSlip $slip) => $slip->employee?->division ?: 'Unassigned'))),
                $this->chart('Daily Usage', 'line', $slips->groupBy(fn (LocatorSlip $slip) => $this->formatDate($slip->date_covered))->map(fn ($items, $date) => ['label' => $date, 'value' => $items->count()])->values()->all()),
            ]
        );
    }

    private function overtimeReport(string $division, string $employmentStatus, string $from, string $to): array
    {
        $requests = CtoRequest::query()
            ->with(['employee.pdsPersonal'])
            ->where('type', 'earn')
            ->whereBetween('date_start', [$from, $to])
            ->whereHas('employee', fn ($query) => $this->applyEmployeeFilters($query, $division, $employmentStatus))
            ->latest('date_start')
            ->get();

        $rows = $requests->map(fn (CtoRequest $request) => $this->row([
            $this->employeeName($request->employee),
            $request->employee?->division ?: 'N/A',
            optional($request->date_start)->format('M d, Y') ?: 'N/A',
            $request->purpose ?: 'N/A',
            'N/A',
            'N/A',
            number_format((float) $request->hours, 2),
            ucfirst((string) $request->status),
        ], $request->employee, optional($request->date_start)->toDateString()))->values();
        $hoursByDivision = $requests
            ->groupBy(fn (CtoRequest $request) => $request->employee?->division ?: 'Unassigned')
            ->map(fn ($items) => (float) $items->sum('hours'));

        return $this->reportPayload(
            'Overtime Summary',
            [
                $this->metric('Requests', $requests->count(), 'blue', 'chart'),
                $this->metric('Approved', $requests->where('status', 'approved')->count(), 'green', 'check'),
                $this->metric('Pending', $requests->where('status', 'pending')->count(), 'amber', 'clock'),
                $this->metric('Rejected', $requests->where('status', 'rejected')->count(), 'red', 'x'),
                $this->metric('Total Hours', number_format((float) $requests->sum('hours'), 2), 'blue', 'clock'),
            ],
            'Overtime Hours by Division',
            $this->bars($hoursByDivision),
            ['Employee', 'Division', 'Date', 'Purpose', 'Start Time', 'End Time', 'Total Hours', 'Status'],
            $rows->all(),
            $requests->pluck('employee')->filter(),
            [
                $this->chart('OT by Division', 'bar', $this->bars($hoursByDivision)),
                $this->chart('OT Status', 'doughnut', [
                    ['label' => 'Approved', 'value' => $requests->where('status', 'approved')->count(), 'color' => '#10b981'],
                    ['label' => 'Pending', 'value' => $requests->where('status', 'pending')->count(), 'color' => '#f59e0b'],
                    ['label' => 'Rejected', 'value' => $requests->where('status', 'rejected')->count(), 'color' => '#ef4444'],
                ]),
                $this->chart('Hours Rendered', 'line', $requests->groupBy(fn (CtoRequest $request) => optional($request->date_start)->format('M d, Y') ?: 'N/A')->map(fn ($items, $date) => ['label' => $date, 'value' => (float) $items->sum('hours')])->values()->all()),
            ]
        );
    }

    private function ctoReport(Collection $employees): array
    {
        $requests = CtoRequest::query()
            ->whereIn('employee_id', $employees->pluck('id'))
            ->where('status', 'approved')
            ->get()
            ->groupBy('employee_id');

        $rows = $employees->map(function (Employee $employee) use ($requests) {
            $employeeRequests = $requests->get($employee->id, collect());
            $earned = (float) $employeeRequests->where('type', 'earn')->sum('hours');
            $used = (float) $employeeRequests->where('type', 'use')->sum('hours');

            return $this->row([
                $this->employeeName($employee),
                number_format($earned, 2),
                number_format($used, 2),
                number_format((float) $employee->cto_balance, 2),
            ], $employee);
        })->values();

        return $this->reportPayload(
            'CTO Summary',
            [
                $this->metric('Employees', $employees->count(), 'blue', 'users'),
                $this->metric('CTO Earned', number_format($rows->sum(fn ($row) => (float) $row['cells'][1]), 2), 'green', 'check'),
                $this->metric('CTO Used', number_format($rows->sum(fn ($row) => (float) $row['cells'][2]), 2), 'amber', 'clock'),
                $this->metric('Remaining', number_format($rows->sum(fn ($row) => (float) $row['cells'][3]), 2), 'blue', 'chart'),
                $this->metric('Low Balance', $rows->filter(fn ($row) => (float) $row['cells'][3] < 2)->count(), 'red', 'x'),
            ],
            'CTO Balance by Division',
            $this->bars($employees->countBy(fn (Employee $employee) => $employee->division ?: 'Unassigned')),
            ['Employee', 'CTO Earned', 'CTO Used', 'Remaining Balance'],
            $rows->all(),
            $employees,
            [
                $this->chart('CTO by Division', 'bar', $this->bars($employees->countBy(fn (Employee $employee) => $employee->division ?: 'Unassigned'))),
                $this->chart('CTO Earned vs Used', 'doughnut', [
                    ['label' => 'Earned', 'value' => $rows->sum(fn ($row) => (float) $row['cells'][1]), 'color' => '#10b981'],
                    ['label' => 'Used', 'value' => $rows->sum(fn ($row) => (float) $row['cells'][2]), 'color' => '#f59e0b'],
                ]),
                $this->chart('Credits Remaining', 'bar', $rows->map(fn ($row) => ['label' => $this->abbreviateLabel($row['cells'][0]), 'fullLabel' => $row['cells'][0], 'value' => (float) $row['cells'][3]])->all()),
            ]
        );
    }

    private function reportPayload(string $title, array $metrics, string $chartTitle, array $bars, array $columns, array $rows, Collection $employees, ?array $charts = null): array
    {
        return [
            'title' => $title,
            'icon' => 'M9 17v-6m4 6V7m4 10v-4',
            'gender' => $this->genderBreakdown($employees),
            'employment' => $this->employmentBreakdown($employees),
            'metrics' => $metrics,
            'chartTwoTitle' => $chartTitle,
            'bars' => $bars,
            'charts' => $charts ?? [
                $this->chart($chartTitle, 'bar', $bars),
                $this->chart('Gender Distribution', 'doughnut', [
                    ['label' => 'Male', 'value' => $this->genderBreakdown($employees)['male'], 'color' => '#0038a8'],
                    ['label' => 'Female', 'value' => $this->genderBreakdown($employees)['female'], 'color' => '#ec4899'],
                ]),
                $this->chart('Employment Status', 'doughnut', collect($this->employmentBreakdown($employees)['items'])->all()),
            ],
            'recordCount' => count($rows),
            'columns' => $columns,
            'rows' => $rows,
        ];
    }

    private function metric(string $label, string|int|float $value, string $color, string $icon): array
    {
        $colors = [
            'blue' => ['badge' => 'bg-blue-50 text-blue-700', 'text' => 'text-[#0038a8]'],
            'green' => ['badge' => 'bg-emerald-50 text-emerald-700', 'text' => 'text-emerald-700'],
            'amber' => ['badge' => 'bg-amber-50 text-amber-700', 'text' => 'text-amber-600'],
            'red' => ['badge' => 'bg-red-50 text-red-700', 'text' => 'text-red-600'],
        ];

        $icons = [
            'chart' => 'M9 17v-6m4 6V7m4 10v-4',
            'check' => 'M5 13l4 4L19 7',
            'clock' => 'M12 8v4l3 3',
            'x' => 'M6 18L18 6M6 6l12 12',
            'users' => 'M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 11-8 0 4 4 0 018 0zm8 0a4 4 0 11-8 0 4 4 0 018 0z',
            'calendar' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
        ];

        return [
            'label' => $label,
            'value' => (string) $value,
            'badge' => $colors[$color]['badge'] ?? $colors['blue']['badge'],
            'text' => $colors[$color]['text'] ?? $colors['blue']['text'],
            'icon' => $icons[$icon] ?? $icons['chart'],
        ];
    }

    private function chart(string $title, string $type, array|Collection $items): array
    {
        $colors = ['#0038a8', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];

        return [
            'title' => $title,
            'type' => $type,
            'items' => collect($items)
                ->values()
                ->map(fn ($item, $index) => [
                    'label' => $item['label'] ?? 'N/A',
                    'fullLabel' => $item['fullLabel'] ?? ($item['label'] ?? 'N/A'),
                    'shortLabel' => $item['shortLabel'] ?? ($item['label'] ?? 'N/A'),
                    'value' => (float) ($item['value'] ?? 0),
                    'color' => $item['color'] ?? $colors[$index % count($colors)],
                ])
                ->all(),
        ];
    }

    private function row(array $cells, ?Employee $employee = null, ?string $date = null): array
    {
        return [
            'key' => md5(json_encode($cells).'-'.uniqid('', true)),
            'cells' => $cells,
            'division' => $employee?->division ?: '',
            'employmentStatus' => $this->displayEmploymentStatus($employee?->employment_status),
            'sex' => $employee?->pdsPersonal?->sex ?: '',
            'date' => $date,
        ];
    }

    private function bars(Collection $counts): array
    {
        $max = max((float) $counts->max(), 1);

        return $counts
            ->sortDesc()
            ->map(fn ($value, $label) => [
                'label' => $this->abbreviateLabel((string) $label),
                'fullLabel' => (string) $label,
                'value' => round((float) $value, 2),
                'percent' => max(6, round(((float) $value / $max) * 100)),
            ])
            ->values()
            ->all();
    }

    private function abbreviateLabel(string $label): string
    {
        $label = trim($label);

        if ($label === '') {
            return 'N/A';
        }

        $known = [
            'Migrant Workers Processing Division' => 'MWPD',
            'Finance and Administrative Division' => 'FAD',
            'Migrant Workers Protection Division' => 'MWPrD',
            'Welfare and Reintegration Services Division' => 'WRSD',
        ];

        foreach ($known as $name => $abbr) {
            if (strcasecmp($label, $name) === 0) {
                return $abbr;
            }
        }

        $words = preg_split('/\s+/', preg_replace('/[^A-Za-z0-9\s]/', ' ', $label));
        $initials = collect($words)
            ->filter(fn ($word) => strlen($word) > 0 && ! in_array(strtolower($word), ['and', 'of', 'the', 'for'], true))
            ->map(fn ($word) => strtoupper(substr($word, 0, 1)))
            ->take(5)
            ->implode('');

        return $initials !== '' ? $initials : $label;
    }

    private function genderBreakdown(Collection $employees): array
    {
        $male = $employees->filter(fn ($employee) => strtolower((string) $employee?->pdsPersonal?->sex) === 'male')->count();
        $female = $employees->filter(fn ($employee) => strtolower((string) $employee?->pdsPersonal?->sex) === 'female')->count();
        $total = max($employees->count(), 0);

        return [
            'total' => $total,
            'male' => $male,
            'female' => $female,
            'malePercent' => $total > 0 ? round(($male / $total) * 100) : 0,
        ];
    }

    private function employmentBreakdown(Collection $employees): array
    {
        $items = collect([
            ['label' => 'Regular', 'shortLabel' => 'REG.', 'value' => $employees->filter(fn ($employee) => $this->isRegularStatus($employee?->employment_status))->count(), 'color' => '#0038a8'],
            ['label' => 'Job Order', 'shortLabel' => 'J.O.', 'value' => $employees->filter(fn ($employee) => $this->sameStatus($employee?->employment_status, 'Job Order'))->count(), 'color' => '#f59e0b'],
            ['label' => 'Contract of Service', 'shortLabel' => 'C.O.S.', 'value' => $employees->filter(fn ($employee) => $this->sameStatus($employee?->employment_status, 'Contractual') || $this->sameStatus($employee?->employment_status, 'Contract of Service'))->count(), 'color' => '#10b981'],
        ]);
        $statusTotal = (int) $items->sum('value');
        $total = max($statusTotal, 1);

        return [
            'total' => $statusTotal,
            'items' => $items->map(fn ($item) => [
                ...$item,
                'percent' => round(($item['value'] / $total) * 100),
            ])->all(),
        ];
    }

    private function applyEmployeeFilters($query, string $division, string $employmentStatus): void
    {
        $query
            ->when($division !== 'All Divisions', fn ($employeeQuery) => $this->applyDivisionFilter($employeeQuery, $division))
            ->when($employmentStatus !== 'All Employment Status', fn ($employeeQuery) => $this->applyEmploymentStatusFilter($employeeQuery, $employmentStatus));
    }

    private function applyDivisionFilter($query, string $division): void
    {
        $query->whereRaw('TRIM(division) = ?', [$division]);
    }

    private function applyEmploymentStatusFilter($query, string $employmentStatus): void
    {
        if ($this->sameStatus($employmentStatus, 'Regular')) {
            $query->whereIn('employment_status', ['Regular', 'Permanent']);

            return;
        }

        $query->where('employment_status', $employmentStatus);
    }

    private function employeeName(?Employee $employee): string
    {
        if (! $employee) {
            return 'N/A';
        }

        return trim(($employee->firstname ?? '').' '.($employee->middlename ? $employee->middlename.' ' : '').($employee->lastname ?? '')) ?: 'N/A';
    }

    private function hoursWorked($timeIn, $timeOut): float
    {
        if (! $timeIn || ! $timeOut) {
            return 0.0;
        }

        $minutes = Carbon::parse($timeIn)->diffInMinutes(Carbon::parse($timeOut));

        if ($minutes > 240) {
            $minutes -= 60;
        }

        return round($minutes / 60, 2);
    }

    private function creditBalance(Collection $credits, string $needle): float
    {
        return (float) $credits
            ->filter(fn (LeaveCredit $credit) => str_contains(strtolower($credit->leaveType?->name ?? ''), strtolower($needle)))
            ->sum('balance');
    }

    private function locatorType(?string $type): string
    {
        return match (strtolower((string) $type)) {
            'official_business', 'official business', 'ob' => 'Official Business',
            'pass_slip', 'pass slip' => 'Pass Slip',
            default => $type ? ucwords(str_replace('_', ' ', $type)) : 'N/A',
        };
    }

    private function locatorTypeChart(Collection $slips): array
    {
        return $slips
            ->countBy(fn (LocatorSlip $slip) => $this->locatorType($slip->type))
            ->map(fn ($value, $label) => [
                'label' => (string) $label,
                'value' => (float) $value,
            ])
            ->values()
            ->all();
    }

    private function sameStatus(?string $actual, string $expected): bool
    {
        return strtolower(trim((string) $actual)) === strtolower($expected);
    }

    private function isRegularStatus(?string $status): bool
    {
        return $this->sameStatus($status, 'Regular') || $this->sameStatus($status, 'Permanent');
    }

    private function displayEmploymentStatus(?string $status): string
    {
        if (! $status) {
            return 'N/A';
        }

        if ($this->isRegularStatus($status)) {
            return 'Regular';
        }

        return $this->sameStatus($status, 'Contractual') ? 'Contract of Service' : $status;
    }

    private function databaseEmploymentStatus(string $status): string
    {
        if ($this->sameStatus($status, 'Regular')) {
            return 'Regular';
        }

        return $this->sameStatus($status, 'Contract of Service') ? 'Contractual' : $status;
    }

    private function validDate($value, string $fallback): string
    {
        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return $fallback;
        }
    }

    private function formatDate($value): string
    {
        try {
            return Carbon::parse($value)->format('M d, Y');
        } catch (\Throwable) {
            return 'N/A';
        }
    }

    private function dateKey($value): ?string
    {
        try {
            return Carbon::parse($value)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
