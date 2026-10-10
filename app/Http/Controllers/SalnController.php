<?php

namespace App\Http\Controllers;

use App\Listeners\AuditLogListener;
use App\Http\Requests\StoreSalnRequest;
use App\Models\Saln;
use App\Models\User;
use App\Models\UtilityOption;
use App\Notifications\SalnReviewNotification;
use App\Services\SalnPdfExporter;
use App\Support\UtilityOptionRegistry;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;

class SalnController extends Controller
{
    public function index()
    {
        $employee = Auth::user()->employee;

        if (! $employee) {
            abort(403, 'User is not linked to an employee record.');
        }

        UtilityOptionRegistry::ensureDefaults();

        $salns = $employee->salns()->orderBy('as_of_date', 'desc')->get();
        $selectedYear = (int) request('year', now()->year);
        $selectedSaln = $salns->first(fn (Saln $saln) => (int) $saln->as_of_date->format('Y') === $selectedYear);

        if (! request()->boolean('edit')) {
            $saln = $selectedSaln ?: $this->blankSalnForYear($employee->id, $selectedYear);
            $canExportSaln = (bool) $selectedSaln;
            $canManageSaln = true;
            $backUrl = null;
            $hasSavedSaln = (bool) $selectedSaln;

            return view('saln.show', [
                'saln' => $saln,
                'canExportSaln' => $canExportSaln,
                'canManageSaln' => $canManageSaln,
                'backUrl' => $backUrl,
                'selectedYear' => $selectedYear,
                'hasSavedSaln' => $hasSavedSaln,
            ]);
        }

        $isSalnIndex = true;
        $salnOptionSets = $this->optionSets($selectedSaln);

        return view('saln.create', compact('employee', 'salns', 'selectedYear', 'selectedSaln', 'isSalnIndex', 'salnOptionSets'));
    }

    public function create()
    {
        $employee = Auth::user()->employee;

        if (! $employee) {
            abort(403, 'User is not linked to an employee record.');
        }

        UtilityOptionRegistry::ensureDefaults();

        $salns = $employee->salns()->orderBy('as_of_date', 'desc')->get();
        $selectedYear = (int) now()->year;
        $selectedSaln = null;
        $isSalnIndex = false;
        $salnOptionSets = $this->optionSets(null);

        return view('saln.create', compact('employee', 'salns', 'selectedYear', 'selectedSaln', 'isSalnIndex', 'salnOptionSets'));
    }

    public function store(StoreSalnRequest $request)
    {
        $validated = $request->validated();
        $validated['has_business_interests'] = $request->boolean('has_business_interests');
        $validated['has_relatives_in_gov'] = $request->boolean('has_relatives_in_gov');

        $total_assets = 0;
        foreach ($validated['real_properties'] ?? [] as $prop) {
            $total_assets += (float) ($prop['acquisition_cost'] ?? 0);
        }
        foreach ($validated['personal_properties'] ?? [] as $prop) {
            $total_assets += (float) ($prop['acquisition_cost'] ?? 0);
        }

        $total_liabilities = 0;
        foreach ($validated['liabilities'] ?? [] as $liab) {
            $total_liabilities += (float) ($liab['outstanding_balance'] ?? 0);
        }

        $net_worth = $total_assets - $total_liabilities;

        $employee = Auth::user()->employee;
        $payload = array_merge($validated, [
            'employee_id' => $employee->id,
            'total_assets' => $total_assets,
            'total_liabilities' => $total_liabilities,
            'net_worth' => $net_worth,
            'review_status' => 'pending',
            'review_remarks' => null,
            'reviewed_by_id' => null,
            'reviewed_at' => null,
            'submitted_at' => now(),
        ]);

        if ($request->filled('saln_id')) {
            $saln = Saln::where('employee_id', $employee->id)
                ->findOrFail($request->integer('saln_id'));
            $saln->update($payload);
        } else {
            $saln = Saln::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'as_of_date' => $validated['as_of_date'],
                ],
                $payload
            );
        }

        AuditLogListener::logActivity(
            'Save SALN',
            'Employees',
            'Saved Statement of Assets, Liabilities, and Net Worth (SALN) as of ' . $saln->as_of_date->format('F d, Y') . ' for ' . trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? '')),
            Auth::user()
        );

        $this->notifySalnReviewers($saln);

        return redirect()
            ->route('salns.index', ['year' => $saln->as_of_date->format('Y')])
            ->with('success', 'SALN submitted for HR review successfully.');
    }

    public function show(Saln $saln)
    {
        if (! $this->canPreviewSaln($saln)) {
            abort(403);
        }

        $canExportSaln = $this->isSalnOwner($saln);
        $backUrl = $this->safeBackUrl((string) request('back', ''));

        return view('saln.show', compact('saln', 'canExportSaln', 'backUrl'));
    }

    public function review(Request $request, Saln $saln): RedirectResponse
    {
        $validated = $request->validate([
            'review_status' => 'required|in:approved,returned',
            'review_remarks' => 'nullable|string|max:2000',
        ]);

        if ($validated['review_status'] === 'returned' && blank($validated['review_remarks'] ?? null)) {
            return back()->withErrors(['review_remarks' => 'Remarks are required when returning a SALN.'])->withInput();
        }

        $saln->update([
            'review_status' => $validated['review_status'],
            'review_remarks' => $validated['review_remarks'] ?? null,
            'reviewed_by_id' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $employeeName = trim(($saln->employee?->firstname ?? '') . ' ' . ($saln->employee?->lastname ?? '')) ?: 'Employee';
        $year = $saln->as_of_date?->format('Y');
        $statusText = $validated['review_status'] === 'approved' ? 'approved' : 'returned';

        $saln->employee?->user?->notify(new SalnReviewNotification(
            $saln,
            $validated['review_status'] === 'approved' ? 'SALN Approved' : 'SALN Returned',
            $validated['review_status'] === 'approved'
                ? "Your {$year} SALN has been approved by HR."
                : "Your {$year} SALN was returned by HR. Please review the remarks and update it.",
            route('salns.index', ['year' => $year])
        ));

        AuditLogListener::logActivity(
            'Review SALN',
            'Employees',
            'Marked ' . $year . ' SALN for ' . $employeeName . ' as ' . ucfirst($statusText),
            Auth::user()
        );

        return back()->with('success', 'SALN ' . $statusText . ' successfully.');
    }

    public function download(Saln $saln, SalnPdfExporter $exporter): Response|RedirectResponse
    {
        $employee = Auth::user()->employee;

        if (! $employee || $saln->employee_id !== $employee->id) {
            abort(403);
        }

        try {
            return $exporter->download($saln, request()->boolean('inline'));
        } catch (\Throwable $e) {
            return redirect()
                ->route('salns.show', $saln)
                ->with('error', 'Could not generate PDF. '.$e->getMessage());
        }
    }

    protected function optionSets(?Saln $saln): array
    {
        return [
            'property_kinds' => UtilityOption::listFor('property_kinds')->values(),
            'acquisition_modes' => UtilityOption::listFor('acquisition_modes')->values(),
            'relationships' => UtilityOption::listFor('relationships')->values(),
            'government_id_types' => UtilityOption::listFor('government_id_types')->values(),
        ];
    }

    private function isSalnOwner(Saln $saln): bool
    {
        $employee = Auth::user()?->employee;

        return $employee && $saln->employee_id === $employee->id;
    }

    private function canPreviewSaln(Saln $saln): bool
    {
        if ($this->isSalnOwner($saln)) {
            return true;
        }

        $user = Auth::user();
        $employee = $user?->employee;
        $roles = [
            strtolower(trim((string) ($user?->role ?? ''))),
            strtolower(trim((string) ($employee?->account_role ?? ''))),
        ];

        return (bool) array_intersect($roles, ['admin', 'hrstaff', 'hr staff']);
    }

    private function notifySalnReviewers(Saln $saln): void
    {
        $saln->loadMissing('employee');

        $employeeName = trim(($saln->employee?->firstname ?? '') . ' ' . ($saln->employee?->lastname ?? '')) ?: 'An employee';
        $year = $saln->as_of_date?->format('Y');

        $reviewers = User::query()
            ->whereHas('employee', function ($query) {
                $query->whereIn('account_role', ['ADMIN', 'HRSTAFF', 'admin', 'hrstaff', 'HR Staff']);
            })
            ->get();

        if ($reviewers->isEmpty()) {
            return;
        }

        Notification::send($reviewers, new SalnReviewNotification(
            $saln,
            'SALN Pending Review',
            "{$employeeName} submitted the {$year} SALN for HR review.",
            route('employees.show', ['employee' => $saln->employee_id, 'tab' => 'saln'])
        ));
    }

    private function safeBackUrl(string $back): string
    {
        $back = trim($back);

        if ($back !== '' && (str_starts_with($back, url('/')) || str_starts_with($back, '/'))) {
            return $back;
        }

        return route('salns.index');
    }

    private function blankSalnForYear(int $employeeId, int $year): Saln
    {
        return new Saln([
            'employee_id' => $employeeId,
            'type_of_filing' => '',
            'as_of_date' => \Carbon\Carbon::create($year, 12, 31),
            'declarant_info' => [],
            'spouse_info' => [],
            'filing_status' => '',
            'children' => [],
            'real_properties' => [],
            'personal_properties' => [],
            'liabilities' => [],
            'has_business_interests' => false,
            'business_interests' => [],
            'has_relatives_in_gov' => false,
            'relatives_in_gov' => [],
            'total_assets' => 0,
            'total_liabilities' => 0,
            'net_worth' => 0,
        ]);
    }
}
