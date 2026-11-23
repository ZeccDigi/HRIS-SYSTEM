<?php

namespace App\Http\Controllers;

use App\Listeners\AuditLogListener;
use App\Models\Employee;
use App\Models\LocatorSlip;
use App\Models\User;
use App\Notifications\LocatorSlipNotification;
use App\Notifications\LocatorSlipQrReadyNotification;
use App\Services\LocatorSlipPdfExporter;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

class LocatorSlipController extends Controller
{
    public function index(Request $request)
    {
        $employee = Auth::user()->employee;
        $search = trim((string) $request->query('search', ''));
        $month = (string) $request->query('month', '');
        $status = (string) $request->query('status', '');
        $year = (string) $request->query('year', now()->year);
        $perPage = (int) $request->query('per_page', 5);
        if (! in_array($perPage, [5, 10, 20, 50], true)) {
            $perPage = 5;
        }

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
            $this->filterLocatorSlips($employeeLocatorQuery, $search, $status, $year, $month),
            'latest'
        )
            ->paginate($perPage)
            ->withQueryString();

        $locatorSlips->getCollection()->transform(
            fn (LocatorSlip $locatorSlip) => $this->appendQrMetadata($locatorSlip)
        );

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

        return view('locator-slip.index', compact('locatorSlips', 'latestRejectedRemark', 'employeeName', 'employeePosition', 'availableYears', 'search', 'month', 'status', 'year', 'perPage'));
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

        $chiefs = $this->chiefUsersForEmployee(Auth::user()->employee);

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
        $type = (string) $request->query('type', '');
        $month = (string) $request->query('month', '');
        $status = $request->query('status', '');
        $sort = $request->query('sort', 'latest');
        $year = (string) $request->query('year', now()->year);
        $perPage = (int) $request->query('per_page', 5);
        if (! in_array($perPage, [5, 10, 20, 50], true)) {
            $perPage = 5;
        }

        $allLocatorSlips = $this->sortLocatorSlips($this->filterLocatorSlips(
            $this->scopeLocatorSlipsForRole(LocatorSlip::with('employee')),
            $search,
            $status,
            $year,
            $month,
            $type
        ), $sort)->paginate($perPage, ['*'], 'all_page')->withQueryString();

        $pendingLocatorSlips = $this->getPendingLocatorSlips($sort, $search, $type, $status, $year, $month, $perPage);

        $tab = $request->query('tab', 'pending');

        if (! in_array($tab, ['all', 'pending'], true)) {
            $tab = 'pending';
        }

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

        return view('locator-slip.hr.manage', compact('allLocatorSlips', 'pendingLocatorSlips', 'tab', 'sort', 'search', 'type', 'month', 'status', 'year', 'perPage', 'availableYears'));
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

        if (strtolower($user->role) === 'chief' && $locatorSlip->status === 'pending' && $this->chiefCanReviewEmployee($locatorSlip->employee)) {
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

            $this->sendApprovedLocatorQrEmail($locatorSlip);

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
        if (strtolower(Auth::user()->role) !== 'chief'
            || strtolower((string) $locatorSlip->status) !== 'pending'
            || ! $this->chiefCanReviewEmployee($locatorSlip->employee)) {
            abort(403);
        }

        if (is_string($request->input('remarks'))) {
            $request->merge(['remarks' => trim($request->input('remarks'))]);
        }
        $validated = $request->validate([
            'remarks' => 'required|string|max:1000',
        ], ['remarks.required' => 'Remarks are required when rejecting a locator slip.']);

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

        if (strtolower(Auth::user()->role) === 'chief' && ! $this->chiefCanReviewEmployee($locatorSlip->employee)) {
            abort(403);
        }

        return view('locator-slip.hr.show', compact('locatorSlip'));
    }

    public function show(Request $request, LocatorSlip $locatorSlip)
    {
        if (Auth::user()->employee?->id !== $locatorSlip->employee_id && ! in_array(strtolower(Auth::user()->role), ['admin', 'hrstaff', 'chief', 'regionaldirector'], true)) {
            abort(403);
        }

        $locatorSlip = $this->appendQrMetadata($locatorSlip->load('employee'));

        if (Auth::user()->employee?->id !== $locatorSlip->employee_id
            && strtolower(Auth::user()->role) === 'chief'
            && ! $this->chiefCanReviewEmployee($locatorSlip->employee)) {
            abort(403);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'out' => $locatorSlip->time_from ? Carbon::parse($locatorSlip->time_from)->format('h:i A') : null,
                'in' => $locatorSlip->time_to ? Carbon::parse($locatorSlip->time_to)->format('h:i A') : null,
            ])->header('Cache-Control', 'private, no-store');
        }
        return view('locator-slip.show', compact('locatorSlip'));
    }

    public function print(
        Request $request,
        LocatorSlip $locatorSlip,
        LocatorSlipPdfExporter $exporter
    ): Response
    {
        if (Auth::user()->employee?->id !== $locatorSlip->employee_id && ! in_array(strtolower(Auth::user()->role), ['admin', 'hrstaff', 'chief', 'regionaldirector'], true)) {
            abort(403);
        }

        $locatorSlip->load('employee.user');

        if (Auth::user()->employee?->id !== $locatorSlip->employee_id
            && strtolower(Auth::user()->role) === 'chief'
            && ! $this->chiefCanReviewEmployee($locatorSlip->employee)) {
            abort(403);
        }

        if ($request->boolean('preview')) {
            return response()->view('locator-slip.print', [
                'locatorSlip' => $locatorSlip,
                'previewMode' => true,
                'pdfMode' => false,
            ]);
        }

        return $exporter->stream($locatorSlip);
    }

    public function edit(LocatorSlip $locatorSlip)
    {
        abort_unless($locatorSlip->canBeEditedBy(Auth::user()->employee), 403);

        return view('locator-slip.edit', compact('locatorSlip'));
    }

    public function update(Request $request, LocatorSlip $locatorSlip)
    {
        abort_unless($locatorSlip->canBeEditedBy(Auth::user()->employee), 403);

        $request->validate([
            'date_covered' => 'required|date',
            'destination' => 'required|string|max:255',
            'type' => 'required|string|in:Official Business,Pass Slip',
            'purpose' => 'required|string',
        ]);

        $this->validateCoveredDate($request->date_covered);

        \Illuminate\Support\Facades\DB::transaction(function () use ($locatorSlip, $request) {
            $current = LocatorSlip::query()->lockForUpdate()->findOrFail($locatorSlip->id);
            abort_unless($current->canBeEditedBy($request->user()->employee), 403);
            $current->update($request->only(['date_covered', 'destination', 'type', 'purpose']));
        });

        return redirect()->route('locator-slips.show', $locatorSlip)->with('message', 'Locator slip updated successfully.');
    }

    public function scan(Request $request, LocatorSlip $locatorSlip)
    {
        abort_unless($request->hasValidSignature(), 403);
        return response()->view('locator-slip.scan', [
            'title' => 'Locator Slip QR',
            'message' => 'Open the current QR in HRIS and present it to the locator-slip kiosk camera. Opening this link does not record OUT or IN time.',
            'status' => 'info',
            'locatorSlip' => $locatorSlip->load('employee'),
        ]);
    }

    private function getPendingLocatorSlips(string $sort = 'latest', string $search = '', string $type = '', string $status = '', string $year = '', string $month = '', int $perPage = 5)
    {
        $query = $this->scopeLocatorSlipsForRole(LocatorSlip::with('employee'));
        $query->where('status', 'pending');

        if ($status !== '' && $status !== 'pending') {
            $query->whereRaw('1 = 0');
        }

        return $this->sortLocatorSlips($this->filterLocatorSlips($query, $search, '', $year, $month, $type), $sort)
            ->paginate($perPage, ['*'], 'pending_page')
            ->withQueryString();
    }

    private function scopeLocatorSlipsForRole($query)
    {
        if (strtolower(Auth::user()->role ?? '') !== 'chief') {
            return $query;
        }

        $division = $this->currentChiefDivision();

        if ($division === '') {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('employee', fn ($employeeQuery) => $employeeQuery->where('division', $division));
    }

    private function chiefUsersForEmployee(?Employee $employee)
    {
        $division = trim((string) $employee?->division);

        if ($division === '') {
            return collect();
        }

        return User::whereHas('employee', function ($query) use ($division) {
            $query
                ->whereIn('account_role', ['chief', 'CHIEF'])
                ->where('division', $division);
        })->get();
    }

    private function chiefCanReviewEmployee(?Employee $employee): bool
    {
        $chiefDivision = $this->currentChiefDivision();
        $employeeDivision = trim((string) $employee?->division);

        return $chiefDivision !== '' && $employeeDivision !== '' && strcasecmp($chiefDivision, $employeeDivision) === 0;
    }

    private function currentChiefDivision(): string
    {
        return trim((string) Auth::user()?->employee?->division);
    }

    private function filterLocatorSlips($query, string $search, string $status, string $year = '', string $month = '', string $type = '')
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
            ->when($status === 'pending', fn ($query) => $query->where('status', 'pending'))
            ->when($status === 'approved', fn ($query) => $query->whereIn('status', ['approved', 'approved by chief']))
            ->when($status === 'rejected', fn ($query) => $query->where('status', 'rejected'))
            ->when($year !== '', fn ($query) => $query->whereYear('date_covered', $year))
            ->when((int) $month >= 1 && (int) $month <= 12, fn ($query) => $query->whereMonth('date_covered', (int) $month));
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

        if ($coveredDate->lt(today()) || ($coveredDate->isWeekend() && !config('locator_slips.allow_weekends'))) {
            throw ValidationException::withMessages([
                'date_covered' => config('locator_slips.allow_weekends') ? 'Please select today or a future date.' : 'Please select today or a future weekday. Saturdays and Sundays are disabled.',
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
        if (!$locatorSlip->qr_token) {
            // Keep one unpredictable token per slip, including concurrent page/email loads.
            LocatorSlip::whereKey($locatorSlip->id)->whereNull('qr_token')->update(['qr_token' => bin2hex(random_bytes(16))]);
            $locatorSlip->qr_token = LocatorSlip::whereKey($locatorSlip->id)->value('qr_token');
        }
        $locatorSlip->qr_payload = 'LSQR:'.strtoupper($locatorSlip->qr_token);
        $locatorSlip->qr_svg = $this->buildQrSvg($locatorSlip->qr_payload);

        return $locatorSlip;
    }

    private function sendApprovedLocatorQrEmail(LocatorSlip $locatorSlip): void
    {
        $locatorSlip = $this->appendQrMetadata($locatorSlip->fresh(['employee.user']));
        $user = $locatorSlip->employee?->user;

        if (! $user || ! $locatorSlip->employee?->notification_email) {
            return;
        }

        try {
            $user->notify(new LocatorSlipQrReadyNotification(
                $locatorSlip,
                $locatorSlip->qr_svg,
                $this->buildQrPngData($locatorSlip->qr_payload)
            ));
        } catch (\Throwable $exception) {
            Log::warning('Unable to send approved locator slip QR email.', [
                'locator_slip_id' => $locatorSlip->id,
                'employee_id' => $locatorSlip->employee_id,
                'message' => $exception->getMessage(),
            ]);
        }
    }

    private function buildQrSvg(string $value): ?string
    {
        $barcodeClass = base_path('vendor/tecnickcom/tcpdf/tcpdf_barcodes_2d.php');

        if (! file_exists($barcodeClass)) {
            return null;
        }

        require_once $barcodeClass;

        try {
            $barcode = new \TCPDF2DBarcode($value, 'QRCODE,M');
            $matrix = $barcode->getBarcodeArray();
            $size = $matrix['num_cols'] + 8;
            $svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$size.' '.$size.'" width="'.($size * 8).'" height="'.($size * 8).'" shape-rendering="crispEdges"><rect width="100%" height="100%" fill="white"/><g fill="black">';
            foreach ($matrix['bcode'] as $row => $cells) {
                foreach ($cells as $column => $dark) {
                    if ($dark) {
                        $svg .= '<rect x="'.($column + 4).'" y="'.($row + 4).'" width="1" height="1"/>';
                    }
                }
            }
            return $svg.'</g></svg>';
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function buildQrPngData(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $barcodeClass = base_path('vendor/tecnickcom/tcpdf/tcpdf_barcodes_2d.php');

        if (! file_exists($barcodeClass)) {
            return null;
        }

        require_once $barcodeClass;

        try {
            $barcode = new \TCPDF2DBarcode($value, 'QRCODE,M');
            $png = $barcode->getBarcodePngData(8, 8, [0, 0, 0]);

            if ($png instanceof \Imagick) {
                $png->setImageBackgroundColor('white');
                $png = $png->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
                $png->borderImage('white', 32, 32);
                $png->setImageFormat('png');

                return $png->getImagesBlob();
            }

            if (!is_string($png) || !function_exists('imagecreatefromstring')) {
                return null;
            }
            $source = imagecreatefromstring($png);
            if ($source === false) {
                return null;
            }
            $image = imagecreatetruecolor(imagesx($source) + 64, imagesy($source) + 64);
            if ($image === false) {
                return null;
            }
            imagefill($image, 0, 0, imagecolorallocate($image, 255, 255, 255));
            imagecopy($image, $source, 32, 32, 0, 0, imagesx($source), imagesy($source));
            ob_start();
            imagepng($image);
            return ob_get_clean();
        } catch (\Throwable $exception) {
            Log::warning('Unable to generate locator slip QR PNG.', [
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    public function destroy(LocatorSlip $locatorSlip): RedirectResponse
    {
        abort_unless(in_array(strtolower(auth()->user()->role ?? ''), ['admin', 'hrstaff', 'hr staff'], true), 403);

        $locatorSlip->delete();

        AuditLogListener::logActivity(
            'Archive Locator Slip',
            'Locator Slip',
            "Archived locator slip (#{$locatorSlip->id}) for " . trim(($locatorSlip->employee?->firstname ?? '') . ' ' . ($locatorSlip->employee?->lastname ?? '')),
            auth()->user()
        );

        return redirect()->back()->with('success', 'Locator slip moved to Archive.');
    }
}
