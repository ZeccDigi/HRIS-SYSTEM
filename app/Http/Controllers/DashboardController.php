<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\CtoRequest;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\LocatorSlip;
use App\Models\Saln;
use App\Models\TravelOrder;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $latestAnnouncement = Announcement::published()->visibleTo($request->user())->latest()->first();
        $employee = $request->user()->employee;
        $calendar = $this->leaveCalendarData($request);
        $approvalDashboard = $this->approvalDashboardData($request);

        return view('dashboard', array_merge([
            'latestAnnouncement' => $latestAnnouncement,
            'employee' => $employee,
        ], $calendar, $approvalDashboard));
    }

    public function leaveCalendar(Request $request): JsonResponse
    {
        $calendar = $this->leaveCalendarData($request);

        return response()->json([
            'html' => view('dashboard._leave-calendar', $calendar)->render(),
            'month' => $calendar['leaveCalendarMonth']->format('Y-m'),
        ]);
    }

    private function leaveCalendarData(Request $request): array
    {
        $employee = $request->user()->employee;
        $role = strtolower($request->user()->role ?? '');
        $isHrAdmin = in_array($role, ['admin', 'hrstaff'], true);
        $requestedMonth = $request->query('month');
        $leaveCalendarMonth = is_string($requestedMonth) && preg_match('/^\d{4}-\d{2}$/', $requestedMonth)
            ? Carbon::createFromFormat('Y-m-d', $requestedMonth . '-01')->startOfMonth()
            : now()->startOfMonth();
        $leaveCalendarStart = $leaveCalendarMonth->copy()->startOfMonth();
        $leaveCalendarEnd = $leaveCalendarMonth->copy()->endOfMonth();
        $previousLeaveCalendarMonth = $leaveCalendarMonth->copy()->subMonth()->format('Y-m');
        $nextLeaveCalendarMonth = $leaveCalendarMonth->copy()->addMonth()->format('Y-m');
        $leaveCalendarRequests = collect();
        $leaveCalendarDays = collect();
        $leaveUpcomingRequests = collect();
        $leaveRecentRequest = null;
        $travelRecentOrder = null;
        $locatorRecentSlip = null;
        $ctoRecentRequest = null;

        if ($employee) {
            if ($isHrAdmin) {
                $leaveCalendarRequests = LeaveRequest::query()
                    ->with(['employee', 'leaveType'])
                    ->whereHas('leaveType', fn ($query) => $query->where('name', 'Mandatory/Force Leave'))
                    ->whereNotIn('status', ['rejected', 'cancelled'])
                    ->where(function ($query) use ($leaveCalendarStart, $leaveCalendarEnd) {
                        $query->whereBetween('start_date', [$leaveCalendarStart, $leaveCalendarEnd])
                            ->orWhereBetween('end_date', [$leaveCalendarStart, $leaveCalendarEnd])
                            ->orWhere(function ($innerQuery) use ($leaveCalendarStart, $leaveCalendarEnd) {
                                $innerQuery->where('start_date', '<=', $leaveCalendarStart)
                                    ->where('end_date', '>=', $leaveCalendarEnd);
                            });
                    })
                    ->get();
            } else {
                $leaveCalendarRequests = $employee->leaveRequests()
                    ->with('leaveType')
                    ->whereNotIn('status', ['rejected', 'cancelled'])
                    ->where(function ($query) use ($leaveCalendarStart, $leaveCalendarEnd) {
                        $query->whereBetween('start_date', [$leaveCalendarStart, $leaveCalendarEnd])
                            ->orWhereBetween('end_date', [$leaveCalendarStart, $leaveCalendarEnd])
                            ->orWhere(function ($innerQuery) use ($leaveCalendarStart, $leaveCalendarEnd) {
                                $innerQuery->where('start_date', '<=', $leaveCalendarStart)
                                    ->where('end_date', '>=', $leaveCalendarEnd);
                            });
                    })
                    ->get();
            }

            $holidays = Holiday::whereBetween('date', [$leaveCalendarStart, $leaveCalendarEnd])
                ->get()
                ->keyBy(fn ($holiday) => $holiday->date->format('Y-m-d'));

            for ($i = 0; $i < $leaveCalendarStart->dayOfWeek; $i++) {
                $leaveCalendarDays->push([
                    'day' => null,
                    'date' => null,
                    'requests' => collect(),
                    'is_today' => false,
                    'is_weekend' => false,
                    'is_holiday' => false,
                    'holiday_name' => null,
                ]);
            }

            for ($day = 1; $day <= $leaveCalendarStart->daysInMonth; $day++) {
                $date = $leaveCalendarMonth->copy()->day($day)->startOfDay();
                $dateKey = $date->format('Y-m-d');
                $holiday = $holidays->get($dateKey);
                $requests = collect();

                if ($date->isWeekday() && !$holiday) {
                    $requests = $leaveCalendarRequests->filter(function ($leave) use ($date) {
                        return $date->between(
                            Carbon::parse($leave->start_date)->startOfDay(),
                            Carbon::parse($leave->end_date)->endOfDay()
                        );
                    })->values();
                }

                $leaveCalendarDays->push([
                    'day' => $day,
                    'date' => $date,
                    'requests' => $requests,
                    'is_today' => $date->isToday(),
                    'is_weekend' => $date->isWeekend(),
                    'is_holiday' => (bool) $holiday,
                    'holiday_name' => $holiday?->name,
                ]);
            }

            if (!$isHrAdmin) {
                $leaveRecentRequest = $employee->leaveRequests()
                    ->with('leaveType')
                    ->latest('date_filed')
                    ->latest()
                    ->first();

                $travelRecentOrder = TravelOrder::query()
                    ->where('employee_id', $employee->id)
                    ->latest()
                    ->first();

                $locatorRecentSlip = LocatorSlip::query()
                    ->where('employee_id', $employee->id)
                    ->latest()
                    ->first();

                $ctoRecentRequest = CtoRequest::query()
                    ->where('employee_id', $employee->id)
                    ->latest()
                    ->first();
            }
        }

        return compact(
            'employee',
            'leaveCalendarMonth',
            'previousLeaveCalendarMonth',
            'nextLeaveCalendarMonth',
            'leaveCalendarDays',
            'leaveUpcomingRequests',
            'leaveRecentRequest',
            'travelRecentOrder',
            'locatorRecentSlip',
            'ctoRecentRequest',
            'isHrAdmin'
        );
    }

    private function approvalDashboardData(Request $request): array
    {
        $role = strtolower($request->user()->role ?? '');
        $canViewApprovals = in_array($role, ['admin', 'hrstaff', 'recordofficer', 'chief', 'regionaldirector'], true);
        $approvalCounts = collect();
        $latestApprovals = collect();

        if (! $canViewApprovals) {
            return compact('approvalCounts', 'latestApprovals');
        }

        $leaveQuery = LeaveRequest::with(['employee', 'leaveType'])->where('status', 'pending');
        if ($role === 'hrstaff') {
            $leaveQuery->where('hrstaff_status', 'pending');
        } elseif ($role === 'chief') {
            $leaveQuery->whereIn('hrstaff_status', ['approved', 'rejected'])->where('chief_status', 'pending');
            $this->scopeToCurrentUserDivision($leaveQuery);
        } elseif ($role === 'regionaldirector') {
            $leaveQuery->whereIn('hrstaff_status', ['approved', 'rejected'])->where('chief_status', 'approved')->where('rd_status', 'pending');
        } elseif ($role === 'recordofficer') {
            $leaveQuery->whereRaw('1 = 0');
        }

        $ctoQuery = CtoRequest::with('employee')->where('status', 'pending');
        if ($role === 'hrstaff') {
            $ctoQuery->where('hrstaff_status', 'pending');
        } elseif ($role === 'chief') {
            $ctoQuery->where('hrstaff_status', 'approved')->where('chief_status', 'pending');
            $this->scopeToCurrentUserDivision($ctoQuery);
        } elseif ($role === 'regionaldirector') {
            $ctoQuery->where('hrstaff_status', 'approved')->where('chief_status', 'approved')->where('rd_status', 'pending');
        } elseif ($role === 'recordofficer') {
            $ctoQuery->whereRaw('1 = 0');
        }

        $canReviewEmployeeDocuments = in_array($role, ['admin', 'hrstaff', 'regionaldirector'], true);
        $pdsQuery = Employee::with(['pdsSectionReviews' => fn ($query) => $query->where('status', 'pending')->latest()])
            ->whereHas('pdsSectionReviews', fn ($query) => $query->where('status', 'pending'));
        $salnQuery = Saln::with('employee')->where('review_status', 'pending');

        if (! $canReviewEmployeeDocuments) {
            $pdsQuery->whereRaw('1 = 0');
            $salnQuery->whereRaw('1 = 0');
        }

        $approvalSources = collect([
            [
                'label' => 'Leave',
                'route' => route('leave-applications.index'),
                'query' => $leaveQuery,
                'title' => fn (LeaveRequest $leave) => $leave->leaveType?->name ?? 'Leave Application',
                'date' => fn (LeaveRequest $leave) => $leave->date_filed?->format('M d, Y') ?? $leave->created_at?->format('M d, Y'),
            ],
            [
                'label' => 'CTO',
                'route' => route('hr.cto.index', ['tab' => 'pending']),
                'query' => $ctoQuery,
                'title' => fn (CtoRequest $cto) => $cto->type_label,
                'date' => fn (CtoRequest $cto) => $cto->date_start?->format('M d, Y') ?? $cto->created_at?->format('M d, Y'),
            ],
            [
                'label' => 'PDS',
                'route' => fn (Employee $employee) => route('employees.show', ['employee' => $employee, 'tab' => 'pds']),
                'query' => $pdsQuery,
                'title' => fn (Employee $employee) => $employee->pdsSectionReviews->count().' section'.($employee->pdsSectionReviews->count() === 1 ? '' : 's').' updated',
                'date' => fn (Employee $employee) => $employee->pdsSectionReviews->first()?->updated_at?->format('M d, Y') ?? $employee->updated_at?->format('M d, Y'),
            ],
            [
                'label' => 'SALN',
                'route' => fn (Saln $saln) => route('salns.show', [
                    'saln' => $saln,
                    'back' => route('employees.show', ['employee' => $saln->employee_id, 'tab' => 'saln']),
                ]),
                'query' => $salnQuery,
                'title' => fn (Saln $saln) => 'SALN '.$saln->as_of_date?->format('Y'),
                'date' => fn (Saln $saln) => $saln->submitted_at?->format('M d, Y') ?? $saln->updated_at?->format('M d, Y'),
            ],
        ]);

        $approvalCounts = $approvalSources->map(function ($source) {
            return [
                'label' => $source['label'],
                'url' => is_string($source['route']) ? $source['route'] : '#',
                'count' => (clone $source['query'])->count(),
            ];
        });

        $latestApprovals = $approvalSources
            ->map(function ($source) {
                $record = (clone $source['query'])->latest()->first();

                if (! $record) {
                    return null;
                }

                $employee = $record instanceof Employee ? $record : $record->employee;
                $employeeName = trim(implode(' ', array_filter([
                    $employee?->firstname,
                    $employee?->lastname,
                ])));

                return [
                    'label' => $source['label'],
                    'title' => $source['title']($record),
                    'employee' => $employeeName ?: 'Employee',
                    'date' => $source['date']($record) ?: '',
                    'url' => is_string($source['route']) ? $source['route'] : $source['route']($record),
                ];
            })
            ->filter()
            ->take(4)
            ->values();

        return compact('approvalCounts', 'latestApprovals');
    }

    private function scopeToCurrentUserDivision($query): void
    {
        $division = trim((string) auth()->user()?->employee?->division);

        if ($division === '') {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereHas('employee', fn ($employeeQuery) => $employeeQuery->where('division', $division));
    }
}
