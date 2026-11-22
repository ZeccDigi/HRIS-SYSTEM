<?php

namespace App\Http\Controllers;

use App\Listeners\AuditLogListener;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use App\Notifications\LeaveRequestNotification;
use App\Notifications\LeaveStatusUpdatedNotification;
use App\Services\LeaveCreditService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class LeaveApplicationController extends Controller
{
    public function index(Request $request): View
    {
        $role = strtolower(auth()->user()->role ?? '');
        $search = trim((string) $request->query('search', ''));
        $leaveTypeId = $request->query('leave_type_id', '');
        $month = (string) $request->query('month', '');
        $sort = $request->query('sort', 'date_filed_desc');
        $perPage = $this->perPage($request);
        $query = $this->pendingLeaveApplicationsQuery($role);

        $this->applyMonthFilter($query, $month);
        $this->applyFilters($query, $search, $leaveTypeId);
        $this->applySort($query, $sort);

        $leaves = $query->paginate($perPage)->withPath(route('leave-applications.index'))->withQueryString();
        $leaveTypes = LeaveType::where('is_active', true)->orderBy('name')->get();

        return view('leaves.applications.index', compact('leaves', 'leaveTypes', 'search', 'leaveTypeId', 'month', 'sort'));
    }

    public function filter(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $leaveTypeId = $request->query('leave_type_id', '');
        $month = (string) $request->query('month', '');
        $sort = $request->query('sort', 'date_filed_desc');
        $perPage = $this->perPage($request);
        $role = strtolower(auth()->user()->role ?? '');
        $query = $this->pendingLeaveApplicationsQuery($role);

        $this->applyMonthFilter($query, $month);
        $this->applyFilters($query, $search, $leaveTypeId);
        $this->applySort($query, $sort);

        $leaves = $query->paginate($perPage)
            ->withPath(route('leave-applications.index'))
            ->withQueryString();
        $actionMode = ($role === 'admin') ? 'view' : 'review';

        return response()->json([
            'html' => view('leaves.applications._rows', [
                'leaves' => $leaves,
                'actionMode' => $actionMode,
                'emptyMessage' => __('No pending leave applications found.'),
            ])->render(),
            'count' => $leaves->total(),
            'pagination' => view('partials.table-pagination', [
                'paginator' => $leaves,
                'label' => __('leave requests'),
                'perPageOptions' => [5, 10, 20, 50],
                'perPageSelectId' => 'pending_leave_per_page',
                'formAction' => route('leave-applications.index'),
            ])->render(),
        ]);
    }

    /**
     * Display a listing of all leave requests (History).
     */
    public function all(Request $request): View
    {
        $role = strtolower(auth()->user()->role ?? '');
        $search = trim((string) $request->query('search', ''));
        $leaveTypeId = $request->query('leave_type_id', '');
        $month = (string) $request->query('month', '');
        $status = $request->query('status', '');
        $sort = $request->query('sort', 'date_filed_desc');
        $year = (string) $request->query('year', now()->year);
        $perPage = $this->perPage($request);

        $query = LeaveRequest::with(['employee', 'leaveType'])
            ->when(in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true), fn ($query) => $query->where('status', $status));

        if ($role === 'chief') {
            $this->scopeToChiefDivision($query);
        }

        $query->when($year !== '', fn ($query) => $query->whereYear('created_at', $year));
        $this->applyMonthFilter($query, $month);

        $this->applyFilters($query, $search, $leaveTypeId);
        $this->applySort($query, $sort);

        $leaves = $query->paginate($perPage)->withPath(route('leave-applications.all'))->withQueryString();
        $leaveTypes = LeaveType::where('is_active', true)->orderBy('name')->get();

        return view('leaves.applications.all', compact('leaves', 'leaveTypes', 'search', 'leaveTypeId', 'month', 'status', 'sort', 'year'));
    }

    public function allFilter(Request $request)
    {
        $role = strtolower(auth()->user()->role ?? '');
        $search = trim((string) $request->query('search', ''));
        $leaveTypeId = $request->query('leave_type_id', '');
        $month = (string) $request->query('month', '');
        $status = $request->query('status', '');
        $sort = $request->query('sort', 'date_filed_desc');
        $year = (string) $request->query('year', now()->year);
        $perPage = $this->perPage($request);

        $query = LeaveRequest::with(['employee', 'leaveType'])
            ->when(in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true), fn ($query) => $query->where('status', $status));

        if ($role === 'chief') {
            $this->scopeToChiefDivision($query);
        }

        $query->when($year !== '', fn ($query) => $query->whereYear('created_at', $year));
        $this->applyMonthFilter($query, $month);

        $this->applyFilters($query, $search, $leaveTypeId);
        $this->applySort($query, $sort);

        $leaves = $query->paginate($perPage)
            ->withPath(route('leave-applications.all'))
            ->withQueryString();

        return response()->json([
            'html' => view('leaves.applications._rows', [
                'leaves' => $leaves,
                'actionMode' => 'view',
                'emptyMessage' => __('No leave records found.'),
            ])->render(),
            'count' => $leaves->total(),
            'pagination' => view('partials.table-pagination', [
                'paginator' => $leaves,
                'label' => __('leave requests'),
                'perPageOptions' => [5, 10, 20, 50],
                'perPageSelectId' => 'all_leave_per_page',
                'formAction' => route('leave-applications.all'),
            ])->render(),
        ]);
    }

    private function pendingLeaveApplicationsQuery(string $role)
    {
        $query = LeaveRequest::where('status', 'pending')
            ->with(['employee', 'leaveType']);

        if ($role === 'admin') {
            // Admin can view all pending leave applications across HR, Chief, and Regional Director
            return $query;
        } elseif ($role === 'hrstaff') {
            $query->where('hrstaff_status', 'pending');
        } elseif ($role === 'chief') {
            $query->where('hrstaff_status', 'approved')->where('chief_status', 'pending');
            $this->scopeToChiefDivision($query);
        } elseif ($role === 'regionaldirector') {
            $query->where('hrstaff_status', 'approved')->where('chief_status', 'approved')->where('rd_status', 'pending');
        } else {
            $query->where('id', '<', 0);
        }

        return $query;
    }

    private function applyFilters($query, string $search, mixed $leaveTypeId): void
    {
        $query->where('is_monetization', request()->query('tab') === 'monetization');
        $query
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->whereHas('employee', function ($employeeQuery) use ($search) {
                            $employeeQuery
                                ->where('firstname', 'like', "%{$search}%")
                                ->orWhere('middlename', 'like', "%{$search}%")
                                ->orWhere('lastname', 'like', "%{$search}%");
                        })
                        ->orWhereHas('leaveType', function ($typeQuery) use ($search) {
                            $typeQuery->where('name', 'like', "%{$search}%");
                        });
                });
            })
            ->when($leaveTypeId !== '', fn ($query) => $query->where('leave_type_id', $leaveTypeId));
    }

    private function applyMonthFilter($query, string $month): void
    {
        $monthNumber = (int) $month;

        if ($monthNumber >= 1 && $monthNumber <= 12) {
            $query->whereMonth('created_at', $monthNumber);
        }
    }

    private function applySort($query, string $sort): void
    {
        match ($sort) {
            'date_filed_asc' => $query->orderBy('date_filed')->orderBy('created_at'),
            'leave_start_asc' => $query->orderBy('start_date')->orderBy('date_filed', 'desc'),
            'leave_start_desc' => $query->orderByDesc('start_date')->orderByDesc('date_filed'),
            'employee_asc' => $query
                ->orderBy(\App\Models\Employee::select('lastname')->whereColumn('employees.id', 'leave_requests.employee_id'))
                ->orderBy(\App\Models\Employee::select('firstname')->whereColumn('employees.id', 'leave_requests.employee_id')),
            default => $query->orderByDesc('date_filed')->orderByDesc('created_at'),
        };
    }

    /**
     * Display the specified resource for review.
     */
    public function show(LeaveRequest $leaveApplication): View
    {
        $leaveApplication->load(['employee', 'leaveType', 'chief', 'hrstaff', 'regionalDirector']);

        if (strtolower(auth()->user()->role ?? '') === 'chief' && ! $this->chiefCanReviewEmployee($leaveApplication->employee)) {
            abort(403);
        }

        $leaveYear = Carbon::parse($leaveApplication->start_date ?? $leaveApplication->date_filed)->year;
        $leaveApplication->employee?->ensureLeaveCredits($leaveYear);

        $creditLeaveType = $leaveApplication->leaveType;

        if (strtolower(trim((string) $leaveApplication->leaveType?->name)) === 'mandatory/force leave') {
            $creditLeaveType = LeaveType::where('name', 'Vacation Leave')->first() ?? $leaveApplication->leaveType;
        }

        $leaveCredit = $leaveApplication->employee?->leaveCredits()
            ->where('leave_type_id', $creditLeaveType?->id)
            ->where('year', $leaveYear)
            ->first();

        return view('leaves.applications.show', compact('leaveApplication', 'leaveCredit', 'creditLeaveType'));
    }

    public function print(LeaveRequest $leaveApplication): View
    {
        $leaveApplication->load(['employee.user', 'employee.leaveCredits.leaveType', 'employee.pdsGovId', 'leaveType', 'chief', 'hrstaff', 'regionalDirector']);

        if (strtolower(auth()->user()->role ?? '') === 'chief' && ! $this->chiefCanReviewEmployee($leaveApplication->employee)) {
            abort(403);
        }

        $leaveApplication->employee?->ensureLeaveCredits(Carbon::parse($leaveApplication->start_date ?? $leaveApplication->date_filed)->year);

        return view('leaves.print', ['leaveRequest' => $leaveApplication]);
    }

    public function update(Request $request, LeaveRequest $leaveApplication): RedirectResponse
    {
        $role = strtolower(auth()->user()->role ?? '');
        
        if ($role === 'admin') {
            return redirect()->back()->with('error', __('Admin role has read-only viewing access for leave applications. Approvals must be performed by HR, Chief, or Regional Director.'));
        }

        $leaveApplication->loadMissing('employee');

        if ($role === 'chief' && ! $this->chiefCanReviewEmployee($leaveApplication->employee)) {
            return redirect()->route('leave-applications.index')->with('error', __('You can only review leave applications from your division.'));
        }

        abort_unless($leaveApplication->status === 'pending', 403, 'This application has already been completed.');

        $employeeId = auth()->user()->employee?->id;
        $isHR = $role === 'hrstaff';

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'remarks' => 'nullable|string',
        ]);

        // HR certifies leave credits. The Division Chief and Regional Director
        // retain the decision to reject an application.
        if ($isHR && $validated['status'] !== 'approved') {
            return redirect()->back()->with('error', __('HR can only verify leave applications. Please add any concern in the remarks.'));
        }

        $remarks = $validated['remarks'] ?? null;

        DB::transaction(function () use ($validated, $leaveApplication, $role, $employeeId, $isHR, $remarks, $request) {
            $leaveApplication = LeaveRequest::whereKey($leaveApplication->id)->lockForUpdate()->firstOrFail();
            $hrPending = in_array($leaveApplication->hrstaff_status, [null, 'pending'], true)
                && $leaveApplication->approved_by_hrstaff === null;
            $chiefPending = in_array($leaveApplication->chief_status, [null, 'pending'], true)
                && $leaveApplication->approved_by_chief === null;
            $rdPending = in_array($leaveApplication->rd_status, [null, 'pending'], true)
                && $leaveApplication->approved_by_regionaldirector === null;
            $stageReady = $leaveApplication->status === 'pending' && match ($role) {
                'hrstaff' => $hrPending && $chiefPending && $rdPending,
                'chief' => $leaveApplication->hrstaff_status === 'approved' && $chiefPending && $rdPending,
                'regionaldirector' => $leaveApplication->hrstaff_status === 'approved'
                    && $leaveApplication->chief_status === 'approved' && $rdPending,
                default => false,
            };
            abort_unless($stageReady, 403, 'This application is not awaiting your decision or has already been completed.');
            if ($leaveApplication->is_monetization) {
                $employee = Employee::whereKey($leaveApplication->employee_id)->lockForUpdate()->firstOrFail();
                if ($isHR) {
                    $request->validate(['monetization_verified' => 'required|accepted']);
                    if ($leaveApplication->monetization_basis === 'exceptional' && !$leaveApplication->attachment_path) throw \Illuminate\Validation\ValidationException::withMessages(['monetization' => 'A supporting letter is required for exceptional monetization.']);
                    $available = app(\App\Services\LeaveMonetizationService::class)->validateCredits($employee, $leaveApplication->leaveType, $leaveApplication->toArray(), $leaveApplication->date_filed->year, $leaveApplication->id);
                    $leaveApplication->monetization_credit_before = $available;
                    $leaveApplication->monetization_credit_after = round($available - (float) $leaveApplication->monetization_days, 3);
                    $leaveApplication->monetization_verified_at = now();
                }
            }
            if ($validated['status'] === 'rejected') {
                $leaveApplication->status = 'rejected';
                if ($role === 'chief') {
                    $leaveApplication->chief_status = 'rejected';
                    $leaveApplication->approved_by_chief = $employeeId;
                    $leaveApplication->chief_remarks = $remarks;
                } elseif ($role === 'regionaldirector') {
                    $leaveApplication->rd_status = 'rejected';
                    $leaveApplication->approved_by_regionaldirector = $employeeId;
                    $leaveApplication->rd_remarks = $remarks;
                }
            } else { // approved/verified
                if ($isHR) {
                    $leaveApplication->hrstaff_status = 'approved';
                    $leaveApplication->approved_by_hrstaff = $employeeId;
                    $leaveApplication->hrstaff_remarks = $remarks;

                    // Notify only the chief assigned to the employee's division.
                    $nextLevelUsers = $this->chiefUsersForEmployee($leaveApplication->employee);
                    Notification::send($nextLevelUsers, new LeaveRequestNotification($leaveApplication));
                } elseif ($role === 'chief') {
                    $leaveApplication->chief_status = 'approved';
                    $leaveApplication->approved_by_chief = $employeeId;
                    $leaveApplication->chief_remarks = $remarks;

                    // Notify RD
                    $nextLevelUsers = User::whereHas('employee', function ($query) {
                        $query->where('account_role', 'regionaldirector');
                    })->get();
                    Notification::send($nextLevelUsers, new LeaveRequestNotification($leaveApplication));
                } elseif ($role === 'regionaldirector') {
                    $leaveApplication->rd_status = 'approved';
                    $leaveApplication->approved_by_regionaldirector = $employeeId;
                    $leaveApplication->rd_remarks = $remarks;
                    $leaveApplication->status = 'approved'; // Final Approval

                    app(LeaveCreditService::class)->applyApprovedLeave($leaveApplication);
                }
            }

            $leaveApplication->save();

            // Notify Employee on status change
            if ($leaveApplication->employee?->user) {
                $statusText = match ($validated['status']) {
                    'approved' => $role === 'regionaldirector' ? 'Approved' : 'Verified/Recommended',
                    'rejected' => 'Rejected',
                    default => 'Updated',
                };
                $leaveApplication->employee->user->notify(new LeaveStatusUpdatedNotification($leaveApplication, $statusText));
            }
        });

        $actionVerb = match ($validated['status']) {
            'approved' => $role === 'regionaldirector' ? 'Approved' : 'Verified/Recommended',
            'rejected' => 'Rejected',
            default => 'Updated',
        };

        AuditLogListener::logActivity(
            $actionVerb . ' Leave Application',
            'Leaves',
            $actionVerb . ' leave application (#' . $leaveApplication->id . ') for ' . trim(($leaveApplication->employee->firstname ?? '') . ' ' . ($leaveApplication->employee->lastname ?? '')),
            auth()->user()
        );

        return redirect()->route($leaveApplication->is_monetization ? 'leave-applications.all' : 'leave-applications.index', $leaveApplication->is_monetization ? ['tab' => 'monetization'] : [])->with('success', __('Leave application updated successfully.'));
    }

    private function scopeToChiefDivision($query): void
    {
        $division = $this->currentChiefDivision();

        if ($division === '') {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereHas('employee', fn ($employeeQuery) => $employeeQuery->where('division', $division));
    }

    private function chiefUsersForEmployee(?Employee $employee)
    {
        $division = trim((string) $employee?->division);

        if ($division === '') {
            return collect();
        }

        return User::whereHas('employee', function ($query) use ($division) {
            $query
                ->whereIn('account_role', ['chief', 'CHIEF'])
                ->where('division', $division);
        })->get();
    }

    private function chiefCanReviewEmployee(?Employee $employee): bool
    {
        $chiefDivision = $this->currentChiefDivision();
        $employeeDivision = trim((string) $employee?->division);

        return $chiefDivision !== '' && $employeeDivision !== '' && strcasecmp($chiefDivision, $employeeDivision) === 0;
    }

    private function currentChiefDivision(): string
    {
        return trim((string) auth()->user()?->employee?->division);
    }

    public function destroy(LeaveRequest $leaveApplication): RedirectResponse
    {
        abort_unless(in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'hrstaff', 'hr staff'], true), 403);

        $leaveApplication->delete();

        AuditLogListener::logActivity(
            'Archive Leave Application',
            'Leaves',
            "Archived leave application (#{$leaveApplication->id}) for " . trim(($leaveApplication->employee?->firstname ?? '') . ' ' . ($leaveApplication->employee?->lastname ?? '')),
            auth()->user()
        );

        return redirect()->back()->with('success', 'Leave application moved to Archive.');
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 5);

        return in_array($perPage, [5, 10, 20, 50], true) ? $perPage : 5;
    }
}
