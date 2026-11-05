<?php

namespace App\Http\Controllers;

use App\Listeners\AuditLogListener;
use App\Models\Employee;
use App\Models\TravelOrder;
use App\Models\User;
use App\Notifications\TravelOrderNotification;
use App\Services\TravelAuthorityPdfExporter;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
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
            ->paginate(5)
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

    public function store(Request $request): RedirectResponse|Response
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

        $this->validateTravelDates($validated['travel_date_start'], $validated['travel_date_end']);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('travel-order-attachments', 'public');
        }

        $travelOrder = DB::transaction(function () use ($employee, $validated, $attachmentPath) {
            $travelOrder = $employee->travelOrders()->create([
                'ta_number' => $this->generateTravelAuthorityNumber(),
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
                'tar_deadline' => Carbon::parse($validated['travel_date_end'])->addDays(5)->toDateString(),
                'tar_status' => 'pending',
                'chief_status' => 'pending',
                'hrstaff_status' => 'pending',
            ]);

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
            'dmwLogoPath' => asset('images/dmw.png'),
            'bagongPilipinasLogoPath' => asset('images/bagong-pilipinas-logo.png'),
            'regionalOffice' => 'REGIONAL OFFICE XI, DAVAO CITY',
            'requestingOffice' => 'Regional Office XI',
            'notesRemarks' => $travelOrder->notes_remarks ?: '',
            'driverName' => $travelOrder->driver_name ?: '',
            'vehiclePlateNo' => $travelOrder->vehicle_plate_no ?: '',
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
        $travelOrder->loadMissing(['employee.user', 'companions', 'recordsOfficer', 'regionalDirector']);

        $divisionHead = $this->divisionHeadFor($travelOrder);

        return view('travel-orders.acknowledgement-report', compact('travelOrder', 'divisionHead'));
    }

    public function printAcknowledgementReport(TravelOrder $travelOrder): View
    {
        $this->authorizeTravelOrderAccess($travelOrder);
        $travelOrder->loadMissing(['employee.user', 'companions', 'recordsOfficer', 'regionalDirector']);

        $divisionHead = $this->divisionHeadFor($travelOrder);

        return view('travel-orders.acknowledgement-report-print', compact('travelOrder', 'divisionHead'));
    }

    public function viewAcknowledgementReportFile(TravelOrder $travelOrder)
    {
        $this->authorizeTravelOrderAccess($travelOrder);

        if (! $travelOrder->tar_attachment_path || ! Storage::disk('public')->exists($travelOrder->tar_attachment_path)) {
            abort(404);
        }

        $fileName = ($travelOrder->ta_number ?: 'travel-authority-report') . '.pdf';

        return Storage::disk('public')->response($travelOrder->tar_attachment_path, $fileName, [
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
        ]);

        $role = strtolower((string) auth()->user()->role);
        $employeeId = auth()->user()->employee?->id;
        $remarks = $validated['remarks'] ?? null;

        DB::transaction(function () use ($validated, $travelOrder, $role, $employeeId, $remarks) {
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
            }

            if ($role === 'regionaldirector') {
                if (($travelOrder->recordofficer_status ?? 'pending') !== 'approved' || ($travelOrder->rd_status ?? 'pending') !== 'pending') {
                    return;
                }

                $travelOrder->approved_by_regionaldirector = $employeeId;
                $travelOrder->rd_remarks = $remarks;
                $travelOrder->rd_status = $validated['status'];
                $travelOrder->status = $validated['status'] === 'approved' ? 'approved' : 'rejected';

                $travelOrder->employee?->user?->notify(new TravelOrderNotification(
                    $travelOrder,
                    $validated['status'] === 'approved' ? 'Travel Authority Approved' : 'Travel Authority Rejected',
                    $validated['status'] === 'approved'
                        ? "Your travel authority to {$travelOrder->places_of_travel} has been approved by the Regional Director."
                        : "Your travel authority to {$travelOrder->places_of_travel} was rejected by the Regional Director."
                ));
            }

            $travelOrder->save();
        });

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

        $validated = $request->validate([
            'tar_activity' => 'required|string|max:255',
            'tar_activity_date' => 'required|date',
            'tar_venue' => 'required|string|max:255',
            'tar_participants_present' => 'required|string|max:2000',
            'tar_dmw_participants' => 'required|string|max:2000',
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

        $attachmentPath = $travelOrder->tar_attachment_path;
        if ($request->hasFile('tar_attachment')) {
            if ($attachmentPath) {
                Storage::disk('public')->delete($attachmentPath);
            }

            $attachmentPath = $request->file('tar_attachment')->store('travel-accomplishment-reports', 'public');
        }

        $travelOrder->update([
            'tar_attachment_path' => $attachmentPath,
            'tar_submitted_at' => now(),
            'tar_status' => 'submitted',
            'tar_review_status' => 'pending_hr',
            'tar_hr_status' => 'pending',
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
            'tar_activity' => $validated['tar_activity'],
            'tar_activity_date' => $validated['tar_activity_date'],
            'tar_venue' => $validated['tar_venue'],
            'tar_participants_present' => $validated['tar_participants_present'],
            'tar_dmw_participants' => $validated['tar_dmw_participants'],
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
        $viewers = User::whereHas('employee', function ($query) {
            $query->whereIn('account_role', ['admin', 'hrstaff']);
        })->get();

        Notification::send($viewers, new TravelOrderNotification(
            $travelOrder,
            'TAR Submitted',
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
        $stage = (string) ($travelOrder->tar_review_status ?: 'pending_hr');
        $isReturn = $validated['action'] === 'return';
        $remarks = $validated['remarks'] ?? null;

        DB::transaction(function () use ($travelOrder, $role, $reviewer, $stage, $isReturn, $remarks) {
            if (in_array($role, ['admin', 'hrstaff'], true)) {
                abort_unless($stage === 'pending_hr', 403);
                $travelOrder->tar_hr_status = $isReturn ? 'returned' : 'approved';
                $travelOrder->tar_reviewed_by_hr = $reviewer?->id;
                $travelOrder->tar_hr_remarks = $remarks;
                $travelOrder->tar_review_status = $isReturn ? 'returned' : 'pending_chief';
            } elseif ($role === 'chief') {
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

    private function generateTravelAuthorityNumber(): string
    {
        $today = now();
        $datePart = $today->format('Y-m-d');

        $sequence = TravelOrder::query()
            ->lockForUpdate()
            ->count() + 1;

        return sprintf('TA-%s-%03d', $datePart, $sequence);
    }
}
