<?php

namespace App\Http\Controllers;

use App\Listeners\AuditLogListener;
use App\Models\Employee;
use App\Models\User;
use App\Notifications\CtoRequestNotification;
use App\Services\CocOvertimeVerificationService;
use App\Services\CtoPdfExporter;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
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
    public function quoteCoc(Request $request): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate(['date' => 'required|date|before_or_equal:today', 'hours' => 'required|numeric|min:2|max:999999.99', 'employee_id' => 'nullable|integer']);
        $employee = auth()->user()->employee;
        if (!empty($data['employee_id']) && (int) $data['employee_id'] !== (int) $employee?->id) {
            abort_unless(in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'hrstaff', 'hr staff'], true), 403);
            $employee = Employee::findOrFail($data['employee_id']);
        }
        abort_unless($employee, 403);
        return response()->json(app(\App\Services\CocEntitlementService::class)->calculate($employee, $data['date'], (float) $data['hours']));
    }
    public function index(Request $request): View
    {
        $employee = auth()->user()->employee;
        if (! $employee) {
            abort(404, 'Employee record not found.');
        }

        $search = trim((string) $request->query('search', ''));
        $year = (string) $request->query('year', now()->year);
        $month = (string) $request->query('month', '');
        $status = (string) $request->query('status', '');
        $perPage = $this->perPage($request);

        $ctoRequests = $employee->ctoRequests()
            ->where(fn ($query) => $query->whereNull('credit_source')->orWhere('credit_source', '!=', 'hr_direct'))
            ->with(['chief', 'hrstaff', 'regionalDirector'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('type', 'like', "%{$search}%")
                        ->orWhere('purpose', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('date_start', 'like', "%{$search}%");
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($year !== '', fn ($query) => $query->whereYear('created_at', $year))
            ->when((int) $month >= 1 && (int) $month <= 12, fn ($query) => $query->whereMonth('date_start', (int) $month))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        $approved = $employee->ctoRequests()->where('status', 'approved')->get(['type', 'hours']);
        $earnedCtoHours = (float) $approved->where('type', 'earn')->sum('hours');
        $usedCtoHours = (float) $approved->where('type', 'use')->sum('hours');
        return view('my-cto.index', compact('employee', 'ctoRequests', 'search', 'year', 'month', 'status', 'earnedCtoHours', 'usedCtoHours'));
    }

    public function create(): View
    {
        $employee = auth()->user()->employee;
        if (! $employee) {
            abort(404, 'Employee record not found.');
        }

        return view('my-cto.create', compact('employee'));
    }

    public function overtimeDetails(Request $request, CocOvertimeVerificationService $cocOvertime): JsonResponse
    {
        $employee = auth()->user()->employee;
        abort_unless($employee, 404, 'Employee record not found.');

        $validated = $request->validate(['date' => ['required', 'date']]);

        return response()->json($cocOvertime->forEmployeeDate($employee, $validated['date']));
    }

    public function edit(Request $request, CtoRequest $ctoRequest): View
    {
        abort_unless($ctoRequest->canBeEditedBy($request->user()->employee), 403);
        $employee = $request->user()->employee;

        return view('my-cto.create', compact('ctoRequest', 'employee'));
    }

    public function update(Request $request, CtoRequest $ctoRequest): RedirectResponse|Response
    {
        abort_unless($ctoRequest->canBeEditedBy($request->user()->employee), 403);

        return $this->saveRequest($request, $ctoRequest);
    }

    public function store(Request $request): RedirectResponse|Response
    {
        return $this->saveRequest($request);
    }

    private function saveRequest(Request $request, ?CtoRequest $existing = null): RedirectResponse|Response
    {
        $employee = auth()->user()->employee;
        if (! $employee) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        }

        $validated = $request->validate([
            'type' => 'required|in:earn,use',
            'date_start' => 'required|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
            'hours' => ['required', 'numeric', $request->input('type') === 'earn' ? 'min:2' : 'min:0.5', 'max:999'],
            'purpose' => ['nullable', 'string', 'max:2000'],
            'overtime_authority_reference' => 'nullable|string|max:255',
            'attachment' => [($request->input('type') === 'earn' && !$existing?->attachment_path) ? 'required' : 'nullable', 'file', 'mimes:pdf', 'max:5120'],
        ]);

        $validated['purpose'] = trim((string) ($validated['purpose'] ?? ''));
        if ($validated['type'] === 'earn') {
            $validated = array_merge($validated, app(\App\Services\CocEntitlementService::class)->calculate($employee, $validated['date_start'], (float) $validated['hours']));
            $validated['purpose'] = $existing?->purpose ?? '';
            $overtimeDate = Carbon::parse($validated['date_start'])->startOfDay();

            if ($overtimeDate->gt(now()->startOfDay())) {
                throw ValidationException::withMessages([
                    'date_start' => 'Choose the date on which overtime was actually rendered.',
                ]);
            }

            $validated['date_end'] = $validated['date_start'];
            // DTR time-out verification is intentionally disabled for the
            // current manual Earn COC process. The employee supplies the
            // rendered hours and the approval chain authorizes crediting.
            if (! isset($validated['hours'])) {
                throw ValidationException::withMessages(['hours' => 'Enter the number of hours rendered.']);
            }
        } else {
            if (blank($validated['date_end'] ?? null)) {
                $validated['date_end'] = $validated['date_start'];
            }

            $this->validateCtoDates($validated['date_start'], $validated['date_end']);

            if (! isset($validated['hours'])) {
                throw ValidationException::withMessages(['hours' => 'Enter the number of CTO hours to use.']);
            }
        }

        if ($validated['type'] === 'use' && $employee->cto_balance < $validated['hours']) {
            return back()->withInput()->withErrors([
                'hours' => 'Insufficient CTO balance. Available: '.$employee->cto_balance.' hour(s).',
            ]);
        }

        if ($existing) {
            DB::transaction(function () use ($existing, $employee, $validated, $request) {
                $current = CtoRequest::query()->lockForUpdate()->findOrFail($existing->id);
                abort_unless($current->canBeEditedBy($employee), 403);
                $current->update([
                    'type' => $validated['type'] ?? null,
                    'date_start' => $validated['date_start'] ?? null,
                    'date_end' => $validated['date_end'] ?? null,
                    'hours' => $validated['hours'] ?? null,
                    'rendered_hours' => $validated['rendered_hours'] ?? null,
                    'coc_rate' => $validated['coc_rate'] ?? null,
                    'overtime_classification' => $validated['overtime_classification'] ?? null,
                    'purpose' => $validated['purpose'] ?? null,
                    'overtime_authority_reference' => $validated['type'] === 'earn' ? ($validated['overtime_authority_reference'] ?? null) : null,
                    'cto_balance_before' => $employee->cto_balance,
                    'cto_balance_after' => $validated['type'] === 'use' ? (float) $employee->cto_balance - (float) $validated['hours'] : null,
                    'attachment_path' => $request->file('attachment')?->store('cto-attachments', 'documents') ?? $current->attachment_path,
                    'attachment_original_name' => $request->file('attachment')?->getClientOriginalName() ?? $current->attachment_original_name,
                ]);
            });

            return redirect()->route('my-cto.show', $existing)->with('success', 'Request updated successfully.');
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('cto-attachments', 'documents');
        }

        $ctoBalanceBefore = (float) $employee->cto_balance;
        $ctoBalanceAfter = $validated['type'] === 'use'
            ? max(0, $ctoBalanceBefore - (float) $validated['hours'])
            : null;

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
            'credit_source' => $validated['type'] === 'earn' ? 'employee_request' : null,
            'recorded_by' => auth()->id(),
            'type' => $validated['type'],
            'date_start' => $validated['date_start'],
            'date_end' => $validated['date_end'],
            'hours' => $validated['hours'],
            'rendered_hours' => $validated['rendered_hours'] ?? null,
            'coc_rate' => $validated['coc_rate'] ?? null,
            'overtime_classification' => $validated['overtime_classification'] ?? null,
            'purpose' => $validated['purpose'],
            'overtime_authority_reference' => $validated['type'] === 'earn'
                ? ($validated['overtime_authority_reference'] ?? null)
                : null,
            'exception_justification' => null,
            'attachment_path' => $attachmentPath,
            'attachment_original_name' => $request->file('attachment')?->getClientOriginalName(),
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

        $ctoRequest->load(['employee.user', 'chief', 'hrstaff', 'regionalDirector', 'recordedBy.employee']);

        if ($ctoRequest->employee_id !== $employee?->id
            && strtolower(auth()->user()->role ?? '') === 'chief'
            && ! $this->chiefCanReviewEmployee($ctoRequest->employee)) {
            abort(403);
        }

        return view('my-cto.show', compact('ctoRequest'));
    }

    public function print(Request $request, CtoRequest $ctoRequest, CtoPdfExporter $exporter): View|Response
    {
        $employee = auth()->user()->employee;

        if ($ctoRequest->employee_id !== $employee?->id
            && ! in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'hrstaff', 'hr staff', 'chief', 'regionaldirector'])) {
            abort(403);
        }

        $ctoRequest->load(['employee.user', 'chief', 'hrstaff', 'regionalDirector']);

        if ($ctoRequest->employee_id !== $employee?->id
            && strtolower(auth()->user()->role ?? '') === 'chief'
            && ! $this->chiefCanReviewEmployee($ctoRequest->employee)) {
            abort(403);
        }

        if ($request->boolean('preview')) {
            return view($ctoRequest->type === 'earn' ? 'my-cto.print-coc-certificate' : 'my-cto.print-availment-new', [
                'ctoRequest' => $ctoRequest,
                'isPrintDocument' => false,
                'dmwLogoPath' => asset('images/official DMW.png'),
                'bagongPilipinasLogoPath' => asset('images/bagong-pilipinas-logo.png'),
                'applicantSignaturePath' => $ctoRequest->applicant_signature_path
                    ? asset('storage/'.$ctoRequest->applicant_signature_path)
                    : $ctoRequest->employee?->effective_signature_url,
            ]);
        }

        return $exporter->stream($ctoRequest);
    }

    private function chiefCanReviewEmployee(?Employee $employee): bool
    {
        $chiefDivision = trim((string) auth()->user()?->employee?->division);
        $employeeDivision = trim((string) $employee?->division);

        return $chiefDivision !== '' && $employeeDivision !== '' && strcasecmp($chiefDivision, $employeeDivision) === 0;
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

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 5);

        return in_array($perPage, [5, 10, 20, 50], true) ? $perPage : 5;
    }
}
