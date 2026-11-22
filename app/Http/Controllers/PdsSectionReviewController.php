<?php

namespace App\Http\Controllers;

use App\Listeners\AuditLogListener;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Services\PdsSnapshotService;
use Illuminate\Support\Facades\DB;

class PdsSectionReviewController extends Controller
{
    private const SECTION_NAMES = [
        'Personal Information',
        'Family Background',
        'Educational Background',
        'Civil Service Eligibility',
        'Work Experience',
        'Voluntary Work',
        'Training',
        'Other Information',
        'References',
        'Questionnaire',
    ];

    public function store(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'section_name' => ['required', 'string', Rule::in(self::SECTION_NAMES)],
            'status' => 'required|in:approved,rejected',
            'remarks' => [
                Rule::requiredIf($request->input('status') === 'rejected'),
                'nullable',
                'string',
            ],
        ]);

        $year = $this->reviewYear($employee);
        if ($year !== (int) now()->year || $year !== (int) ($employee->pds_record_year ?? now()->year)) {
            app(PdsSnapshotService::class)->reviewHistorical($employee, $year, $validated['section_name'], $validated['status'], $validated['remarks'] ?? null);
            AuditLogListener::logActivity('Review PDS Section', 'Employees', "Reviewed {$year} {$validated['section_name']} for employee #{$employee->id}: {$validated['status']}.", auth()->user());
            return back()->with('success', "{$year} PDS section review saved.");
        }

        DB::transaction(function () use ($employee, $validated) {
        $employee->newQuery()->whereKey($employee->id)->lockForUpdate()->firstOrFail();
        $review = $employee->pdsSectionReviews()->where('section_name', $validated['section_name'])->lockForUpdate()->first();
        abort_unless($review && $review->status === 'pending', 409, 'This section has no pending update to review.');
        $review = $employee->pdsSectionReviews()->firstOrNew([
            'section_name' => $validated['section_name'],
        ]);

        $review->fill([
            'status' => $validated['status'],
            'remarks' => $validated['remarks'] ?? null,
            'reviewed_by_id' => auth()->id(),
        ]);

        if ($validated['status'] === 'approved') {
            $review->changed_fields = [];
        }

            $review->save();
            if ($validated['status'] === 'approved') {
                app(PdsSnapshotService::class)->publishSections($employee, [$validated['section_name']]);
            }
            app(PdsSnapshotService::class)->syncLiveDraft($employee);
        });

        AuditLogListener::logActivity(
            'Review PDS Section',
            'Employees',
            'Reviewed PDS section (' . $validated['section_name'] . ') for employee ' . trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? '')) . ' - Status: ' . ucfirst($validated['status']),
            auth()->user()
        );

        return back()->with('success', 'Review for ' . $validated['section_name'] . ' saved successfully.');
    }

    public function approveAll(Employee $employee)
    {
        $remarks = request()->validate([
            'remarks' => ['nullable', 'string'],
        ])['remarks'] ?? null;

        $year = $this->reviewYear($employee);
        if ($year !== (int) now()->year || $year !== (int) ($employee->pds_record_year ?? now()->year)) {
            app(PdsSnapshotService::class)->reviewHistorical($employee, $year, null, 'approved', $remarks);
            AuditLogListener::logActivity('Approve All PDS Sections', 'Employees', "Approved pending {$year} PDS sections for employee #{$employee->id}.", auth()->user());
            return back()->with('success', "Pending {$year} PDS sections approved.");
        }

        DB::transaction(function () use ($employee, $remarks) {
        $employee->newQuery()->whereKey($employee->id)->lockForUpdate()->firstOrFail();
        $sections = $employee->pdsSectionReviews()->where('status', 'pending')->lockForUpdate()->pluck('section_name')->all();
        abort_if(empty($sections), 409, 'There are no pending PDS updates to approve.');
        foreach ($sections as $sectionName) {
            $employee->pdsSectionReviews()->updateOrCreate(
                ['section_name' => $sectionName],
                [
                    'status' => 'approved',
                    'remarks' => $remarks,
                    'changed_fields' => [],
                    'reviewed_by_id' => auth()->id(),
                ]
            );
        }

        app(PdsSnapshotService::class)->publishSections($employee, $sections);
        app(PdsSnapshotService::class)->syncLiveDraft($employee);
        });

        AuditLogListener::logActivity(
            'Approve All PDS Sections',
            'Employees',
            'Approved all PDS sections for employee ' . trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? '')),
            auth()->user()
        );

        return back()->with('success', 'All pending PDS sections have been approved successfully.');
    }

    public function rejectAll(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'remarks' => ['required', 'string'],
        ]);

        $year = $this->reviewYear($employee);
        if ($year !== (int) now()->year || $year !== (int) ($employee->pds_record_year ?? now()->year)) {
            app(PdsSnapshotService::class)->reviewHistorical($employee, $year, null, 'rejected', $validated['remarks']);
            AuditLogListener::logActivity('Reject All PDS Sections', 'Employees', "Rejected pending {$year} PDS sections for employee #{$employee->id}.", auth()->user());
            return back()->with('success', "Pending {$year} PDS sections rejected.");
        }

        DB::transaction(function () use ($employee, $validated) {
        $employee->newQuery()->whereKey($employee->id)->lockForUpdate()->firstOrFail();
        $sections = $employee->pdsSectionReviews()->where('status', 'pending')->lockForUpdate()->pluck('section_name')->all();
        abort_if(empty($sections), 409, 'There are no pending PDS updates to reject.');
        foreach ($sections as $sectionName) {
            $employee->pdsSectionReviews()->updateOrCreate(
                ['section_name' => $sectionName],
                [
                    'status' => 'rejected',
                    'remarks' => $validated['remarks'],
                    'reviewed_by_id' => auth()->id(),
                ]
            );
        }
        app(PdsSnapshotService::class)->syncLiveDraft($employee);
        });

        AuditLogListener::logActivity(
            'Reject All PDS Sections',
            'Employees',
            'Rejected all PDS sections for employee ' . trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? '')),
            auth()->user()
        );

        return back()->with('success', 'PDS sections have been rejected successfully.');
    }

    private function reviewYear(Employee $employee): int
    {
        request()->validate(['year' => ['nullable', 'integer']]);
        $year = (int) request('year', now()->year);
        abort_unless(app(PdsSnapshotService::class)->hasYear($employee, $year), 422);
        abort_if(app(PdsSnapshotService::class)->isArchivedForYear($employee, $year), 409, 'Restore this PDS year before reviewing it.');
        return $year;
    }
}
