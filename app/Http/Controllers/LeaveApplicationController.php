<?php

namespace App\Http\Controllers;

use App\Listeners\AuditLogListener;
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
        $sort = $request->query('sort', 'date_filed_desc');
        $query = $this->pendingLeaveApplicationsQuery($role);

        $this->applyFilters($query, $search, $leaveTypeId);
        $this->applySort($query, $sort);

        $leaves = $query->get();
        $leaveTypes = LeaveType::where('is_active', true)->orderBy('name')->get();

        return view('leaves.applications.index', compact('leaves', 'leaveTypes', 'search', 'leaveTypeId', 'sort'));
    }

    public function filter(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $leaveTypeId = $request->query('leave_type_id', '');
        $sort = $request->query('sort', 'date_filed_desc');
        $role = strtolower(auth()->user()->role ?? '');
        $query = $this->pendingLeaveApplicationsQuery($role);

        $this->applyFilters($query, $search, $leaveTypeId);
        $this->applySort($query, $sort);

        $leaves = $query->get();
        $actionMode = ($role === 'admin') ? 'view' : 'review';

        return response()->json([
            'html' => view('leaves.applications._rows', [
                'leaves' => $leaves,
                'actionMode' => $actionMode,
                'emptyMessage' => __('No pending leave applications found.'),
            ])->render(),
            'count' => $leaves->count(),
        ]);
    }

    /**
     * Display a listing of all leave requests (History).
     */
    public function all(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $leaveTypeId = $request->query('leave_type_id', '');
        $status = $request->query('status', '');
        $sort = $request->query('sort', 'date_filed_desc');

        $query = LeaveRequest::with(['employee', 'leaveType'])
            ->when(in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true), fn ($query) => $query->where('status', $status));

        $this->applyFilters($query, $search, $leaveTypeId);
        $this->applySort($query, $sort);

        $leaves = $query->get();
        $leaveTypes = LeaveType::where('is_active', true)->orderBy('name')->get();

        return view('leaves.applications.all', compact('leaves', 'leaveTypes', 'search', 'leaveTypeId', 'status', 'sort'));
    }

    public function allFilter(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $leaveTypeId = $request->query('leave_type_id', '');
        $status = $request->query('status', '');
        $sort = $request->query('sort', 'date_filed_desc');

        $query = LeaveRequest::with(['employee', 'leaveType'])
            ->when(in_array($status, ['pending', 'approved', 'rejected', 'cancelled'], true), fn ($query) => $query->where('status', $status));

        $this->applyFilters($query, $search, $leaveTypeId);
        $this->applySort($query, $sort);

        $leaves = $query->get();

        return response()->json([
            'html' => view('leaves.applications._rows', [
                'leaves' => $leaves,
                'actionMode' => 'view',
                'emptyMessage' => __('No leave records found.'),
            ])->render(),
            'count' => $leaves->count(),
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
            $query->whereIn('hrstaff_status', ['approved', 'rejected'])->where('chief_status', 'pending');
        } elseif ($role === 'regionaldirector') {
            $query->whereIn('hrstaff_status', ['approved', 'rejected'])->where('chief_status', 'approved')->where('rd_status', 'pending');
        } else {
            $query->where('id', '<', 0);
        }

        return $query;
    }

    private function applyFilters($query, string $search, mixed $leaveTypeId): void
    {
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
        $leaveYear = Carbon::parse($leaveApplication->start_date)->year;
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
        $leaveApplication->employee?->ensureLeaveCredits(Carbon::parse($leaveApplication->start_date)->year);

        return view('leaves.print', ['leaveRequest' => $leaveApplication]);
    }

    public function update(Request $request, LeaveRequest $leaveApplication): RedirectResponse
    {
        $role = strtolower(auth()->user()->role ?? '');
        
        if ($role === 'admin') {
            return redirect()->back()->with('error', __('Admin role has read-only viewing access for leave applications. Approvals must be performed by HR, Chief, or Regional Director.'));
        }

        $employeeId = auth()->user()->employee?->id;
        $isHR = $role === 'hrstaff';

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'remarks' => 'nullable|string',
        ]);

        $remarks = $validated['remarks'] ?? null;

        DB::transaction(function () use ($validated, $leaveApplication, $role, $employeeId, $isHR, $remarks) {
            if ($validated['status'] === 'rejected') {
                if ($isHR) {
                    $leaveApplication->hrstaff_status = 'rejected';
                    $leaveApplication->approved_by_hrstaff = $employeeId;
                    $leaveApplication->hrstaff_remarks = $remarks;
                    
                    // Notify Chief
                    $nextLevelUsers = User::whereHas('employee', function ($query) {
                        $query->where('account_role', 'chief');
                    })->get();
                    Notification::send($nextLevelUsers, new LeaveRequestNotification($leaveApplication));
                } else {
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
                }
            } else { // approved/verified
                if ($isHR) {
                    $leaveApplication->hrstaff_status = 'approved';
                    $leaveApplication->approved_by_hrstaff = $employeeId;
                    $leaveApplication->hrstaff_remarks = $remarks;

                    // Notify Chief
                    $nextLevelUsers = User::whereHas('employee', function ($query) {
                        $query->where('account_role', 'chief');
                    })->get();
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

        return redirect()->route('leave-applications.index')->with('success', __('Leave application updated successfully.'));
    }
}
