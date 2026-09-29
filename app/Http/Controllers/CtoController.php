<?php

namespace App\Http\Controllers;

use App\Listeners\AuditLogListener;
use App\Models\CtoRequest;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\CtoRequestNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class CtoController extends Controller
{
    public function adminIndex(Request $request): View
    {
        $role = strtolower(auth()->user()->role ?? '');
        $isAdmin = $role === 'admin';
        $isHR = in_array($role, ['admin', 'hrstaff', 'hr staff'], true);
        $isChief = $role === 'chief';
        $isRegionalDirector = $role === 'regionaldirector';
        $search = trim((string) $request->query('search', ''));
        $type = $request->query('type', '');
        $sort = $request->query('sort', 'latest');

        $allCtoRequests = $this->sortCtoRequests($this->filterCtoRequests(
            CtoRequest::with(['employee', 'chief', 'hrstaff', 'regionalDirector'])->whereIn('status', ['approved', 'rejected']),
            $search,
            $type
        ), $sort)
            ->get();

        $pendingCtoRequests = $this->sortCtoRequests($this->filterCtoRequests(
            CtoRequest::with(['employee', 'chief', 'hrstaff', 'regionalDirector'])
                ->where('status', 'pending')
                ->when($isHR && !$isAdmin, fn ($query) => $query->where('hrstaff_status', 'pending'))
                ->when($isChief, fn ($query) => $query
                    ->whereIn('hrstaff_status', ['approved', 'rejected'])
                    ->where('chief_status', 'pending'))
                ->when($isRegionalDirector, fn ($query) => $query
                    ->whereIn('hrstaff_status', ['approved', 'rejected'])
                    ->where('chief_status', 'approved')
                    ->where('rd_status', 'pending')),
            $search,
            $type
        ), $sort)
            ->get();

        $tab = $request->query('tab', 'pending');
        if (! in_array($tab, ['all', 'pending'])) {
            $tab = 'pending';
        }

        return view('my-cto.admin-index', compact('allCtoRequests', 'pendingCtoRequests', 'tab', 'sort', 'search', 'type', 'isAdmin'));
    }

    private function filterCtoRequests($query, string $search, string $type)
    {
        return $query
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('purpose', 'like', "%{$search}%")
                        ->orWhereHas('employee', function ($employeeQuery) use ($search) {
                            $employeeQuery
                                ->where('firstname', 'like', "%{$search}%")
                                ->orWhere('middlename', 'like', "%{$search}%")
                                ->orWhere('lastname', 'like', "%{$search}%")
                                ->orWhere('division', 'like', "%{$search}%");
                        });
                });
            })
            ->when(in_array($type, ['earn', 'use'], true), fn ($query) => $query->where('type', $type));
    }

    private function sortCtoRequests($query, string $sort)
    {
        return match ($sort) {
            'oldest' => $query->oldest(),
            'period_asc' => $query->orderBy('date_start')->orderBy('created_at'),
            'period_desc' => $query->orderByDesc('date_start')->orderByDesc('created_at'),
            'employee_asc' => $query
                ->orderBy(Employee::select('lastname')->whereColumn('employees.id', 'cto_requests.employee_id'))
                ->orderBy(Employee::select('firstname')->whereColumn('employees.id', 'cto_requests.employee_id')),
            default => $query->latest(),
        };
    }

    public function updateStatus(Request $request, CtoRequest $ctoRequest): RedirectResponse
    {
        $role = strtolower(auth()->user()->role ?? '');

        if ($role === 'admin') {
            return redirect()->back()->with('error', __('Admin role has read-only viewing access for CTO applications. Approvals must be performed by HR, Chief, or Regional Director.'));
        }

        $employeeId = auth()->user()->employee?->id;
        $isHR = in_array($role, ['hrstaff', 'hr staff'], true);

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'remarks' => 'nullable|string',
        ]);

        $remarks = $validated['remarks'] ?? null;
        $wasFullyApproved = $ctoRequest->status === 'approved';

        DB::transaction(function () use ($validated, $ctoRequest, $role, $employeeId, $isHR, $remarks, $wasFullyApproved) {
            if ($validated['status'] === 'rejected') {
                if ($isHR) {
                    $ctoRequest->hrstaff_status = 'rejected';
                    $ctoRequest->approved_by_hrstaff = $employeeId;
                    $ctoRequest->hrstaff_remarks = $remarks;
                    
                    // Notify Chief
                    $nextLevelUsers = User::whereHas('employee', function ($query) {
                        $query->where('account_role', 'chief');
                    })->get();
                    Notification::send($nextLevelUsers, new CtoRequestNotification($ctoRequest, 'cto_rejected', 'CTO request rejected by HR.'));
                } else {
                    $ctoRequest->status = 'rejected';
                    if ($role === 'chief') {
                        $ctoRequest->chief_status = 'rejected';
                        $ctoRequest->approved_by_chief = $employeeId;
                        $ctoRequest->chief_remarks = $remarks;
                    } elseif ($role === 'regionaldirector') {
                        $ctoRequest->rd_status = 'rejected';
                        $ctoRequest->approved_by_regionaldirector = $employeeId;
                        $ctoRequest->rd_remarks = $remarks;
                    }

                    if ($ctoRequest->employee?->user) {
                        $ctoRequest->employee->user->notify(new CtoRequestNotification($ctoRequest, 'cto_rejected', 'Your CTO request was rejected.'));
                    }
                }
            } else { // approved
                if ($isHR) {
                    $ctoRequest->hrstaff_status = 'approved';
                    $ctoRequest->approved_by_hrstaff = $employeeId;
                    $ctoRequest->hrstaff_remarks = $remarks;

                    // Notify Chief
                    $nextLevelUsers = User::whereHas('employee', function ($query) {
                        $query->where('account_role', 'chief');
                    })->get();
                    Notification::send($nextLevelUsers, new CtoRequestNotification($ctoRequest, 'cto_hr_approved', 'New CTO request ready for Chief approval.'));
                } elseif ($role === 'chief') {
                    $ctoRequest->chief_status = 'approved';
                    $ctoRequest->approved_by_chief = $employeeId;
                    $ctoRequest->chief_remarks = $remarks;

                    // Notify RD
                    $nextLevelUsers = User::whereHas('employee', function ($query) {
                        $query->where('account_role', 'regionaldirector');
                    })->get();
                    Notification::send($nextLevelUsers, new CtoRequestNotification($ctoRequest, 'cto_chief_approved', 'New CTO request ready for Regional Director approval.'));
                } elseif ($role === 'regionaldirector') {
                    $ctoRequest->rd_status = 'approved';
                    $ctoRequest->approved_by_regionaldirector = $employeeId;
                    $ctoRequest->rd_remarks = $remarks;
                    $ctoRequest->status = 'approved';

                    if (! $wasFullyApproved && $ctoRequest->employee) {
                        $newBalance = $ctoRequest->cto_balance_after;

                        if ($newBalance === null) {
                            $currentBalance = (float) $ctoRequest->employee->cto_balance;
                            $hours = (float) $ctoRequest->hours;
                            $newBalance = $ctoRequest->type === 'use'
                                ? max(0, $currentBalance - $hours)
                                : $currentBalance + $hours;
                        }

                        $ctoRequest->employee->update(['cto_balance' => $newBalance]);
                    }

                    if ($ctoRequest->employee?->user) {
                        $ctoRequest->employee->user->notify(new CtoRequestNotification($ctoRequest, 'cto_fully_approved', 'Your CTO request has been fully approved.'));
                    }
                }
            }

            $ctoRequest->save();
        });

        $actionVerb = ucfirst($validated['status']);

        AuditLogListener::logActivity(
            $actionVerb . ' CTO Request',
            'Leaves',
            $actionVerb . ' CTO request (#' . $ctoRequest->id . ') for employee ' . trim(($ctoRequest->employee->firstname ?? '') . ' ' . ($ctoRequest->employee->lastname ?? '')),
            auth()->user()
        );

        return redirect()->back()->with('success', __('CTO request updated successfully.'));
    }
}
