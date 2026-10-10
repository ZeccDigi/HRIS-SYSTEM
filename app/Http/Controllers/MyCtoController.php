<?php

namespace App\Http\Controllers;

use App\Listeners\AuditLogListener;
use App\Models\User;
use App\Notifications\CtoRequestNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use App\Models\CtoRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MyCtoController extends Controller
{
    public function index(): View
    {
        $employee = auth()->user()->employee;
        if (! $employee) {
            abort(404, 'Employee record not found.');
        }

        $ctoRequests = $employee->ctoRequests()->with(['chief', 'hrstaff', 'regionalDirector'])->latest()->get();

        return view('my-cto.index', compact('employee', 'ctoRequests'));
    }

    public function create(): View
    {
        $employee = auth()->user()->employee;
        if (! $employee) {
            abort(404, 'Employee record not found.');
        }

        return view('my-cto.create', compact('employee'));
    }

    public function store(Request $request): RedirectResponse|Response
    {
        $employee = auth()->user()->employee;
        if (! $employee) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        }

        $validated = $request->validate([
            'type' => 'required|in:earn,use',
            'date_start' => 'required|date',
            'date_end' => 'required|date|after_or_equal:date_start',
            'hours' => 'required|numeric|min:0.5|max:999',
            'purpose' => 'required|string',
            'attachment' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        $this->validateCtoDates($validated['date_start'], $validated['date_end']);

        if ($validated['type'] === 'use' && $employee->cto_balance < $validated['hours']) {
            return back()->withInput()->withErrors([
                'hours' => 'Insufficient CTO balance. Available: '.$employee->cto_balance.' hour(s).',
            ]);
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('cto-attachments', 'public');
        }

        $ctoBalanceBefore = (float) $employee->cto_balance;
        $ctoBalanceAfter = $validated['type'] === 'use'
            ? max(0, $ctoBalanceBefore - (float) $validated['hours'])
            : $ctoBalanceBefore + (float) $validated['hours'];

        $duplicatePendingCto = $employee->ctoRequests()
            ->where('type', $validated['type'])
            ->whereDate('date_start', $validated['date_start'])
            ->whereDate('date_end', $validated['date_end'])
            ->where('hours', $validated['hours'])
            ->where('purpose', $validated['purpose'])
            ->where('status', 'pending')
            ->latest()
            ->first();

        if ($duplicatePendingCto) {
            $successMessage = 'CTO request submitted successfully.';

            if ($request->boolean('modal')) {
                session()->flash('success', $successMessage);

                return response()->view('leaves.modal-success-redirect', [
                    'url' => route('my-cto.index'),
                    'message' => $successMessage,
                ]);
            }

            return redirect()->route('my-cto.index')->with('success', $successMessage);
        }

        $ctoRequest = $employee->ctoRequests()->create([
            'type' => $validated['type'],
            'date_start' => $validated['date_start'],
            'date_end' => $validated['date_end'],
            'hours' => $validated['hours'],
            'purpose' => $validated['purpose'],
            'attachment_path' => $attachmentPath,
            'cto_balance_before' => $ctoBalanceBefore,
            'cto_balance_after' => $ctoBalanceAfter,
        ]);

        $hrUsers = User::whereHas('employee', function ($query) {
            $query->whereIn('account_role', ['hrstaff', 'admin']);
        })->get();

        $employeeName = trim($employee->firstname . ' ' . $employee->lastname);

        Notification::send($hrUsers, new CtoRequestNotification(
            $ctoRequest,
            'New CTO Request',
            "{$employeeName} submitted a CTO request for {$ctoRequest->hours} hour(s)."
        ));

        AuditLogListener::logActivity(
            'Submit CTO Application',
            'Leaves',
            "Submitted CTO application ({$ctoRequest->type}) for {$ctoRequest->hours} hour(s) from {$ctoRequest->date_start} to {$ctoRequest->date_end}",
            auth()->user()
        );

        $successMessage = 'CTO request submitted successfully.';

        if ($request->boolean('modal')) {
            session()->flash('success', $successMessage);

            return response()->view('leaves.modal-success-redirect', [
                'url' => route('my-cto.index'),
                'message' => $successMessage,
            ]);
        }

        return redirect()->route('my-cto.index')->with('success', $successMessage);
    }

    public function show(CtoRequest $ctoRequest): View
    {
        $employee = auth()->user()->employee;

        if ($ctoRequest->employee_id !== $employee?->id
            && ! in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'hrstaff', 'hr staff', 'chief', 'regionaldirector'])) {
            abort(403);
        }

        $ctoRequest->load(['employee', 'chief', 'hrstaff', 'regionalDirector']);

        return view('my-cto.show', compact('ctoRequest'));
    }

    public function print(CtoRequest $ctoRequest): View
    {
        $employee = auth()->user()->employee;

        if ($ctoRequest->employee_id !== $employee?->id
            && ! in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'hrstaff', 'hr staff', 'chief', 'regionaldirector'])) {
            abort(403);
        }

        $ctoRequest->load(['employee.user', 'chief', 'hrstaff', 'regionalDirector']);

        return view('my-cto.print', compact('ctoRequest'));
    }

    private function validateCtoDates(string $startDate, string $endDate): void
    {
        $today = now()->startOfDay();
        $dates = [
            'date_start' => Carbon::parse($startDate)->startOfDay(),
            'date_end' => Carbon::parse($endDate)->startOfDay(),
        ];

        foreach ($dates as $field => $date) {
            if ($date->lt($today) || $date->isWeekend()) {
                throw ValidationException::withMessages([
                    $field => 'Please select today or a future weekday. Saturdays and Sundays are disabled.',
                ]);
            }
        }
    }
}
