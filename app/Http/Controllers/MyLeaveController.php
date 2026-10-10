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
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $employee = auth()->user()->employee;
        if (! $employee) {
            abort(404, 'Employee record not found.');
        }

        $employee->ensureLeaveCredits(now()->year);
        $leaves = $employee->leaveRequests()->with(['leaveType', 'employee', 'chief', 'hrstaff', 'regionalDirector'])->latest()->get();
        $credits = $employee->leaveCredits()->with('leaveType')->get();

        return view('leaves.index', compact('leaves', 'credits'));
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

        return view('leaves.create', compact('leaveTypes', 'credits', 'leaveTypePicker', 'holidayDates'));
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

        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
            'is_paid' => 'required|boolean',
            'attachment' => 'nullable|file|mimes:pdf|max:5120', // Max 5MB
        ]);

        $leaveType = LeaveType::findOrFail($validated['leave_type_id']);
        $this->validateLeaveEligibilityAndDates($request, $employee, $leaveType);

            if ($request->hasFile('attachment')) {
                $path = $request->file('attachment')->store('leave-attachments', 'public');
                $validated['attachment_path'] = $path;
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
        $employee->ensureLeaveCredits(Carbon::parse($leaf->start_date)->year);
        $leaveCredit = $employee->leaveCredits()
            ->where('leave_type_id', $leaf->leave_type_id)
            ->where('year', Carbon::parse($leaf->start_date)->year)
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
        $leaf->employee?->ensureLeaveCredits(Carbon::parse($leaf->start_date)->year);

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

        if ($leaf->status !== 'pending') {
            return redirect()->route('leaves.index')->with('error', 'Only pending requests can be edited.');
        }

        $employee->loadMissing(['pdsPersonal', 'pdsQuestionnaire', 'pdsChildren']);
        $leaveTypes = $this->eligibleLeaveTypes($employee);
        $credits = $employee->leaveCredits->keyBy('leave_type_id');
        $leaveTypePicker = $this->leaveTypePickerData($leaveTypes, $credits);
        $holidayDates = $this->holidayDates();

        return view('leaves.edit', compact('leaf', 'leaveTypes', 'credits', 'leaveTypePicker', 'holidayDates'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, LeaveRequest $leaf): RedirectResponse
    {
        $employee = auth()->user()->employee;
        if ($leaf->employee_id !== $employee?->id || $leaf->status !== 'pending') {
            abort(403);
        }

        $validated = $request->validate([
            'leave_type_id' => 'required|exists:leave_types,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
            'is_paid' => 'required|boolean',
        ]);

        $leaveType = LeaveType::findOrFail($validated['leave_type_id']);
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

        $leaf->update([
            ...$validated,
            'is_paid' => $isPaid,
        ]);

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
}
