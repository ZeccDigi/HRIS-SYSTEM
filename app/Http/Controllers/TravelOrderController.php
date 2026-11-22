<?php

namespace App\Http\Controllers;

use App\Listeners\AuditLogListener;
use App\Models\Employee;
use App\Models\TravelOrder;
use App\Models\User;
use App\Notifications\TravelOrderNotification;
use App\Services\TravelAuthorityPdfExporter;
use App\Services\TravelAuthorityReportPdfExporter;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TravelOrderController extends Controller
{
    public function index(Request $request): View
    {
        $employee = auth()->user()->employee;

        if (! $employee) {
            abort(404, 'Employee record not found.');
        }

        $tab = (string) $request->query('tab', 'my');
        if (! in_array($tab, ['my', 'tagged'], true)) {
            $tab = 'my';
        }

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', '');
        $month = (string) $request->query('month', '');
        $year = (string) $request->query('year', now()->year);
        $perPage = $this->perPage($request);

        $travelOrders = TravelOrder::with(['employee', 'companions', 'recordsOfficer', 'regionalDirector'])
            ->when($tab === 'my', fn ($query) => $query->where('employee_id', $employee->id))
            ->when($tab === 'tagged', fn ($query) => $query
                ->where('employee_id', '!=', $employee->id)
                ->whereHas('companions', fn ($companionQuery) => $companionQuery->where('employees.id', $employee->id)))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('ta_number', 'like', "%{$search}%")
                        ->orWhere('places_of_travel', 'like', "%{$search}%")
                        ->orWhere('purpose', 'like', "%{$search}%")
                        ->orWhereHas('employee', function ($employeeQuery) use ($search) {
                            $employeeQuery
                                ->where('firstname', 'like', "%{$search}%")
                                ->orWhere('lastname', 'like', "%{$search}%");
                        })
                        ->orWhereHas('recordsOfficer', function ($recordsOfficerQuery) use ($search) {
                            $recordsOfficerQuery
                                ->where('firstname', 'like', "%{$search}%")
                                ->orWhere('lastname', 'like', "%{$search}%");
                        })
                        ->orWhereHas('regionalDirector', function ($regionalDirectorQuery) use ($search) {
                            $regionalDirectorQuery
                                ->where('firstname', 'like', "%{$search}%")
                                ->orWhere('lastname', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status !== '', fn ($query) => $query->where('status', $status))
            ->when($month !== '', fn ($query) => $query->whereMonth('travel_date_start', $month))
            ->when($year !== '', fn ($query) => $query->whereYear('created_at', $year))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('travel-orders.index', compact('travelOrders', 'tab', 'search', 'status', 'month', 'year'));
    }

    public function create(): View
    {
        $employees = Employee::where('id', '!=', auth()->user()->employee?->id)
            ->orderBy('lastname')
            ->get();

        return view('travel-orders.create', compact('employees'));
    }

    public function edit(Request $request, TravelOrder $travelOrder): View
    {
        abort_unless($travelOrder->canBeEditedBy($request->user()->employee), 403);
        $employees = Employee::where('id', '!=', $request->user()->employee->id)->orderBy('lastname')->get();

        return view('travel-orders.create', compact('travelOrder', 'employees'));
    }

    public function update(Request $request, TravelOrder $travelOrder): RedirectResponse|Response
    {
        abort_unless($travelOrder->canBeEditedBy($request->user()->employee), 403);

        return $this->saveRequest($request, $travelOrder);
    }

    public function store(Request $request): RedirectResponse|Response
    {
        return $this->saveRequest($request);
    }

    private function saveRequest(Request $request, ?TravelOrder $existing = null): RedirectResponse|Response
    {
        $employee = auth()->user()->employee;

        if (! $employee) {
            return redirect()->route('dashboard')->with('error', 'Unauthorized.');
        }

        $validated = $request->validate([
            'travel_type' => 'required|string|in:local,foreign',
            'travel_date_start' => 'required|date',
            'travel_date_end' => 'required|date|after_or_equal:travel_date_start',
            'places_of_travel' => 'required|string|max:500',
            'purpose' => 'required|string',
            'notes_remarks' => 'nullable|string|max:1000',
            'driver_name' => 'nullable|string|max:255',
            'vehicle_plate_no' => 'nullable|string|max:255',
            'companions' => 'nullable|array',
            'companions.*' => 'exists:employees,id',
            'attachment' => 'nullable|file|mimes:pdf|max:5120',
        ]);

        if (in_array($employee->id, $validated['companions'] ?? [], true)) {
            throw ValidationException::withMessages([
                'companions' => 'You cannot add yourself as a companion.',
            ]);
        }

        $this->validateTravelDates($validated['travel_date_start'], $validated['travel_date_end']);

        if ($existing) {
            DB::transaction(function () use ($existing, $employee, $validated, $request) {
                $current = TravelOrder::query()->lockForUpdate()->findOrFail($existing->id);
                abort_unless($current->canBeEditedBy($employee), 403);
                $current->update([
                    'travel_type' => $validated['travel_type'] ?? null,
                    'travel_date_start' => $validated['travel_date_start'] ?? null,
                    'travel_date_end' => $validated['travel_date_end'] ?? null,
                    'places_of_travel' => $validated['places_of_travel'] ?? null,
                    'purpose' => $validated['purpose'] ?? null,
                    'notes_remarks' => $validated['notes_remarks'] ?? null,
                    'driver_name' => $validated['driver_name'] ?? null,
                    'vehicle_plate_no' => $validated['vehicle_plate_no'] ?? null,
                    'tar_deadline' => null,
                    'attachment_path' => $request->file('attachment')?->store('travel-order-attachments', 'documents') ?? $current->attachment_path,
                ]);
                $current->companions()->sync($validated['companions'] ?? []);
            });

            return redirect()->route('travel-orders.show', $existing)->with('success', 'Request updated successfully.');
        }

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('travel-order-attachments', 'documents');
        }

        $travelOrder = DB::transaction(function () use ($employee, $validated, $attachmentPath) {
            $travelOrder = $employee->travelOrders()->create([
                'ta_number' => 'TA-TEMP-'.Str::uuid(),
                'travel_type' => $validated['travel_type'],
                'travel_date_start' => $validated['travel_date_start'],
                'travel_date_end' => $validated['travel_date_end'],
                'places_of_travel' => $validated['places_of_travel'],
                'purpose' => $validated['purpose'],
                'requesting_office' => 'Regional Office XI',
                'notes_remarks' => $validated['notes_remarks'] ?? null,
                'driver_name' => $validated['driver_name'] ?? null,
                'vehicle_plate_no' => $validated['vehicle_plate_no'] ?? null,
                'attachment_path' => $attachmentPath,
                'status' => 'pending',
                'recordofficer_status' => 'pending',
                'rd_status' => 'pending',
                'tar_deadline' => null,
                'tar_status' => 'pending',
                'chief_status' => 'pending',
                'hrstaff_status' => 'pending',
            ]);

            $travelOrder->forceFill([
                'ta_number' => $this->generateTravelAuthorityNumber($travelOrder),
            ])->save();

            if (! empty($validated['companions'])) {
                $travelOrder->companions()->attach($validated['companions']);
            }

            return $travelOrder;
        });

        $recordsOfficers = User::whereHas('employee', function ($query) {
            $query->where('account_role', 'recordofficer');
        })->get();

        $employeeName = trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? ''));

        Notification::send($recordsOfficers, new TravelOrderNotification(
            $travelOrder,
            'New Travel Authority',
            "{$employeeName} submitted a travel authority to {$travelOrder->places_of_travel} for Record Officer review."
        ));

        AuditLogListener::logActivity(
            'Submit Travel Order',
            'Attendance',
            "Submitted travel order ({$travelOrder->ta_number}) to {$travelOrder->places_of_travel} from {$travelOrder->travel_date_start} to {$travelOrder->travel_date_end}",
            auth()->user()
        );

        $successMessage = 'Travel authority created successfully.';

        if ($request->boolean('modal')) {
            session()->flash('success', $successMessage);

            return response()->view('leaves.modal-success-redirect', [
                'url' => route('travel-orders.index'),
                'message' => $successMessage,
            ]);
        }

        return redirect()->route('travel-orders.index')->with('success', $successMessage);
    }

    public function show(TravelOrder $travelOrder): View
    {
        $this->authorizeTravelOrderAccess($travelOrder);

        $travelOrder->load(['employee', 'companions', 'recordsOfficer', 'regionalDirector']);

        return view('travel-orders.show', compact('travelOrder'));
    }

    public function preview(TravelOrder $travelOrder): View
    {
        $this->authorizeTravelOrderAccess($travelOrder);

        $travelOrder->loadMissing(['employee', 'companions']);

        return view('travel-orders.pdf', [
            'travelOrder' => $travelOrder,
            // Keep the browser preview identical to the generated PDF header.
            'dmwLogoPath' => asset('images/official DMW.png'),
            'bagongPilipinasLogoPath' => asset('images/bagong-pilipinas-logo.png'),
            'regionalOffice' => 'REGIONAL OFFICE XI, DAVAO CITY',
            'requestingOffice' => 'Regional Office XI',
            'notesRemarks' => $travelOrder->notes_remarks ?: '',
            'driverName' => $travelOrder->driver_name ?: '',
            'vehiclePlateNo' => $travelOrder->vehicle_plate_no ?: '',
            // Browser preview uses the public URL; the printable PDF receives the local path.
            'requestingSignaturePath' => $travelOrder->employee?->effective_signature_url,
        ]);
    }

    public function print(TravelOrder $travelOrder, TravelAuthorityPdfExporter $exporter): Response
    {
        $this->authorizeTravelOrderAccess($travelOrder);

        return $exporter->stream($travelOrder);
    }

    public function acknowledgementReport(TravelOrder $travelOrder): View
    {
        $this->authorizeTravelOrderAccess($travelOrder);
        $travelOrder->loadMissing([
            'employee.user',
            'companions',
            'recordsOfficer',
            'regionalDirector',
            'tarHrReviewer',
            'tarChiefReviewer',
            'tarRegionalDirectorReviewer',
        ]);

        $divisionHead = $this->divisionHeadFor($travelOrder);

        return view('travel-orders.acknowledgement-report', compact('travelOrder', 'divisionHead'));
    }

    public function printAcknowledgementReport(
        TravelOrder $travelOrder,
        TravelAuthorityReportPdfExporter $exporter
    ): Response
    {
        $this->authorizeTravelOrderAccess($travelOrder);
        $travelOrder->loadMissing(['employee.user', 'companions', 'recordsOfficer', 'regionalDirector']);

        $divisionHead = $this->divisionHeadFor($travelOrder);

        return $exporter->stream($travelOrder, $divisionHead);
    }

    public function viewAcknowledgementReportFile(TravelOrder $travelOrder)
    {
        $this->authorizeTravelOrderAccess($travelOrder);

        if (! $travelOrder->tar_attachment_path || ! Storage::disk('documents')->exists($travelOrder->tar_attachment_path)) {
            abort(404);
        }

        $fileName = ($travelOrder->ta_number ?: 'travel-authority-report') . '.pdf';

        return Storage::disk('documents')->response($travelOrder->tar_attachment_path, $fileName, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }

    public function adminIndex(Request $request): View
    {
        $role = strtolower((string) auth()->user()->role);
        $search = trim((string) $request->query('search', ''));
        $travelType = $request->query('travel_type', '');
        $sort = $request->query('sort', 'latest');

        $allTravelOrders = $this->sortTravelOrders($this->filterTravelOrders(
            $this->scopeTravelOrdersForRole(TravelOrder::with(['employee', 'companions', 'recordsOfficer', 'regionalDirector']), $role),
            $search,
            $travelType
        ), $sort)->get();

        $pendingTravelOrders = $this->sortTravelOrders($this->filterTravelOrders(
            $this->scopeTravelOrdersForRole(TravelOrder::with(['employee', 'companions', 'recordsOfficer', 'regionalDirector'])->where('status', 'pending'), $role),
            $search,
            $travelType
        ), $sort)->get();

        $postActivityReports = $this->sortTravelOrders($this->filterTravelOrders(
            $this->scopeTravelOrdersForRole(
                TravelOrder::with(['employee', 'companions', 'tarHrReviewer', 'tarChiefReviewer', 'tarRegionalDirectorReviewer'])
                    ->whereNotNull('tar_submitted_at'),
                $role
            ),
            $search,
            $travelType
        ), $sort)->get();

        $tab = $request->query('tab', 'pending');
        if (! in_array($tab, ['all', 'pending', 'reports'], true)) {
            $tab = 'pending';
        }

        $travelTypes = [
            'local' => 'Local',
            'foreign' => 'Foreign',
        ];

        return view('travel-orders.admin-index', compact('allTravelOrders', 'pendingTravelOrders', 'postActivityReports', 'tab', 'sort', 'search', 'travelType', 'travelTypes'));
    }

    public function updateStatus(Request $request, TravelOrder $travelOrder): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected',
            'remarks' => 'nullable|string',
            'is_tar_required' => 'sometimes|boolean',
        ]);

        $role = strtolower((string) auth()->user()->role);
        $employeeId = auth()->user()->employee?->id;
        $remarks = $validated['remarks'] ?? null;
        $updated = false;

        abort_unless($employeeId && in_array($role, ['recordofficer', 'regionaldirector'], true), 403);

        DB::transaction(function () use ($validated, $travelOrder, $role, $employeeId, $remarks, &$updated) {
            $travelOrder = TravelOrder::whereKey($travelOrder->id)->lockForUpdate()->firstOrFail();
            if ($travelOrder->status !== 'pending') return;
            if ($role === 'recordofficer') {
                if (($travelOrder->recordofficer_status ?? 'pending') !== 'pending') {
                    return;
                }

                $travelOrder->approved_by_recordofficer = $employeeId;
                $travelOrder->recordofficer_remarks = $remarks;
                $travelOrder->recordofficer_status = $validated['status'];

                if ($validated['status'] === 'rejected') {
                    $travelOrder->status = 'rejected';
                    $travelOrder->employee?->user?->notify(new TravelOrderNotification(
                        $travelOrder,
                        'Travel Authority Rejected',
                        "Your travel authority to {$travelOrder->places_of_travel} was rejected by the Record Officer."
                    ));
                } else {
                    $tarRequired = (bool) ($validated['is_tar_required'] ?? true);
                    $travelOrder->is_tar_required = $tarRequired;

                    $travelOrder->tar_deadline = null;

                    $travelOrder->status = 'pending';

                    $regionalDirectors = User::whereHas('employee', function ($query) {
                        $query->where('account_role', 'regionaldirector');
                    })->get();

                    Notification::send($regionalDirectors, new TravelOrderNotification(
                        $travelOrder,
                        'Travel Authority Ready for Approval',
                        "Travel authority {$travelOrder->ta_number} is ready for Regional Director approval."
                    ));
                }

                $updated = true;
            }

            if ($role === 'regionaldirector') {
                if (($travelOrder->recordofficer_status ?? 'pending') !== 'approved' || ($travelOrder->rd_status ?? 'pending') !== 'pending') {
                    return;
                }

                $travelOrder->approved_by_regionaldirector = $employeeId;
                $travelOrder->rd_remarks = $remarks;
                $travelOrder->rd_status = $validated['status'];
                $travelOrder->status = $validated['status'] === 'approved' ? 'approved' : 'rejected';
                $travelOrder->tar_deadline = $validated['status'] === 'approved' && ($travelOrder->is_tar_required ?? true)
                    ? $travelOrder->travel_date_end?->copy()->addDays(5)->toDateString()
                    : null;

                $travelOrder->employee?->user?->notify(new TravelOrderNotification(
                    $travelOrder,
                    $validated['status'] === 'approved' ? 'Travel Authority Approved' : 'Travel Authority Rejected',
                    $validated['status'] === 'approved'
                        ? "Your travel authority to {$travelOrder->places_of_travel} has been approved by the Regional Director."
                        : "Your travel authority to {$travelOrder->places_of_travel} was rejected by the Regional Director."
                ));

                $updated = true;
            }

            $travelOrder->save();
        });

        if (! $updated) {
            return back()->with('error', 'This travel authority has already been processed or is not ready for your approval.');
        }

        $message = $validated['status'] === 'approved'
            ? 'Travel authority updated successfully.'
            : 'Travel authority rejected.';

        AuditLogListener::logActivity(
            ucfirst($validated['status']) . ' Travel Order',
            'Attendance',
            ucfirst($validated['status']) . " travel order ({$travelOrder->ta_number}) for employee " . trim(($travelOrder->employee->firstname ?? '') . ' ' . ($travelOrder->employee->lastname ?? '')),
            auth()->user()
        );

        return redirect()->route('hr.travel-orders.index', ['tab' => 'pending'])->with('success', $message);
    }

    public function submitTar(Request $request, TravelOrder $travelOrder): RedirectResponse
    {
        $employee = auth()->user()->employee;

        if (! $employee || $travelOrder->employee_id !== $employee->id) {
            abort(403);
        }

        if (($travelOrder->status ?? 'pending') !== 'approved') {
            return back()->with('error', 'Only approved travel authorities can accept a TAR submission.');
        }

        if (! ($travelOrder->is_tar_required ?? true)) {
            return back()->with('error', 'TAR submission is not required for this travel authority.');
        }

        $reviewStage = (string) ($travelOrder->tar_review_status ?? '');
        $isFirstSubmission = $travelOrder->tar_submitted_at === null;
        $isReturnedForCorrection = $reviewStage === 'returned';

        if (! $isFirstSubmission && ! $isReturnedForCorrection) {
            return back()->with('error', 'This TAR has already been submitted and cannot be overwritten unless it is returned for correction.');
        }

        $validated = $request->validate([
            'tar_participants_present' => 'required|string|max:2000',
            'tar_objectives' => 'required|string|max:5000',
            'tar_highlights' => 'required|string|max:5000',
            'tar_results' => 'required|string|max:5000',
            'tar_recommendations' => 'required|string|max:5000',
            'tar_commitments' => 'required|string|max:5000',
            'tar_attachments' => 'nullable|array',
            'tar_attachments.*' => 'in:photo_documentation,lecture_materials,activity_materials,certificate,program_flow,others',
            'tar_other_attachment' => [
                'nullable',
                Rule::requiredIf(fn () => in_array('others', $request->input('tar_attachments', []), true)),
                'string',
                'max:255',
            ],
            'tar_attachment' => 'nullable|file|mimes:pdf|max:5120',
            'tar_remarks' => 'nullable|string|max:1000',
        ]);

        $travelOrder->loadMissing(['employee', 'companions']);
        $dmwParticipants = collect([$travelOrder->employee])
            ->merge($travelOrder->companions)
            ->filter()
            ->map(fn ($participant) => trim(collect([
                $participant->firstname,
                $participant->middlename,
                $participant->lastname,
                $participant->suffix,
            ])->filter()->implode(' ')))
            ->filter()
            ->implode(', ');

        $attachmentPath = $travelOrder->tar_attachment_path;
        if ($request->hasFile('tar_attachment')) {
            if ($attachmentPath) {
                Storage::disk('documents')->delete($attachmentPath);
            }

            $attachmentPath = $request->file('tar_attachment')->store('travel-accomplishment-reports', 'documents');
        }

        $travelOrder->update([
            'tar_attachment_path' => $attachmentPath,
            'tar_submitted_at' => now(),
            'tar_status' => 'submitted',
            'tar_review_status' => 'pending_chief',
            'tar_hr_status' => 'not_applicable',
            'tar_reviewed_by_hr' => null,
            'tar_hr_remarks' => null,
            'tar_chief_status' => 'pending',
            'tar_reviewed_by_chief' => null,
            'tar_chief_remarks' => null,
            'tar_rd_status' => 'pending',
            'tar_reviewed_by_rd' => null,
            'tar_rd_remarks' => null,
            'tar_approved_at' => null,
            'tar_remarks' => $validated['tar_remarks'] ?? null,
            'tar_activity' => $travelOrder->purpose,
            'tar_activity_date' => $travelOrder->travel_date_end?->toDateString(),
            'tar_venue' => $travelOrder->places_of_travel,
            'tar_participants_present' => $validated['tar_participants_present'],
            'tar_dmw_participants' => $dmwParticipants,
            'tar_objectives' => $validated['tar_objectives'],
            'tar_highlights' => $validated['tar_highlights'],
            'tar_results' => $validated['tar_results'],
            'tar_recommendations' => $validated['tar_recommendations'],
            'tar_commitments' => $validated['tar_commitments'],
            'tar_attachments' => $validated['tar_attachments'] ?? [],
            'tar_other_attachment' => $validated['tar_other_attachment'] ?? null,
        ]);

        $travelOrder->loadMissing('employee');
        $submitterName = trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? '')) ?: 'An employee';
        $divisionChief = $this->divisionHeadFor($travelOrder);
        $divisionChief?->user?->notify(new TravelOrderNotification(
            $travelOrder,
            'TAR Ready for Division Chief Review',
            "{$submitterName} submitted a Travel Authority Report for {$travelOrder->ta_number}."
        ));

        AuditLogListener::logActivity(
            'Submit TAR',
            'Attendance',
            "Submitted TAR for travel authority ({$travelOrder->ta_number})",
            auth()->user()
        );

        return redirect()->route('travel-orders.index')->with('success', 'TAR submitted successfully.');
    }

    public function reviewTar(Request $request, TravelOrder $travelOrder): RedirectResponse
    {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['approve', 'return'])],
            'remarks' => [Rule::requiredIf($request->input('action') === 'return'), 'nullable', 'string', 'max:2000'],
        ]);

        if (! $travelOrder->tar_submitted_at) {
            return back()->with('error', 'This TAR has not been submitted.');
        }

        $role = strtolower((string) auth()->user()->role);
        $reviewer = auth()->user()->employee;
        $stage = (string) ($travelOrder->tar_review_status ?: 'pending_chief');
        $isReturn = $validated['action'] === 'return';
        $remarks = $validated['remarks'] ?? null;

        DB::transaction(function () use ($travelOrder, $role, $reviewer, $stage, $isReturn, $remarks) {
            if ($role === 'chief') {
                abort_unless($stage === 'pending_chief' && $this->chiefCanViewEmployee($travelOrder->employee), 403);
                $travelOrder->tar_chief_status = $isReturn ? 'returned' : 'approved';
                $travelOrder->tar_reviewed_by_chief = $reviewer?->id;
                $travelOrder->tar_chief_remarks = $remarks;
                $travelOrder->tar_review_status = $isReturn ? 'returned' : 'pending_rd';
            } elseif ($role === 'regionaldirector') {
                abort_unless($stage === 'pending_rd', 403);
                $travelOrder->tar_rd_status = $isReturn ? 'returned' : 'approved';
                $travelOrder->tar_reviewed_by_rd = $reviewer?->id;
                $travelOrder->tar_rd_remarks = $remarks;
                $travelOrder->tar_review_status = $isReturn ? 'returned' : 'approved';
                $travelOrder->tar_approved_at = $isReturn ? null : now();
            } else {
                abort(403);
            }

            if ($isReturn) {
                $travelOrder->tar_status = 'returned';
            }

            $travelOrder->save();
        });

        $employeeName = trim(($travelOrder->employee?->firstname ?? '') . ' ' . ($travelOrder->employee?->lastname ?? ''));

        if ($isReturn) {
            $travelOrder->employee?->user?->notify(new TravelOrderNotification(
                $travelOrder,
                'TAR Returned',
                "Your TAR for {$travelOrder->ta_number} was returned for correction."
            ));
        } elseif ($travelOrder->tar_review_status === 'pending_chief') {
            $chief = $this->divisionHeadFor($travelOrder);
            $chief?->user?->notify(new TravelOrderNotification(
                $travelOrder,
                'TAR Ready for Division Chief Review',
                "{$employeeName}'s report for {$travelOrder->ta_number} is ready for your review."
            ));
        } elseif ($travelOrder->tar_review_status === 'pending_rd') {
            $regionalDirectors = User::whereHas('employee', fn ($query) => $query->where('account_role', 'regionaldirector'))->get();
            Notification::send($regionalDirectors, new TravelOrderNotification(
                $travelOrder,
                'TAR Ready for Regional Director Review',
                "{$employeeName}'s report for {$travelOrder->ta_number} is ready for final review."
            ));
        } else {
            $travelOrder->employee?->user?->notify(new TravelOrderNotification(
                $travelOrder,
                'TAR Approved',
                "Your TAR for {$travelOrder->ta_number} has been fully approved."
            ));
        }

        AuditLogListener::logActivity(
            $isReturn ? 'Return TAR' : 'Approve TAR',
            'Travel Authority',
            ($isReturn ? 'Returned' : 'Approved') . " TAR for {$travelOrder->ta_number}",
            auth()->user()
        );

        return redirect()->route('travel-orders.ar.show', $travelOrder)->with('success', $isReturn
            ? 'TAR returned to the employee.'
            : 'TAR approved and routed to the next reviewer.');
    }

    private function divisionHeadFor(TravelOrder $travelOrder): ?Employee
    {
        $division = trim((string) $travelOrder->employee?->division);

        if ($division === '') {
            return null;
        }

        return Employee::query()
            ->where('division', $division)
            ->where('account_role', 'chief')
            ->orderBy('lastname')
            ->first();
    }

    private function authorizeTravelOrderAccess(TravelOrder $travelOrder): void
    {
        $employee = auth()->user()->employee;

        $isTaggedCompanion = $employee
            ? $travelOrder->companions()->where('employees.id', $employee->id)->exists()
            : false;

        if ($travelOrder->employee_id !== $employee?->id
            && ! $isTaggedCompanion
            && ! in_array(strtolower((string) auth()->user()->role), ['admin', 'hrstaff', 'recordofficer', 'chief', 'regionaldirector'], true)) {
            abort(403);
        }

        if (strtolower((string) auth()->user()->role) === 'chief' && ! $this->chiefCanViewEmployee($travelOrder->employee)) {
            abort(403);
        }
    }

    private function scopeTravelOrdersForRole($query, string $role)
    {
        if ($role !== 'chief') {
            return $query;
        }

        $division = trim((string) auth()->user()?->employee?->division);

        if ($division === '') {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('employee', fn ($employeeQuery) => $employeeQuery->where('division', $division));
    }

    private function chiefCanViewEmployee(?Employee $employee): bool
    {
        $chiefDivision = trim((string) auth()->user()?->employee?->division);
        $employeeDivision = trim((string) $employee?->division);

        return $chiefDivision !== '' && $employeeDivision !== '' && strcasecmp($chiefDivision, $employeeDivision) === 0;
    }

    private function filterTravelOrders($query, string $search, string $travelType)
    {
        return $query
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('ta_number', 'like', "%{$search}%")
                        ->orWhere('places_of_travel', 'like', "%{$search}%")
                        ->orWhere('purpose', 'like', "%{$search}%")
                        ->orWhereHas('employee', function ($employeeQuery) use ($search) {
                            $employeeQuery
                                ->where('firstname', 'like', "%{$search}%")
                                ->orWhere('middlename', 'like', "%{$search}%")
                                ->orWhere('lastname', 'like', "%{$search}%")
                                ->orWhere('division', 'like', "%{$search}%");
                        });
                });
            })
            ->when(in_array($travelType, ['local', 'foreign'], true), fn ($query) => $query->where('travel_type', $travelType));
    }

    private function sortTravelOrders($query, string $sort)
    {
        return match ($sort) {
            'oldest' => $query->oldest(),
            'travel_date_asc' => $query->orderBy('travel_date_start')->orderBy('created_at'),
            'travel_date_desc' => $query->orderByDesc('travel_date_start')->orderByDesc('created_at'),
            'employee_asc' => $query
                ->orderBy(Employee::select('lastname')->whereColumn('employees.id', 'travel_orders.employee_id'))
                ->orderBy(Employee::select('firstname')->whereColumn('employees.id', 'travel_orders.employee_id')),
            default => $query->latest(),
        };
    }

    private function validateTravelDates(string $startDate, string $endDate): void
    {
        $today = now()->startOfDay();
        $dates = [
            'travel_date_start' => Carbon::parse($startDate)->startOfDay(),
            'travel_date_end' => Carbon::parse($endDate)->startOfDay(),
        ];

        foreach ($dates as $field => $date) {
            if ($date->lt($today) || $date->isWeekend()) {
                throw ValidationException::withMessages([
                    $field => 'Please select today or a future weekday. Saturdays and Sundays are disabled.',
                ]);
            }
        }
    }

    private function generateTravelAuthorityNumber(TravelOrder $travelOrder): string
    {
        $createdAt = $travelOrder->created_at ?? now();

        return sprintf('TA-%s-%03d', $createdAt->format('Y-m-d'), $travelOrder->getKey());
    }

    public function destroy(TravelOrder $travelOrder): RedirectResponse
    {
        abort_unless(in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'hrstaff', 'hr staff'], true), 403);

        $travelOrder->delete();

        AuditLogListener::logActivity(
            'Archive Travel Authority',
            'Travel Authority',
            "Archived travel authority ({$travelOrder->ta_number}) for " . trim(($travelOrder->employee?->firstname ?? '') . ' ' . ($travelOrder->employee?->lastname ?? '')),
            auth()->user()
        );

        return redirect()->back()->with('success', 'Travel authority moved to Archive.');
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->query('per_page', 5);

        return in_array($perPage, [5, 10, 20, 50], true) ? $perPage : 5;
    }
}
