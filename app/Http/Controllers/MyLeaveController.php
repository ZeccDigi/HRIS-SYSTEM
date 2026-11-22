<?php

namespace App\Http\Controllers;

use App\Listeners\AuditLogListener;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Holiday;
use App\Models\User;
use App\Notifications\LeaveRequestNotification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Validator;
use Illuminate\View\View;

class MyLeaveController extends Controller
{
    public function creditHistory(Request $request): \Illuminate\Http\JsonResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee, 403);
        $data = $request->validate(['leave_type_id' => 'required|integer|exists:leave_types,id', 'year' => 'nullable|integer|min:2000|max:2100']);
        $type = LeaveType::findOrFail($data['leave_type_id']);
        abort_unless(in_array(strtolower(trim($type->name)), ['vacation leave', 'sick leave'], true), 404);
        $year = (int) ($data['year'] ?? now()->year);
        return response()->json(app(\App\Services\LeaveCreditHistoryService::class)->history($employee, $type->id, $year));
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $employee = auth()->user()->employee;
        if (! $employee) {
            abort(404, 'Employee record not found.');
        }

        $employee->ensureLeaveCredits(now()->year);
        $search = trim((string) $request->query('search', ''));
        $year = (string) $request->query('year', now()->year);
        $status = (string) $request->query('status', '');
        $month = (string) $request->query('month', '');
        $perPage = $this->perPage($request);

        $allLeaveTypes = $employee->leaveRequests()
            ->with('leaveType')
            ->get()
            ->map(fn (LeaveRequest $leave) => (string) \Illuminate\Support\Str::of($leave->leaveType?->name ?? '')
                ->replaceMatches('/\s+Leave\b/i', '')
                ->trim())
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $leaves = $employee->leaveRequests()
            ->where('is_monetization', $request->query('tab') === 'monetization')
            ->with(['leaveType', 'employee', 'chief', 'hrstaff', 'regionalDirector'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('status', 'like', "%{$search}%")
                        ->orWhere('date_filed', 'like', "%{$search}%")
                        ->orWhereHas('leaveType', fn ($leaveTypeQuery) => $leaveTypeQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($year !== '', fn ($query) => $query->whereYear('date_filed', $year))
            ->when((int) $month >= 1 && (int) $month <= 12, fn ($query) => $query->whereMonth('date_filed', (int) $month))
            ->when($status === 'cancelled', fn ($query) => $query->where('status', 'cancelled'))
            ->when($status === 'rejected', function ($query) {
                $query->where(function ($query) {
                    $query
                        ->where('status', 'rejected')
                        ->orWhere('hrstaff_status', 'rejected')
                        ->orWhere('chief_status', 'rejected')
                        ->orWhere('rd_status', 'rejected');
                });
            })
            ->when($status === 'approved', function ($query) {
                $query
                    ->where('status', '!=', 'cancelled')
                    ->where('hrstaff_status', 'approved')
                    ->where('chief_status', 'approved')
                    ->where('rd_status', 'approved');
            })
            ->when($status === 'pending', function ($query) {
                $query
                    ->whereNotIn('status', ['cancelled', 'rejected'])
                    ->where(function ($query) {
                        $query
                            ->whereNull('hrstaff_status')
                            ->orWhere('hrstaff_status', 'pending')
                            ->orWhereNull('chief_status')
                            ->orWhere('chief_status', 'pending')
                            ->orWhereNull('rd_status')
                            ->orWhere('rd_status', 'pending');
                    })
                    ->where(function ($query) {
                        $query
                            ->whereNull('hrstaff_status')
                            ->orWhere('hrstaff_status', '!=', 'rejected');
                    })
                    ->where(function ($query) {
                        $query
                            ->whereNull('chief_status')
                            ->orWhere('chief_status', '!=', 'rejected');
                    })
                    ->where(function ($query) {
                        $query
                            ->whereNull('rd_status')
                            ->orWhere('rd_status', '!=', 'rejected');
                    });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
        $credits = $employee->leaveCredits()->with('leaveType')->where('year', (int) $year)->get();
        $jobOrder = strtolower(trim((string) $employee->employment_status)) === 'job order';
        $creditCards = $credits->filter(function ($credit) use ($jobOrder) {
            $name = strtolower(trim((string) $credit->leaveType?->name));
            return $jobOrder ? $name === 'wellness leave'
                : in_array($name, ['vacation leave', 'sick leave', 'mandatory/force leave', 'special privilege leave', 'wellness leave'], true);
        });

        return view('leaves.index', [
            'leaves' => $leaves,
            'credits' => $credits,
            'creditCards' => $creditCards,
            'search' => $search,
            'year' => $year,
            'month' => $month,
            'status' => $status,
            'monetizationTab' => $request->query('tab') === 'monetization',
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $employee = auth()->user()->employee;
        $employee?->ensureLeaveCredits(now()->year);

        $employee?->loadMissing(['pdsPersonal', 'pdsQuestionnaire', 'pdsChildren']);
        $leaveTypes = $this->eligibleLeaveTypes($employee);
        $credits = $employee?->leaveCredits->keyBy('leave_type_id') ?? collect();
        $leaveTypePicker = $this->leaveTypePickerData($leaveTypes, $credits);
        $holidayDates = $this->holidayDates();

        $monetizationSalarySource = $employee ? app(\App\Services\SalaryScheduleService::class)->approvedSalary($employee) : null;
        return view('leaves.create', compact('leaveTypes', 'credits', 'leaveTypePicker', 'holidayDates', 'monetizationSalarySource'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse|Response
    {
        $employee = auth()->user()->employee;
        if (! $employee) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        }

        if ($request->boolean('monetization_requested')) return $this->saveMonetization($request, $employee);

        $leaveType = LeaveType::find($request->input('leave_type_id'));
        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_paid' => 'required|boolean',
            'leave_detail_type' => $this->leaveDetailTypeRules($leaveType),
            'leave_detail' => $this->leaveDetailTextRules($leaveType),
            'monetization_requested' => $this->monetizationRules($leaveType),
            'commutation_requested' => 'required|boolean',
            'attachment' => 'nullable|file|mimes:pdf|max:5120', // Max 5MB
        ]);

        $leaveType = LeaveType::findOrFail($validated['leave_type_id']);
        $this->normalizeLeaveDetails($validated, $leaveType);
        $this->normalizeReason($validated, $leaveType);
        $this->validateLeaveEligibilityAndDates($request, $employee, $leaveType);

            if ($request->hasFile('attachment')) {
                $path = $request->file('attachment')->store('leave-attachments', 'documents');
                $validated['attachment_path'] = $path;
                $validated['attachment_original_name'] = $request->file('attachment')->getClientOriginalName();
            }

        // Calculate duration and reserved credits (pending)
        $duration = LeaveRequest::calculateBusinessDays($validated['start_date'], $validated['end_date']);
        $leaveYear = Carbon::parse($validated['start_date'])->year;
        $employee->ensureLeaveCredits($leaveYear);

        $creditType = $this->creditTypeForBalance($leaveType);
        $creditLeaveTypeIds = $this->leaveTypeIdsUsingCredit($creditType);

        // Sum of days in pending requests that draw from the same credit source.
        $pendingDays = $employee->leaveRequests()
            ->whereIn('leave_type_id', $creditLeaveTypeIds)
            ->where('status', 'pending')
            ->get()
            ->sum(function ($req) {
                return $req->duration;
            });

        // Check credits (Warning only, filing is still allowed as unpaid).
        $credit = $employee->leaveCredits()->where('leave_type_id', $creditType->id)
            ->where('year', $leaveYear)
            ->first();

        $availableBalance = max(0, ($credit?->balance ?? 0) - $pendingDays);
        $typeAvailableBalance = $this->selectedTypeAvailableBalance($employee, $leaveType, $leaveYear, $duration, null);
        $isPaid = (bool) $validated['is_paid'] && $availableBalance >= $duration && $typeAvailableBalance >= $duration;

        $duplicatePendingLeave = $employee->leaveRequests()
            ->where('leave_type_id', $leaveType->id)
            ->whereDate('start_date', $validated['start_date'])
            ->whereDate('end_date', $validated['end_date'])
            ->where('reason', $validated['reason'])
            ->where('leave_detail_type', $validated['leave_detail_type'] ?? null)
            ->where('leave_detail', $validated['leave_detail'] ?? null)
            ->where('commutation_requested', $validated['commutation_requested'])
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($duplicatePendingLeave) {
            $successMessage = 'Leave request submitted successfully. Credits will be deducted once approved.';

            if ($request->boolean('modal')) {
                session()->flash('success', $successMessage);

                return response()->view('leaves.modal-success-redirect', [
                    'url' => route('leaves.index'),
                    'message' => $successMessage,
                ]);
            }

            return redirect()->route('leaves.index')->with('success', $successMessage);
        }
        
        // Deduction only happens on Approval. We allow filing even if credits are insufficient.
        $leaveRequest = $employee->leaveRequests()->create([
            ...$validated,
            'is_paid' => $isPaid,
            'date_filed' => now(),
        ]);

        $msg = 'Leave request submitted successfully.';
        if ((bool) $validated['is_paid'] && ! $isPaid) {
            $msg .= ' The selected leave was automatically marked as without pay because the available credit is insufficient.';
        }

        // Notify HR first for leave review.
        $hrUsers = User::whereHas('employee', function ($query) {
            $query->whereIn('account_role', ['hrstaff', 'admin']);
        })->get();

        Notification::send($hrUsers, new LeaveRequestNotification($leaveRequest));

        AuditLogListener::logActivity(
            'Submit Leave Application',
            'Leaves',
            "Submitted {$leaveType->name} leave application from {$validated['start_date']} to {$validated['end_date']}",
            auth()->user()
        );

        $successMessage = $msg.' Credits will be deducted once approved.';

        if ($request->boolean('modal')) {
            session()->flash('success', $successMessage);

            return response()->view('leaves.modal-success-redirect', [
                'url' => route('leaves.index'),
                'message' => $successMessage,
            ]);
        }

        return redirect()->route('leaves.index')->with('success', $successMessage);
    }

    /**
     * Display the specified resource.
     */
    public function show(LeaveRequest $leaf): View
    {
        $employee = auth()->user()->employee;
        if ($leaf->employee_id !== $employee?->id) {
            abort(403);
        }

        $leaf->load(['leaveType', 'chief', 'hrstaff', 'regionalDirector']);
        $employee->ensureLeaveCredits(Carbon::parse($leaf->start_date ?? $leaf->date_filed)->year);
        $leaveCredit = $employee->leaveCredits()
            ->where('leave_type_id', $leaf->leave_type_id)
            ->where('year', Carbon::parse($leaf->start_date ?? $leaf->date_filed)->year)
            ->first();

        return view('leaves.show', compact('leaf', 'leaveCredit'));
    }

    public function print(LeaveRequest $leaf): View
    {
        $employee = auth()->user()->employee;
        if ($leaf->employee_id !== $employee?->id) {
            abort(403);
        }

        $leaf->load(['employee.user', 'employee.leaveCredits.leaveType', 'employee.pdsGovId', 'leaveType', 'chief', 'hrstaff', 'regionalDirector']);
        $leaf->employee?->ensureLeaveCredits(Carbon::parse($leaf->start_date ?? $leaf->date_filed)->year);

        return view('leaves.print', ['leaveRequest' => $leaf]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(LeaveRequest $leaf): View|RedirectResponse
    {
        $employee = auth()->user()->employee;
        if ($leaf->employee_id !== $employee?->id) {
            abort(403);
        }

        abort_unless($leaf->canBeEditedBy($employee), 403);

        $employee->loadMissing(['pdsPersonal', 'pdsQuestionnaire', 'pdsChildren']);
        $leaveTypes = $this->eligibleLeaveTypes($employee);
        $credits = $employee->leaveCredits->keyBy('leave_type_id');
        $leaveTypePicker = $this->leaveTypePickerData($leaveTypes, $credits);
        $holidayDates = $this->holidayDates();

        $monetizationSalarySource = app(\App\Services\SalaryScheduleService::class)->approvedSalary($employee);
        return view('leaves.edit', compact('leaf', 'leaveTypes', 'credits', 'leaveTypePicker', 'holidayDates', 'monetizationSalarySource'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LeaveRequest $leaf): RedirectResponse|Response
    {
        $employee = auth()->user()->employee;
        abort_unless($leaf->canBeEditedBy($employee), 403);

        if ($request->boolean('monetization_requested')) return $this->saveMonetization($request, $employee, $leaf);

        $leaveType = LeaveType::find($request->input('leave_type_id'));
        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'is_paid' => 'required|boolean',
            'leave_detail_type' => $this->leaveDetailTypeRules($leaveType),
            'leave_detail' => $this->leaveDetailTextRules($leaveType),
            'monetization_requested' => $this->monetizationRules($leaveType),
            'commutation_requested' => 'required|boolean',
            'attachment' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        $leaveType = LeaveType::findOrFail($validated['leave_type_id']);
        $this->normalizeLeaveDetails($validated, $leaveType);
        $this->normalizeReason($validated, $leaveType);
        $validated['is_monetization'] = false;
        foreach (['monetization_basis', 'monetization_days', 'monetization_salary', 'monetization_salary_grade', 'monetization_salary_step', 'monetization_salary_year', 'monetization_amount', 'monetization_factor', 'monetization_justification', 'monetization_credit_before', 'monetization_credit_after'] as $field) $validated[$field] = null;
        $this->validateLeaveEligibilityAndDates($request, $employee, $leaveType);
        $duration = LeaveRequest::calculateBusinessDays($validated['start_date'], $validated['end_date']);
        $leaveYear = Carbon::parse($validated['start_date'])->year;
        $employee->ensureLeaveCredits($leaveYear);

        $creditType = $this->creditTypeForBalance($leaveType);
        $creditLeaveTypeIds = $this->leaveTypeIdsUsingCredit($creditType);

        $pendingDays = $employee->leaveRequests()
            ->whereIn('leave_type_id', $creditLeaveTypeIds)
            ->where('status', 'pending')
            ->whereKeyNot($leaf->id)
            ->get()
            ->sum(fn ($request) => $request->duration);

        $credit = $employee->leaveCredits()
            ->where('leave_type_id', $creditType->id)
            ->where('year', $leaveYear)
            ->first();

        $availableBalance = max(0, ($credit?->balance ?? 0) - $pendingDays);
        $typeAvailableBalance = $this->selectedTypeAvailableBalance($employee, $leaveType, $leaveYear, $duration, $leaf->id);
        $isPaid = (bool) $validated['is_paid'] && $availableBalance >= $duration && $typeAvailableBalance >= $duration;

        if ($request->hasFile('attachment')) {
            $validated['attachment_path'] = $request->file('attachment')->store('leave-attachments', 'documents');
            $validated['attachment_original_name'] = $request->file('attachment')->getClientOriginalName();
        }
        unset($validated['attachment']);

        \Illuminate\Support\Facades\DB::transaction(function () use ($leaf, $employee, $validated, $isPaid) {
            $current = LeaveRequest::query()->lockForUpdate()->findOrFail($leaf->id);
            abort_unless($current->canBeEditedBy($employee), 403);
            $current->update([...$validated, 'is_paid' => $isPaid]);
        });

        AuditLogListener::logActivity(
            'Update Leave Application',
            'Leaves',
            "Updated {$leaveType->name} leave application (#{$leaf->id})",
            auth()->user()
        );

        $message = 'Leave request updated successfully.';
        if ((bool) $validated['is_paid'] && ! $isPaid) {
            $message .= ' It was automatically marked as without pay because the available credit is insufficient.';
        }

        return redirect()->route('leaves.index')->with('success', $message);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(LeaveRequest $leaf): RedirectResponse
    {
        $employee = auth()->user()->employee;
        if ($leaf->employee_id !== $employee?->id) {
            abort(403);
        }

        // Standard: only pending can be deleted/cancelled
        if ($leaf->status !== 'pending') {
            return redirect()->route('leaves.index')->with('error', 'Only pending requests can be cancelled.');
        }

        $leaf->delete();

        AuditLogListener::logActivity(
            'Cancel Leave Application',
            'Leaves',
            "Cancelled leave application (#{$leaf->id})",
            auth()->user()
        );

        return redirect()->route('leaves.index')->with('success', 'Leave request cancelled successfully.');
    }

    private function leaveDetailTypeRules(?LeaveType $leaveType): string
    {
        if (! $leaveType) {
            return 'nullable|string|max:50';
        }

        $name = strtolower($leaveType->name);

        if (str_contains($name, 'vacation') || str_contains($name, 'special privilege')) {
            return 'required|in:within_philippines,abroad';
        }

        if (str_contains($name, 'sick')) {
            return 'required|in:in_hospital,out_patient';
        }

        if (str_contains($name, 'women')) {
            return 'required|in:women_illness';
        }

        if (str_contains($name, 'study')) {
            return 'required|in:masters_degree,board_review';
        }

        return 'nullable|string|max:50';
    }

    private function leaveDetailTextRules(?LeaveType $leaveType): string
    {
        if (! $leaveType) {
            return 'nullable|string|max:1000';
        }

        $name = strtolower($leaveType->name);
        $needsDetails = str_contains($name, 'vacation')
            || str_contains($name, 'special privilege')
            || str_contains($name, 'sick')
            || str_contains($name, 'women');

        return $needsDetails ? 'required|string|max:1000' : 'nullable|string|max:1000';
    }

    private function saveMonetization(Request $request, \App\Models\Employee $employee, ?LeaveRequest $existing = null): RedirectResponse|Response
    {
        $salary = app(\App\Services\SalaryScheduleService::class)->approvedSalary($employee);
        if (!$salary) throw \Illuminate\Validation\ValidationException::withMessages(['monetization_salary' => 'No salary recorded in the PDS']);
        // Resolve again on submission; never accept a salary supplied by the browser.
        $request->merge(['monetization_salary' => $salary['amount']]);
        $data = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'monetization_basis' => 'required|in:regular,exceptional',
            'monetization_days' => 'required|numeric|decimal:0,3|min:0.001|max:9999',
            'monetization_salary' => 'required|numeric|decimal:0,2|gt:0|max:10000000',

            'attachment' => ($existing?->attachment_path ? 'nullable' : 'required_if:monetization_basis,exceptional').'|file|mimes:pdf|max:5120',
        ]);
        $data = [...$data, 'monetization_salary_grade' => $salary['grade'], 'monetization_salary_step' => $salary['step'], 'monetization_salary_year' => $salary['year']];
        $type = LeaveType::findOrFail($data['leave_type_id']);
        if (!$this->eligibleLeaveTypes($employee)->contains('id', $type->id)) throw \Illuminate\Validation\ValidationException::withMessages(['leave_type_id' => 'You are not eligible for this leave-credit type.']);
        $leave = \Illuminate\Support\Facades\DB::transaction(function () use ($request, $employee, $existing, $type, $data) {
            $employee = \App\Models\Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
            $current = $existing ? LeaveRequest::whereKey($existing->id)->lockForUpdate()->firstOrFail() : null;
            if ($current) abort_unless($current->canBeEditedBy($employee), 403);
            if (!$current) {
                $duplicate = $employee->leaveRequests()->where('is_monetization', true)->where('status', 'pending')
                    ->where('leave_type_id', $type->id)->where('monetization_basis', $data['monetization_basis'])
                    ->where('monetization_days', $data['monetization_days'])->where('monetization_salary', $data['monetization_salary'])->first();
                if ($duplicate) return $duplicate;
            }
            $service = app(\App\Services\LeaveMonetizationService::class);
            $year = $current?->date_filed?->year ?? now()->year;
            $available = $service->validateCredits($employee, $type, $data, $year, $current?->id);
            $values = collect($data)->except('attachment')->all();
            $values = [...$values, 'is_monetization' => true, 'start_date' => null, 'end_date' => null,
                'leave_detail_type' => null, 'leave_detail' => null, 'reason' => 'Monetization of Leave Credits',
                'is_paid' => true, 'commutation_requested' => true,
                'monetization_factor' => \App\Services\LeaveMonetizationService::FACTOR, 'monetization_justification' => null,
                'monetization_amount' => $service->amount((float) $data['monetization_salary'], (float) $data['monetization_days']),
                'monetization_credit_before' => $available, 'monetization_credit_after' => round($available - (float) $data['monetization_days'], 3)];
            if ($request->hasFile('attachment')) {
                $values['attachment_path'] = $request->file('attachment')->store('leave-attachments', 'documents');
                $values['attachment_original_name'] = $request->file('attachment')->getClientOriginalName();
            }
            if ($current) { $current->update($values); return $current; }
            return $employee->leaveRequests()->create([...$values, 'date_filed' => now()]);
        });
        if (!$existing && $leave->wasRecentlyCreated) {
            $hrUsers = User::whereHas('employee', fn ($query) => $query->whereIn('account_role', ['hrstaff', 'admin']))->get();
            Notification::send($hrUsers, new LeaveRequestNotification($leave));
        }
        AuditLogListener::logActivity($existing ? 'Update Monetization Request' : 'Submit Monetization Request', 'Leaves', 'Monetization request #'.$leave->id.' for '.$leave->monetization_days.' '.$type->name.' days', auth()->user());
        $message = 'Monetization request '.($existing ? 'updated' : 'submitted').'. Credits are deducted only after final approval.';
        $url = route('leaves.index', ['tab' => 'monetization']);
        if ($request->boolean('modal')) {
            session()->flash('success', $message);
            return response()->view('leaves.modal-success-redirect', ['url' => $url, 'message' => $message]);
        }
        return redirect($url)->with('success', $message);
    }

    private function monetizationRules(?LeaveType $leaveType): string
    {
        if (! $leaveType) {
            return 'nullable|boolean';
        }

        $name = strtolower($leaveType->name);

        return str_contains($name, 'vacation') || str_contains($name, 'sick')
            ? 'nullable|boolean'
            : 'prohibited';
    }

    private function normalizeReason(array &$validated, LeaveType $leaveType): void
    {
        $isMonetization = (bool) ($validated['monetization_requested'] ?? false);
        unset($validated['monetization_requested']);

        $validated['reason'] = $isMonetization
            ? 'Monetization of Leave Credits'
            : (trim((string) ($validated['leave_detail'] ?? '')) ?: $leaveType->name);
    }

    private function normalizeLeaveDetails(array &$validated, LeaveType $leaveType): void
    {
        $name = strtolower($leaveType->name);
        $hasStructuredDetails = str_contains($name, 'vacation')
            || str_contains($name, 'special privilege')
            || str_contains($name, 'sick')
            || str_contains($name, 'women')
            || str_contains($name, 'study');

        if (! $hasStructuredDetails) {
            $validated['leave_detail_type'] = null;
            $validated['leave_detail'] = null;
        } elseif (str_contains($name, 'study')) {
            $validated['leave_detail'] = null;
        }
    }

    private function validateLeaveEligibilityAndDates(Request $request, $employee, LeaveType $leaveType): void
    {
        validator($request->only(['leave_type_id', 'start_date', 'end_date']), [])->after(function (Validator $validator) use ($request, $employee, $leaveType) {
            if (! $this->isLeaveTypeEligibleForEmployee($leaveType, $employee)) {
                $validator->errors()->add('leave_type_id', 'This leave type is not available for your employee profile.');
            }

            foreach (['start_date' => 'Start date', 'end_date' => 'End date'] as $field => $label) {
                $value = $request->input($field);

                if ($value && Carbon::parse($value)->isWeekend()) {
                    $validator->errors()->add($field, "{$label} must be a weekday.");
                }

                if ($value && Holiday::whereDate('date', Carbon::parse($value)->toDateString())->exists()) {
                    $validator->errors()->add($field, "{$label} cannot fall on a holiday.");
                }
            }

            $policy = $this->datePolicyForLeaveType($leaveType);
            foreach (['start_date' => 'Start date', 'end_date' => 'End date'] as $field => $label) {
                $value = $request->input($field);
                if (! $value) {
                    continue;
                }

                $date = Carbon::parse($value)->startOfDay();
                $today = today();

                if ($policy === 'past_or_today' && $date->gt($today)) {
                    $validator->errors()->add($field, "{$label} cannot be a future date for {$leaveType->name}.");
                }

                if ($policy === 'today_or_future' && $date->lt($today)) {
                    $validator->errors()->add($field, "{$label} cannot be a past date for {$leaveType->name}.");
                }
            }
        })->validate();
    }

    private function eligibleLeaveTypes($employee): Collection
    {
        $types = LeaveType::where('is_active', true)->get();

        if (! $employee) {
            return $types->filter(fn (LeaveType $type) => $this->isGeneralLeaveType($type))->values();
        }

        return $types->filter(fn (LeaveType $type) => $this->isLeaveTypeEligibleForEmployee($type, $employee))->values();
    }

    private function isLeaveTypeEligibleForEmployee(LeaveType $type, $employee): bool
    {
        $name = $type->name;
        $employmentStatus = strtolower(trim((string) ($employee?->employment_status ?? '')));

        // Agency policy: Job Order personnel may avail only Wellness Leave.
        if ($employmentStatus === 'job order') {
            return $name === 'Wellness Leave';
        }

        $sex = strtolower((string) ($employee?->pdsPersonal?->sex ?? ''));
        $civilStatus = strtolower((string) ($employee?->pdsPersonal?->civil_status ?? ''));
        $soloParentAnswer = $employee?->pdsQuestionnaire?->q40_c ?? false;
        $isMarkedSoloParent = in_array($soloParentAnswer, [true, 1, '1', 'yes', 'Yes'], true);
        $hasChildren = $employee?->relationLoaded('pdsChildren')
            ? $employee->pdsChildren->isNotEmpty()
            : (bool) $employee?->pdsChildren()->exists();
        $isSoloParent = $isMarkedSoloParent
            || ($hasChildren && in_array($civilStatus, ['single', 'separated', 'widowed', 'widow'], true));

        if (in_array($name, ['Maternity Leave', 'VAWC Leave', 'Special Leave Benefits for Women'], true)) {
            return $sex === 'female';
        }

        if ($name === 'Paternity Leave') {
            return $sex === 'male';
        }

        if ($name === 'Solo Parent Leave') {
            return $isSoloParent;
        }

        return true;
    }

    private function isGeneralLeaveType(LeaveType $type): bool
    {
        return ! in_array($type->name, [
            'Maternity Leave',
            'Paternity Leave',
            'Solo Parent Leave',
            'Adoption Leave',
            'VAWC Leave',
            'Special Leave Benefits for Women',
        ], true);
    }

    private function leaveTypePickerData(Collection $leaveTypes, $credits)
    {
        return $leaveTypes->keyBy('id')->map(function (LeaveType $type) use ($credits) {
            return [
                'name' => $type->name,
                'description' => $type->description,
                'legal_basis' => $type->legal_basis,
                'balance' => (float) ($credits[$type->id]->balance ?? 0),
                'credit_note' => $this->creditNoteForLeaveType($type),
                'date_policy' => $this->datePolicyForLeaveType($type),
                'date_help' => '',
            ];
        });
    }

    private function selectedTypeAvailableBalance($employee, LeaveType $leaveType, int $year, float $duration, ?int $exceptRequestId): float
    {
        $creditType = $this->creditTypeForBalance($leaveType);

        if ($creditType->id === $leaveType->id) {
            return $duration;
        }

        $pendingQuery = $employee->leaveRequests()
            ->where('leave_type_id', $leaveType->id)
            ->where('status', 'pending');

        if ($exceptRequestId) {
            $pendingQuery->whereKeyNot($exceptRequestId);
        }

        $pendingDays = $pendingQuery->get()->sum(fn ($request) => $request->duration);
        $credit = $employee->leaveCredits()
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->first();

        return max(0, ($credit?->balance ?? 0) - $pendingDays);
    }

    private function creditTypeForBalance(LeaveType $type): LeaveType
    {
        if (! $this->isMandatoryForceLeave($type)) {
            return $type;
        }

        return LeaveType::where('name', 'Vacation Leave')->first() ?? $type;
    }

    private function leaveTypeIdsUsingCredit(LeaveType $creditType): array
    {
        if (strtolower(trim($creditType->name)) !== 'vacation leave') {
            return [$creditType->id];
        }

        return LeaveType::whereIn('name', ['Vacation Leave', 'Mandatory/Force Leave'])
            ->pluck('id')
            ->all();
    }

    private function creditNoteForLeaveType(LeaveType $type): string
    {
        return $this->isMandatoryForceLeave($type)
            ? 'Mandatory/Force Leave is charged against Vacation Leave credits.'
            : '';
    }

    private function isMandatoryForceLeave(LeaveType $type): bool
    {
        return strtolower(trim($type->name)) === 'mandatory/force leave';
    }

    private function datePolicyForLeaveType(LeaveType $type): string
    {
        if (in_array($type->name, ['Sick Leave', 'Rehabilitation Leave', 'Special Emergency (Calamity) Leave'], true)) {
            return 'past_or_today';
        }

        if (in_array($type->name, ['Special Privilege Leave', 'VAWC Leave', 'Special Leave Benefits for Women'], true)) {
            return 'any_weekday';
        }

        return 'today_or_future';
    }

    private function holidayDates(): array
    {
        return Holiday::orderBy('date')
            ->get()
            ->map(fn (Holiday $holiday) => $holiday->date->format('Y-m-d'))
            ->values()
            ->all();
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 5);

        return in_array($perPage, [5, 10, 20, 50], true) ? $perPage : 5;
    }
}
