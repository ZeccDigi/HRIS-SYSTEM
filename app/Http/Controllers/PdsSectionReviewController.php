<?php

namespace App\Http\Controllers;

use App\Listeners\AuditLogListener;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

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
            'section_name' => 'required|string',
            'status' => 'required|in:pending,approved,rejected',
            'remarks' => [
                Rule::requiredIf($request->input('status') === 'rejected'),
                'nullable',
                'string',
            ],
        ]);

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
        foreach (self::SECTION_NAMES as $sectionName) {
            $employee->pdsSectionReviews()->updateOrCreate(
                ['section_name' => $sectionName],
                [
                    'status' => 'approved',
                    'remarks' => null,
                    'changed_fields' => [],
                    'reviewed_by_id' => auth()->id(),
                ]
            );
        }

        AuditLogListener::logActivity(
            'Approve All PDS Sections',
            'Employees',
            'Approved all PDS sections for employee ' . trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? '')),
            auth()->user()
        );

        return back()->with('success', 'All PDS sections have been approved successfully.');
    }
}
