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
        $status = $request->query('status', '');
        $sort = $request->query('sort', 'latest');

        $allCtoRequests = $this->sortCtoRequests($this->filterCtoRequests(
            tap(
                CtoRequest::with(['employee', 'chief', 'hrstaff', 'regionalDirector']),
                fn ($query) => $isChief ? $this->scopeToChiefDivision($query) : null
            ),
            $search,
            $status
        ), $sort)
            ->get();

        $pendingCtoRequests = $this->sortCtoRequests($this->filterCtoRequests(
            CtoRequest::with(['employee', 'chief', 'hrstaff', 'regionalDirector'])
                ->where('status', 'pending')
                ->when($isHR && !$isAdmin, fn ($query) => $query->where('hrstaff_status', 'pending'))
                ->when($isChief, function ($query) {
                    $query
                        ->where('hrstaff_status', 'approved')
                        ->where('chief_status', 'pending');
                    $this->scopeToChiefDivision($query);
                })
                ->when($isRegionalDirector, fn ($query) => $query
                    ->where('hrstaff_status', 'approved')
                    ->where('chief_status', 'approved')
                    ->where('rd_status', 'pending')),
            $search,
            $status
        ), $sort)
            ->get();

        $tab = $request->query('tab', 'pending');
        if (! in_array($tab, ['all', 'pending'])) {
            $tab = 'pending';
        }

        return view('my-cto.admin-index', compact('allCtoRequests', 'pendingCtoRequests', 'tab', 'sort', 'search', 'status', 'isAdmin'));
    }

    private function filterCtoRequests($query, string $search, string $status)
    {
        return $query
            ->where(fn ($query) => $query->whereNull('credit_source')->orWhere('credit_source', '!=', 'hr_direct'))
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
            ->when(in_array($status, ['pending', 'approved', 'rejected'], true), fn ($query) => $query->where('status', $status));
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

        $ctoRequest->loadMissing('employee');

        if ($role === 'chief' && ! $this->chiefCanReviewEmployee($ctoRequest->employee)) {
            return redirect()->route('hr.cto.index', ['tab' => 'pending'])->with('error', __('You can only review CTO requests from your division.'));
        }

        $employeeId = auth()->user()->employee?->id;
        $isHR = in_array($role, ['hrstaff', 'hr staff'], true);

        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'remarks' => 'nullable|string',
        ]);

        $remarks = $validated['remarks'] ?? null;
        $wasFullyApproved = $ctoRequest->status === 'approved';
        // DTR time-out verification is intentionally disabled for the current
        // manual Earn COC policy. HR approval proceeds using the filed hours.
        DB::transaction(function () use ($validated, $ctoRequest, $role, $employeeId, $isHR, $remarks, $wasFullyApproved) {
            $current = CtoRequest::query()->lockForUpdate()->findOrFail($ctoRequest->id);
            abort_unless($current->status === 'pending', 403);
            $ctoRequest->setRawAttributes($current->getAttributes(), true);
            if ($role === 'chief') {
                abort_unless($ctoRequest->hrstaff_status === 'approved' && $ctoRequest->chief_status === 'pending', 403);
            } elseif ($role === 'regionaldirector') {
                abort_unless($ctoRequest->hrstaff_status === 'approved' && $ctoRequest->chief_status === 'approved' && $ctoRequest->rd_status === 'pending', 403);
            } elseif ($isHR) {
                abort_unless($ctoRequest->hrstaff_status === 'pending', 403);
            }
            if ($validated['status'] === 'rejected') {
                if ($isHR) {
                    $ctoRequest->hrstaff_status = 'rejected';
                    $ctoRequest->approved_by_hrstaff = $employeeId;
                    $ctoRequest->hrstaff_remarks = $remarks;
                    $ctoRequest->status = 'rejected';

                    if ($ctoRequest->employee?->user) {
                        $ctoRequest->employee->user->notify(new CtoRequestNotification($ctoRequest, 'cto_rejected', 'Your CTO request was rejected during HR verification.'));
                    }
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

                    // Notify only the chief assigned to the employee's division.
                    $nextLevelUsers = $this->chiefUsersForEmployee($ctoRequest->employee);
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
                        $balanceOwner = $ctoRequest->employee()->lockForUpdate()->firstOrFail();
                        $currentBalance = (float) $balanceOwner->cto_balance;
                        $hours = (float) $ctoRequest->hours;
                        app(\App\Services\CocGrantService::class)->post($ctoRequest, $balanceOwner);
                        $newBalance = $ctoRequest->type === 'use'
                            ? max(0, $currentBalance - $hours)
                            : $currentBalance + $hours;

                        $balanceOwner->forceFill(['cto_balance' => $newBalance])->save();
                        $ctoRequest->cto_balance_after = $newBalance;
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

    public function destroy(CtoRequest $ctoRequest): RedirectResponse
    {
        abort_unless(in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'hrstaff', 'hr staff'], true), 403);

        $ctoRequest->delete();

        AuditLogListener::logActivity(
            'Archive CTO Request',
            'CTO',
            "Archived CTO request (#{$ctoRequest->id}) for " . trim(($ctoRequest->employee?->firstname ?? '') . ' ' . ($ctoRequest->employee?->lastname ?? '')),
            auth()->user()
        );

        return redirect()->back()->with('success', 'CTO request moved to Archive.');
    }

    private function currentChiefDivision(): string
    {
        return trim((string) auth()->user()?->employee?->division);
    }
}
