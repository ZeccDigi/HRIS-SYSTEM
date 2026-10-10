<?php

namespace App\Http\Controllers;

use App\Listeners\AuditLogListener;
use App\Models\LocatorSlip;
use App\Models\User;
use App\Notifications\LocatorSlipNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class LocatorSlipController extends Controller
{
    public function index(Request $request)
    {
        $employee = Auth::user()->employee;
        $search = trim((string) $request->query('search', ''));
        $type = (string) $request->query('type', '');
        $status = (string) $request->query('status', '');
        $year = (string) $request->query('year', now()->year);

        $employeeLocatorQuery = LocatorSlip::query()
            ->where('employee_id', $employee->id);

        $availableYears = LocatorSlip::query()
            ->where('employee_id', $employee->id)
            ->get(['date_covered', 'created_at'])
            ->map(function (LocatorSlip $slip) {
                $date = $slip->date_covered ?? $slip->created_at;

                return $date ? Carbon::parse($date)->year : null;
            })
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        if (! $availableYears->contains(now()->year)) {
            $availableYears->prepend(now()->year);
        }

        $locatorSlips = $this->sortLocatorSlips(
            $this->filterLocatorSlips($employeeLocatorQuery, $search, $type, $status, $year),
            'latest'
        )
            ->get()
            ->map(fn (LocatorSlip $locatorSlip) => $this->appendQrMetadata($locatorSlip));

        $latestRejectedRemark = LocatorSlip::query()
            ->where('employee_id', $employee->id)
            ->where('status', 'rejected')
            ->whereNotNull('chief_remarks')
            ->latest()
            ->value('chief_remarks');

        $employeeName = trim(collect([
            $employee?->firstname,
            $employee?->middlename,
            $employee?->lastname,
            $employee?->suffix,
        ])->filter()->implode(' '));

        $employeePosition = $employee?->position ?: 'N/A';

        return view('locator-slip.index', compact('locatorSlips', 'latestRejectedRemark', 'employeeName', 'employeePosition', 'availableYears', 'search', 'type', 'status', 'year'));
    }

    public function create()
    {
        $employee = Auth::user()->employee;

        $employeeName = trim(collect([
            $employee?->firstname,
            $employee?->middlename,
            $employee?->lastname,
            $employee?->suffix,
        ])->filter()->implode(' '));

        $employeePosition = $employee?->position ?: 'N/A';

        return view('locator-slip.create', compact('employeeName', 'employeePosition'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date_covered' => 'required|date',
            'destination' => 'required|string|max:255',
            'purpose' => 'required|string',
            'type' => 'required|string|in:Official Business,Pass Slip',
        ]);

        $this->validateCoveredDate($request->date_covered);

        $locatorSlip = LocatorSlip::create([
            'employee_id' => Auth::user()->employee->id,
            'date_covered' => $request->date_covered,
            'destination' => $request->destination,
            'purpose' => $request->purpose,
            'type' => $request->type,
            'time_from' => null,
            'time_to' => null,
            'status' => 'pending',
        ]);

        $chiefs = User::whereHas('employee', function ($query) {
            $query->where('account_role', 'CHIEF');
        })->get();

        foreach ($chiefs as $chief) {
            $chief->notify(new LocatorSlipNotification(
                $locatorSlip,
                'New Locator Slip',
                Auth::user()->employee->firstname . ' ' . Auth::user()->employee->lastname . ' submitted a new locator slip.'
            ));
        }

        AuditLogListener::logActivity(
            'Submit Locator Slip',
            'Attendance',
            "Submitted locator slip ({$locatorSlip->type}) to {$locatorSlip->destination} for {$locatorSlip->date_covered}",
            Auth::user()
        );

        return redirect()->route('locator-slips.index')->with('message', 'Locator slip submitted successfully.');
    }

    public function manageIndex(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $type = $request->query('type', '');
        $status = $request->query('status', '');
        $sort = $request->query('sort', 'latest');
        $year = (string) $request->query('year', now()->year);

        $allLocatorSlips = $this->sortLocatorSlips($this->filterLocatorSlips(
            LocatorSlip::with('employee')->whereIn('status', ['approved', 'rejected', 'approved by chief']),
            $search,
            $type,
            $status,
            $year
        ), $sort)->get();

        $pendingLocatorSlips = $this->getPendingLocatorSlips($sort, $search, $type, $status, $year);

        $tab = $request->query('tab', 'pending');

        if (! in_array($tab, ['all', 'pending'], true)) {
            $tab = 'pending';
        }

        $locatorTypes = collect(['Official Business', 'Pass Slip']);
        $availableYears = LocatorSlip::query()
            ->get(['date_covered', 'created_at'])
            ->map(function (LocatorSlip $slip) {
                $date = $slip->date_covered ?? $slip->created_at;

                return $date ? Carbon::parse($date)->year : null;
            })
            ->filter()
            ->unique()
            ->sortDesc()
            ->values();

        if (! $availableYears->contains(now()->year)) {
            $availableYears->prepend(now()->year);
        }

        return view('locator-slip.hr.manage', compact('allLocatorSlips', 'pendingLocatorSlips', 'tab', 'sort', 'search', 'type', 'status', 'year', 'locatorTypes', 'availableYears'));
    }

    public function allIndex()
    {
        return redirect()->route('hr.locator-slips.index', ['tab' => 'all']);
    }

    public function pendingIndex()
    {
        return redirect()->route('hr.locator-slips.index', ['tab' => 'pending']);
    }

    public function approve(LocatorSlip $locatorSlip)
    {
        $user = Auth::user();

        if (strtolower($user->role) === 'chief' && $locatorSlip->status === 'pending') {
            $validated = request()->validate([
                'remarks' => 'nullable|string|max:1000',
            ]);

            $approverRealName = trim(($user->employee?->firstname ?? '') . ' ' . ($user->employee?->lastname ?? '')) ?: $user->name;

            $locatorSlip->status = 'approved';
            $locatorSlip->approved_by_chief_id = $user->id;
            $locatorSlip->approved_by_chief_name = $approverRealName;
            $locatorSlip->chief_remarks = $validated['remarks'] ?? null;
            $locatorSlip->chief_approval_date = now();
            $locatorSlip->save();

            $locatorSlip->employee->user?->notify(new LocatorSlipNotification(
                $locatorSlip,
                'Locator Slip Approved',
                'Your locator slip for ' . $locatorSlip->date_covered . ' has been approved.'
            ));

            AuditLogListener::logActivity(
                'Approve Locator Slip',
                'Attendance',
                "Approved locator slip (#{$locatorSlip->control_no}) for employee " . trim(($locatorSlip->employee->firstname ?? '') . ' ' . ($locatorSlip->employee->lastname ?? '')),
                $user
            );

            return redirect()->back()->with('message', 'Locator slip approved.');
        }

        return redirect()->back()->with('error', 'You are not authorized to approve this locator slip or it is not in a valid state for approval.');
    }

    public function reject(Request $request, LocatorSlip $locatorSlip)
    {
        if (strtolower(Auth::user()->role) !== 'chief' || strtolower((string) $locatorSlip->status) !== 'pending') {
            abort(403);
        }

        $validated = $request->validate([
            'remarks' => 'nullable|string|max:1000',
        ]);

        $locatorSlip->status = 'rejected';
        $locatorSlip->chief_remarks = $validated['remarks'] ?? null;
        $locatorSlip->save();

        $locatorSlip->employee->user->notify(new LocatorSlipNotification(
            $locatorSlip,
            'Locator Slip Rejected',
            'Your locator slip for ' . $locatorSlip->date_covered . ' has been rejected.'
        ));

        AuditLogListener::logActivity(
            'Reject Locator Slip',
            'Attendance',
            "Rejected locator slip (#{$locatorSlip->control_no}) for employee " . trim(($locatorSlip->employee->firstname ?? '') . ' ' . ($locatorSlip->employee->lastname ?? '')),
            Auth::user()
        );

        return redirect()->back()->with('message', 'Locator slip rejected.');
    }

    public function hrShow(LocatorSlip $locatorSlip)
    {
        if (! in_array(strtolower(Auth::user()->role), ['admin', 'hrstaff', 'chief', 'regionaldirector'], true)) {
            abort(403);
        }

        $locatorSlip->load('employee');

        return view('locator-slip.hr.show', compact('locatorSlip'));
    }

    public function show(LocatorSlip $locatorSlip)
    {
        if (Auth::user()->employee?->id !== $locatorSlip->employee_id && ! in_array(strtolower(Auth::user()->role), ['admin', 'hrstaff', 'chief', 'regionaldirector'], true)) {
            abort(403);
        }

        $locatorSlip = $this->appendQrMetadata($locatorSlip->load('employee'));

        return view('locator-slip.show', compact('locatorSlip'));
    }

    public function print(LocatorSlip $locatorSlip)
    {
        if (Auth::user()->employee?->id !== $locatorSlip->employee_id && ! in_array(strtolower(Auth::user()->role), ['admin', 'hrstaff', 'chief', 'regionaldirector'], true)) {
            abort(403);
        }

        $locatorSlip->load('employee.user');

        return view('locator-slip.print', compact('locatorSlip'));
    }

    public function edit(LocatorSlip $locatorSlip)
    {
        if (Auth::user()->employee->id !== $locatorSlip->employee_id) {
            abort(403);
        }

        return view('locator-slip.edit', compact('locatorSlip'));
    }

    public function update(Request $request, LocatorSlip $locatorSlip)
    {
        if (Auth::user()->employee->id !== $locatorSlip->employee_id) {
            abort(403);
        }

        $request->validate([
            'date_covered' => 'required|date',
            'destination' => 'required|string|max:255',
            'type' => 'required|string|in:Official Business,Pass Slip',
            'purpose' => 'required|string',
        ]);

        $this->validateCoveredDate($request->date_covered);

        $locatorSlip->update([
            'date_covered' => $request->date_covered,
            'destination' => $request->destination,
            'type' => $request->type,
            'purpose' => $request->purpose,
        ]);

        return redirect()->route('locator-slips.show', $locatorSlip)->with('message', 'Locator slip updated successfully.');
    }

    public function scan(Request $request, LocatorSlip $locatorSlip)
    {
        abort_unless($request->hasValidSignature(), 403);

        if (! in_array(strtolower((string) $locatorSlip->status), ['approved', 'approved by chief'], true)) {
            return response()->view('locator-slip.scan', [
                'title' => 'Locator Slip QR',
                'message' => 'This locator slip is not approved for QR scanning yet.',
                'status' => 'warning',
                'locatorSlip' => $locatorSlip->load('employee'),
            ], 422);
        }

        $now = now();

        if (! $locatorSlip->time_from) {
            $locatorSlip->time_from = $now->format('H:i:s');
            $locatorSlip->save();

            $message = 'OUT time recorded successfully.';
            $status = 'success';
        } elseif (! $locatorSlip->time_to) {
            $locatorSlip->time_to = $now->format('H:i:s');
            $locatorSlip->save();

            $message = 'IN time recorded successfully.';
            $status = 'success';
        } else {
            $message = 'Both OUT and IN times have already been recorded for this locator slip.';
            $status = 'info';
        }

        return view('locator-slip.scan', [
            'title' => 'Locator Slip QR',
            'message' => $message,
            'status' => $status,
            'locatorSlip' => $locatorSlip->fresh()->load('employee'),
        ]);
    }

    private function getPendingLocatorSlips(string $sort = 'latest', string $search = '', string $type = '', string $status = '', string $year = '')
    {
        $query = LocatorSlip::with('employee');
        $query->where('status', 'pending');

        if ($status !== '' && $status !== 'pending') {
            $query->whereRaw('1 = 0');
        }

        return $this->sortLocatorSlips($this->filterLocatorSlips($query, $search, $type, '', $year), $sort)
            ->get();
    }

    private function filterLocatorSlips($query, string $search, string $type, string $status, string $year = '')
    {
        return $query
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query
                        ->where('destination', 'like', "%{$search}%")
                        ->orWhere('type', 'like', "%{$search}%")
                        ->orWhere('status', 'like', "%{$search}%")
                        ->orWhere('date_covered', 'like', "%{$search}%")
                        ->orWhereHas('employee', function ($employeeQuery) use ($search) {
                            $employeeQuery
                                ->where('firstname', 'like', "%{$search}%")
                                ->orWhere('middlename', 'like', "%{$search}%")
                                ->orWhere('lastname', 'like', "%{$search}%");
                        });
                });
            })
            ->when($type !== '', function ($query) use ($type) {
                $normalizedType = match (strtolower($type)) {
                    'pass slip', 'personal' => 'Pass Slip',
                    'official business' => 'Official Business',
                    default => $type,
                };

                $query->where(function ($query) use ($normalizedType) {
                    $query->where('type', $normalizedType);

                    if ($normalizedType === 'Pass Slip') {
                        $query->orWhere('type', 'Personal');
                    }
                });
            })
            ->when($status === 'approved', fn ($query) => $query->whereIn('status', ['approved', 'approved by chief']))
            ->when($status === 'rejected', fn ($query) => $query->where('status', 'rejected'))
            ->when($year !== '', fn ($query) => $query->whereYear('date_covered', $year));
    }

    private function sortLocatorSlips($query, string $sort)
    {
        return match ($sort) {
            'oldest' => $query->oldest(),
            'date_asc' => $query->orderBy('date_covered')->orderBy('created_at'),
            'date_desc' => $query->orderByDesc('date_covered')->orderByDesc('created_at'),
            'employee_asc' => $query
                ->orderBy(\App\Models\Employee::select('lastname')->whereColumn('employees.id', 'locator_slips.employee_id'))
                ->orderBy(\App\Models\Employee::select('firstname')->whereColumn('employees.id', 'locator_slips.employee_id')),
            default => $query->latest(),
        };
    }

    private function validateCoveredDate(string $date): void
    {
        $coveredDate = Carbon::parse($date)->startOfDay();

        if ($coveredDate->lt(today()) || $coveredDate->isWeekend()) {
            throw ValidationException::withMessages([
                'date_covered' => 'Please select today or a future weekday. Saturdays and Sundays are disabled.',
            ]);
        }
    }

    private function appendQrMetadata(LocatorSlip $locatorSlip): LocatorSlip
    {
        $status = strtolower((string) $locatorSlip->status);

        if (! in_array($status, ['approved', 'approved by chief'], true)) {
            $locatorSlip->scan_url = null;
            $locatorSlip->qr_svg = null;

            return $locatorSlip;
        }

        $scanUrl = URL::temporarySignedRoute(
            'locator-slips.scan',
            now()->addDays(30),
            ['locatorSlip' => $locatorSlip->id]
        );

        $locatorSlip->scan_url = $scanUrl;
        $locatorSlip->qr_svg = $this->buildQrSvg($scanUrl);

        return $locatorSlip;
    }

    private function buildQrSvg(string $value): ?string
    {
        $barcodeClass = base_path('vendor/tecnickcom/tcpdf/tcpdf_barcodes_2d.php');

        if (! file_exists($barcodeClass)) {
            return null;
        }

        require_once $barcodeClass;

        try {
            $barcode = new \TCPDF2DBarcode($value, 'QRCODE,H');

            return $barcode->getBarcodeSVGCode(5, 5, 'black');
        } catch (\Throwable $exception) {
            return null;
        }
    }
}
