<?php

namespace App\Http\Controllers;

use App\Listeners\AuditLogListener;
use App\Mail\HrisAccountCreatedMail;
use App\Models\Employee;
use App\Models\LeaveCreditTransaction;
use App\Models\User;
use App\Models\UtilityOption;
use App\Support\UtilityOptionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class EmployeeController extends Controller
{
    private function divisionOptions(): array
    {
        UtilityOptionRegistry::ensureDefaults();

        return UtilityOption::listFor('divisions')
            ->pluck('value')
            ->values()
            ->all();
    }

    public function index(Request $request): View
    {
        $divisionOptions = $this->divisionOptions();
        $search = trim((string) $request->query('search', ''));
        $division = (string) $request->query('division', '');
        $employmentStatus = (string) $request->query('employment_status', '');
        $sort = $request->query('sort', 'name_asc');
        $employeeYears = $this->employeeYears();
        $defaultEmployeeYear = 0;
        $year = $request->integer('year', $defaultEmployeeYear);
        $year = $employeeYears->containsStrict($year) ? $year : $defaultEmployeeYear;
        $perPage = $this->perPage($request, 'per_page');
        $archivePerPage = $this->perPage($request, 'archive_per_page');

        $employees = $this->employeeIndexQuery($search, $division, $employmentStatus, $year, $sort, $divisionOptions)
            ->paginate($perPage, ['*'], 'employees_page')
            ->withQueryString();
        $archivedEmployees = Employee::onlyTrashed()
            ->with('user')
            ->latest('deleted_at')
            ->paginate($archivePerPage, ['*'], 'archive_page')
            ->withQueryString();

        return view('employees.index', compact('employees', 'archivedEmployees', 'divisionOptions', 'search', 'division', 'employmentStatus', 'employeeYears', 'defaultEmployeeYear', 'year', 'sort', 'perPage', 'archivePerPage'));
    }

    public function filter(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $division = (string) $request->query('division', '');
        $employmentStatus = (string) $request->query('employment_status', '');
        $sort = $request->query('sort', 'name_asc');
        $employeeYears = $this->employeeYears();
        $defaultEmployeeYear = 0;
        $year = $request->integer('year', $defaultEmployeeYear);
        $year = $employeeYears->containsStrict($year) ? $year : $defaultEmployeeYear;
        $perPage = $this->perPage($request, 'per_page');
        $employees = $this->employeeIndexQuery($search, $division, $employmentStatus, $year, $sort, $this->divisionOptions())
            ->paginate($perPage, ['*'], 'employees_page')
            ->withPath(route('employees.index'))
            ->withQueryString();

        return response()->json([
            'html' => view('employees.partials.rows', compact('employees'))->render(),
            'count' => $employees->total(),
            'pagination' => view('partials.table-pagination', [
                'paginator' => $employees,
                'label' => __('employees'),
                'perPageOptions' => [5, 10, 20, 50],
                'perPageSelectId' => 'employees_per_page',
                'formAction' => route('employees.index'),
            ])->render(),
        ]);
    }

    private function employeeIndexQuery(string $search, string $division, string $employmentStatus, int $year, string $sort, array $divisionOptions)
    {
        $query = Employee::with('user')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('firstname', 'like', "%{$search}%")
                        ->orWhere('lastname', 'like', "%{$search}%")
                        ->orWhere('middlename', 'like', "%{$search}%")
                        ->orWhere('rfid_number', 'like', "%{$search}%")
                        ->orWhere('position', 'like', "%{$search}%")
                        ->orWhere('division', 'like', "%{$search}%");
                });
            })
            ->when(in_array($division, $divisionOptions, true), fn ($query) => $query->where('division', $division))
            ->when(in_array($employmentStatus, ['Regular', 'Contract of Service', 'Job Order'], true), fn ($query) => $query->where('employment_status', $employmentStatus))
            // Match the service-start year exactly; creation year is the legacy fallback.
            ->when($year > 0, fn ($query) => $query->where(function ($query) use ($year) {
                $query->whereYear('service_start_date', $year)
                    ->orWhere(function ($query) use ($year) {
                        $query->whereNull('service_start_date')->whereYear('created_at', $year);
                    });
            }));

        return match ($sort) {
            'name_desc' => $query->orderByDesc('lastname')->orderByDesc('firstname'),
            'division_asc' => $query->orderBy('division')->orderBy('lastname')->orderBy('firstname'),
            'newest' => $query->latest(),
            default => $query->orderBy('lastname')->orderBy('firstname'),
        };
    }

    private function employeeYears(): \Illuminate\Support\Collection
    {
        $currentYear = (int) now()->year;
        $years = Employee::query()->get(['service_start_date', 'created_at'])
            ->map(fn (Employee $employee) => (int) ($employee->service_start_date?->year ?? $employee->created_at?->year ?? $currentYear))
            ->filter(fn ($year) => $year >= 2000 && $year <= $currentYear)
            ->unique()->sortDesc()->values();
        return $years->isEmpty() ? collect([$currentYear]) : $years;
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $roles = ['employee', 'hrstaff', 'recordofficer', 'chief', 'regionaldirector', 'admin'];
        $divisionOptions = $this->divisionOptions();
        $employmentStatuses = ['Regular', 'Job Order', 'Contract of Service'];

        return view('employees.create', compact('roles', 'divisionOptions', 'employmentStatuses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'hris_username' => Str::lower(trim((string) $request->input('hris_username'))),
            'notification_email' => Str::lower(trim((string) $request->input('notification_email'))),
        ]);

        $validated = $request->validate([
            'lastname' => 'required|string|max:255',
            'firstname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'contact_number' => 'required|string|digits:11',
            'notification_email' => ['required', 'email', 'max:255', Rule::unique('employees', 'notification_email')],
            'division' => ['required', 'string', Rule::in($this->divisionOptions())],
            'position' => 'required|string|max:255',
            'account_role' => 'required|string|in:employee,hrstaff,recordofficer,chief,regionaldirector,admin',
            'employment_status' => 'required|string|in:Regular,Job Order,Contract of Service',
            'service_start_date' => 'required|date',
            'hris_username' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9._-]+$/'],
            'rfid_number' => 'nullable|string|max:255|unique:employees,rfid_number',
        ], [
            'notification_email.required' => 'The Recovery / OTP Email field is required.',
            'notification_email.unique' => 'This Recovery / OTP Email is already assigned to another employee.',
            'hris_username.required' => 'The HRIS Username field is required.',
            'hris_username.regex' => 'The HRIS Username may only contain lowercase letters, numbers, dots, underscores, and hyphens.',
            'rfid_number.unique' => 'This RFID Number is already assigned to another employee.',
        ]);

        $validated = [...$validated, ...app(\App\Services\EmployeeSalaryService::class)->values($request)];
        $loginUsername = $validated['hris_username'].'@dmw.gov.ph';

        if (User::where('email', $loginUsername)->exists()) {
            return back()
                ->withErrors(['hris_username' => 'This HRIS Username is already assigned to another employee.'])
                ->withInput();
        }

        $createdAccount = null;
        $temporaryPassword = null;
        $notificationEmail = null;

        DB::transaction(function () use (&$validated, &$createdAccount, &$temporaryPassword, &$notificationEmail, $loginUsername) {
            $temporaryPassword = Str::password(12, true, true, true, false);
            $notificationEmail = $validated['notification_email'];
            $user = User::create([
                'name' => trim($validated['firstname'].' '.$validated['lastname']),
                'email' => $loginUsername,
                'password' => $temporaryPassword,
                'email_verified_at' => now(),
                'is_approved' => true,
                'must_change_password' => true,
                'account_status' => 'active',
                'privacy_consent' => false,
            ]);

            $validated['user_id'] = $user->id;
            $createdAccount = $user;

            unset($validated['hris_username']);
            $newEmployee = Employee::create($validated);
            app(\App\Services\EmployeeSalaryService::class)->syncPds($newEmployee);
            $newEmployee->ensureLeaveCredits((int) now()->year);
        });

        if ($createdAccount && $temporaryPassword) {
            try {
                Mail::to($notificationEmail)->send(new HrisAccountCreatedMail(
                    $createdAccount->name,
                    $createdAccount->email,
                    $temporaryPassword,
                ));
            } catch (Throwable $exception) {
                Log::warning('Unable to send HRIS account creation email.', [
                    'user_id' => $createdAccount->id,
                    'hris_username' => $createdAccount->email,
                    'notification_email' => $notificationEmail,
                    'message' => $exception->getMessage(),
                ]);

                return redirect()->route('employees.index')
                    ->with('success', 'Employee created successfully.')
                    ->with('error', 'The login account was created, but the account details could not be sent to the Recovery / OTP Email. Please check the mail settings.')
                    ->with('account_credentials', [
                        'hris_username' => $createdAccount->email,
                        'recovery_email' => $notificationEmail,
                        'temporary_password' => $temporaryPassword,
                    ]);
            }
        }

        AuditLogListener::logActivity(
            'Create Employee',
            'Employees',
            'Created new employee record for ' . trim(($validated['firstname'] ?? '') . ' ' . ($validated['lastname'] ?? '')),
            auth()->user()
        );

        return redirect()
            ->route('employees.index')
            ->with('success', "Employee created successfully. Account details were sent to {$notificationEmail}.");
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Employee $employee, \App\Services\PdsSnapshotService $snapshots): View
    {
        $employee->ensureLeaveCredits(now()->year);

        $employee->load([
            'pdsPersonal', 'pdsFamily', 'pdsChildren', 'pdsEducation', 
            'pdsEligibilities', 'pdsWorkExperiences', 'pdsVoluntaryWorks', 
            'pdsTrainings', 'pdsOthers', 'pdsQuestionnaire', 'pdsReferences', 'pdsGovId',
            'pdsSectionReviews', 'salns', 'leaveCredits' => fn ($query) => $query->where('year', now()->year)->with('leaveType'), 'ctoRequests'
        ]);

        $pdsReviewChangedFields = $this->pdsReviewChangedFields($employee);

        $currentYear = (int) now()->year;
        $pdsYears = $snapshots->yearsFor($employee);
        $pdsYear = $request->integer('pds_year', $currentYear);
        $pdsYear = $pdsYears->containsStrict($pdsYear) ? $pdsYear : $currentYear;
        $pdsDraft = $snapshots->draftForYear($employee, $pdsYear);
        $employee->setRelation('pdsSectionReviews', $pdsDraft->pdsSectionReviews);
        $pdsReviewChangedFields = $this->pdsReviewChangedFields($pdsDraft);
        $pdsStatus = $request->query('pds_status', 'all');
        abort_unless(in_array($pdsStatus, ['all', 'pending', 'approved', 'rejected'], true), 422);
        $pdsSnapshot = $employee->pdsSnapshots()->where('year', $pdsYear)->first();
        $pdsHasRecord = !$snapshots->isArchivedForYear($employee, $pdsYear) && ($pdsSnapshot !== null
            || ($pdsYear === (int) ($employee->pds_record_year ?? now()->year) && $employee->pdsPersonal !== null));
        $pdsReviewStatus = app(\App\Services\PdsCompletionService::class)->summary($pdsDraft->pdsSectionReviews)['status'];
        $pdsDateFiled = $pdsSnapshot?->submitted_at ?? $pdsSnapshot?->created_at ?? $employee->pdsPersonal?->created_at;

        $salnYears = $employee->salns->pluck('as_of_date')->filter()
            ->map(fn ($date) => $date->year)->merge($snapshots->yearsFor($employee))->unique()->sortDesc()->values();
        if ($salnYears->isEmpty()) {
            $salnYears->push($currentYear);
        }
        $salnYear = $request->integer('saln_year', $salnYears->first());
        $salnYear = $salnYears->containsStrict($salnYear) ? $salnYear : $salnYears->first();
        $salnRecords = $employee->salns->filter(fn ($saln) => $saln->as_of_date?->year === $salnYear)
            ->sortByDesc('as_of_date');
        $salnStatus = $request->query('saln_status', 'all');
        abort_unless(in_array($salnStatus, ['all', 'pending', 'approved', 'returned'], true), 422);
        if ($salnStatus !== 'all') $salnRecords = $salnRecords->filter(fn ($saln) => ($saln->review_status ?? 'pending') === $salnStatus);
        $salnBackUrl = route('employees.show', ['employee' => $employee, 'tab' => 'saln', 'saln_year' => $salnYear, 'pds_year' => $pdsYear, 'saln_status' => $salnStatus]);

        return view('employees.show', compact(
            'employee', 'pdsReviewChangedFields', 'pdsYears', 'pdsYear', 'salnYears', 'salnYear', 'salnRecords', 'salnBackUrl', 'salnStatus',
            'pdsStatus', 'pdsHasRecord', 'pdsReviewStatus', 'pdsDateFiled'
        ));
    }

    public function storeCtoCredit(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validateWithBag('ctoCredit', [
            'hours' => ['required', 'numeric', 'decimal:0,2', 'min:2', 'max:999999.99'],
            'overtime_date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        DB::transaction(function () use ($employee, $validated, $request) {
            $current = Employee::query()->lockForUpdate()->findOrFail($employee->id);
            $calculation = app(\App\Services\CocEntitlementService::class)->calculate($current, $validated['overtime_date'], (float) $validated['hours']);
            $hours = $calculation['hours'];
            $balance = round((float) $current->cto_balance + $hours, 2);

            if ($balance > 999999.99) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'hours' => 'The new balance exceeds the supported limit.',
                ])->errorBag('ctoCredit');
            }

            $creditRequest = \App\Models\CtoRequest::create([
                'employee_id' => $current->id, 'type' => 'earn',
                'date_start' => $validated['overtime_date'], 'date_end' => $validated['overtime_date'],
                'hours' => $hours, 'purpose' => '',
                'rendered_hours' => $calculation['rendered_hours'], 'coc_rate' => $calculation['coc_rate'],
                'overtime_classification' => $calculation['overtime_classification'],
                'status' => 'approved', 'hrstaff_status' => 'approved',
                'chief_status' => 'not_applicable', 'rd_status' => 'not_applicable',
                'approved_by_hrstaff' => $request->user()->employee?->id,
                'credit_source' => 'hr_direct', 'recorded_by' => $request->user()->id,
                'cto_balance_before' => $current->cto_balance,
            ]);
            app(\App\Services\CocGrantService::class)->post($creditRequest, $current);
            $creditRequest->cto_balance_after = $balance;
            $creditRequest->save();
            $current->forceFill(['cto_balance' => $balance])->save();
            $filer = $request->user()->name ?: 'HR';
            $current->user?->notify(new \App\Notifications\CtoRequestNotification(
                $creditRequest, 'COC Credits Added by HR',
                "{$filer} added {$hours} COC hours to your balance. Your new balance is {$balance} hours."
            ));
            AuditLogListener::logActivity(
                'Add CTO Credits',
                'CTO',
                "HR added {$hours} COC hours directly to employee #{$current->id}. Balance after: {$balance}.",
                $request->user()
            );
        });

        return redirect()->route('employees.show', ['employee' => $employee, 'tab' => 'cto-credits'])
            ->with('cto_credit_success', 'COC credits added. The employee has been notified and the ledger updated.');
    }

    public function storeLeaveCreditAdjustment(Request $request, Employee $employee): RedirectResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'leave_credit_id' => ['required', 'integer'],
            'operation' => ['required', 'in:add,deduct'],
            'amount' => ['required', 'numeric', 'min:0.001', 'max:365'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validator->fails()) {
            return redirect()
                ->route('employees.show', $employee)
                ->withFragment('leave-credits')
                ->withErrors($validator)
                ->withInput();
        }

        $validated = $validator->validated();

        $leaveCredit = DB::transaction(function () use ($employee, $validated) {
            Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
            $credit = $employee->leaveCredits()->whereKey($validated['leave_credit_id'])->lockForUpdate()->firstOrFail();
            $amount = round((float) $validated['amount'], 3);
            if ($validated['operation'] === 'deduct' && $amount > (float) $credit->balance) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'amount' => (float) $credit->balance <= 0
                        ? 'No available credits. The balance is 0; a deduction cannot be made.'
                        : 'The deduction exceeds the available balance of '.number_format((float) $credit->balance, 3).' days.',
                ]);
            }
            $delta = $validated['operation'] === 'deduct' ? -$amount : $amount;
            $previousAdjusted = (float) $credit->adjusted;
            $credit->adjusted = round($previousAdjusted + $delta, 3);
            $credit->balance = round((float) $credit->balance + $delta, 3);
            $credit->save();
            LeaveCreditTransaction::create([
                'employee_id' => $employee->id, 'leave_type_id' => $credit->leave_type_id,
                'type' => 'adjustment', 'credit_year' => $credit->year, 'amount' => $delta,
                'balance_after' => $credit->balance, 'effective_date' => now()->toDateString(),
                'description' => ($validated['remarks'] ?? null) ?: 'HR leave credit adjustment',
                'metadata' => ['operation' => $validated['operation'], 'previous_adjusted' => $previousAdjusted,
                    'new_adjusted' => $credit->adjusted, 'year' => $credit->year, 'updated_by' => auth()->id()],
            ]);
            DB::afterCommit(function () use ($employee, $validated, $amount, $credit) {
                $employee->user?->notify(new \App\Notifications\LeaveCreditAdjustedNotification(
                    $credit->leaveType?->name ?? 'Leave', $validated['operation'], $amount,
                    (float) $credit->balance, (int) $credit->year, $validated['remarks'] ?? null,
                ));
            });
            return $credit;
        });
        return redirect()
            ->route('employees.show', $employee)
            ->withFragment('leave-credits')
            ->with('success', 'Leave credit adjustment saved successfully.');
    }

    public function updateCoreTime(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'core_time_start_date' => ['nullable', 'date', 'required_with:core_time_end_date'],
            'core_time_end_date' => ['nullable', 'date', 'required_with:core_time_start_date', 'after_or_equal:core_time_start_date'],
            'core_time_schedule' => ['nullable', 'array'],
            'core_time_schedule.*.am_start' => ['nullable', 'date_format:H:i', 'required_with:core_time_schedule.*.am_end'],
            'core_time_schedule.*.am_end' => ['nullable', 'date_format:H:i', 'required_with:core_time_schedule.*.am_start', 'after:core_time_schedule.*.am_start'],
            'core_time_schedule.*.pm_start' => ['nullable', 'date_format:H:i', 'required_with:core_time_schedule.*.pm_end'],
            'core_time_schedule.*.pm_end' => ['nullable', 'date_format:H:i', 'required_with:core_time_schedule.*.pm_start', 'after:core_time_schedule.*.pm_start'],
        ]);

        $schedule = collect($validated['core_time_schedule'] ?? [])
            ->map(fn (array $day) => collect($day)
                ->map(fn ($time) => filled($time) ? $time : null)
                ->filter()
                ->all())
            ->filter()
            ->all();

        app(\App\Services\CoreTimeHistoryService::class)->assign($employee, [
            'core_time_start_date' => $validated['core_time_start_date'] ?? null,
            'core_time_end_date' => $validated['core_time_end_date'] ?? null,
            'core_time_schedule' => $schedule !== [] ? $schedule : null,
        ]);

        AuditLogListener::logActivity(
            'Update Employee Core Time',
            'Employees',
            'Updated approved core time for '.trim(($employee->firstname ?? '').' '.($employee->lastname ?? '')),
            auth()->user()
        );

        return redirect()
            ->route('employees.show', ['employee' => $employee, 'tab' => 'core-time'])
            ->with('success', 'Approved core time updated successfully.');
    }

    private function pdsReviewChangedFields(Employee $employee): array
    {
        $reviews = $employee->pdsSectionReviews->keyBy('section_name');

        return [
            'Personal Information' => $this->changedReviewPayload($reviews->get('Personal Information')),
            'Family Background' => $this->changedReviewPayload($reviews->get('Family Background')),
            'Educational Background' => $this->changedReviewPayload($reviews->get('Educational Background')),
            'Civil Service Eligibility' => $this->changedReviewPayload($reviews->get('Civil Service Eligibility')),
            'Work Experience' => $this->changedReviewPayload($reviews->get('Work Experience')),
            'Voluntary Work' => $this->changedReviewPayload($reviews->get('Voluntary Work')),
            'Training' => $this->changedReviewPayload($reviews->get('Training')),
            'Other Information' => $this->changedReviewPayload($reviews->get('Other Information')),
            'Questionnaire' => $this->changedReviewPayload($reviews->get('Questionnaire')),
            'References' => $this->changedReviewPayload($reviews->get('References')),
        ];
    }

    private function changedReviewPayload($review): array
    {
        $changedFields = collect($review?->changed_fields ?? []);

        if ($changedFields->isEmpty()) {
            return ['fields' => [], 'entries' => []];
        }

        $entries = $changedFields
            ->map(function ($field) {
                $field = str_contains($field, '.') && ! is_numeric(strtok($field, '.'))
                    ? substr($field, strpos($field, '.') + 1)
                    : $field;

                return preg_match('/^(\d+)\./', $field, $matches) ? (int) $matches[1] : null;
            })
            ->filter(fn ($index) => $index !== null)
            ->unique()
            ->values()
            ->all();

        $fields = $changedFields
            ->filter(fn ($field) => ! preg_match('/(^|\.)(\d+)\./', $field))
            ->map(fn ($field) => str_contains($field, '.') ? substr($field, strrpos($field, '.') + 1) : $field)
            ->unique()
            ->values()
            ->all();

        return ['fields' => $fields, 'entries' => $entries];
    }

    /**
     * Display the current logged in user's employee record.
     */
    public function myRecord(): View|\Illuminate\Http\RedirectResponse
    {
        $employee = auth()->user()->employee;
        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'You do not have an associated employee record.');
        }

        $employee->load([
            'pdsPersonal'
        ]);

        return view('employees.personal-information', compact('employee'));
    }

    /**
     * Show the form for editing the current logged in user's personal information.
     */
    public function editMyRecord(): View|\Illuminate\Http\RedirectResponse
    {
        $employee = auth()->user()->employee;
        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'You do not have an associated employee record.');
        }

        return view('employees.personal-information-edit', compact('employee'));
    }

    /**
     * Update the current logged in user's personal information.
     */
    public function updateMyRecord(Request $request): \Illuminate\Http\RedirectResponse
    {
        $employee = auth()->user()->employee;
        if (!$employee) {
            return redirect()->route('dashboard')->with('error', 'You do not have an associated employee record.');
        }

        $validated = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|digits:11',
        ]);

        // Update employee name and contact number
        $employee->update([
            'firstname' => $validated['firstname'],
            'lastname' => $validated['lastname'],
            'middlename' => $validated['middlename'],
            'contact_number' => $validated['contact_number']
        ]);

        // Update user name (combining first and last)
        $user = auth()->user();
        $user->update([
            'name' => trim($validated['firstname'] . ' ' . $validated['lastname']),
        ]);

        // If they have a PDS personal info record, sync the contact there too
        if ($employee->pdsPersonal) {
            $employee->pdsPersonal->update([
                'firstname' => $validated['firstname'],
                'surname' => $validated['lastname'],
                'middlename' => $validated['middlename'],
                'mobile_no' => $validated['contact_number'],
            ]);
        }

        return redirect()->route('personal-information.show')->with('success', 'Personal information updated successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee): View
    {
        $users = User::whereDoesntHave('employee', function ($query) use ($employee) {
            $query->where('id', '!=', $employee->id);
        })->get();
        $roles = ['employee', 'hrstaff', 'recordofficer', 'chief', 'regionaldirector', 'admin'];
        $divisionOptions = $this->divisionOptions();
        $employmentStatuses = ['Regular', 'Job Order', 'Contract of Service'];

        return view('employees.edit', compact('employee', 'users', 'roles', 'divisionOptions', 'employmentStatuses'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $request->merge([
            'notification_email' => Str::lower(trim((string) $request->input('notification_email'))),
        ]);

        $validated = $request->validate([
            'lastname' => 'required|string|max:255',
            'firstname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|digits:11',
            'notification_email' => ['required', 'email', 'max:255', Rule::unique('employees', 'notification_email')->ignore($employee->id)],
            'division' => ['required', 'string', Rule::in($this->divisionOptions())],
            'position' => 'required|string|max:255',
            'account_role' => 'required|string|in:employee,hrstaff,recordofficer,chief,regionaldirector,admin',
            'employment_status' => 'nullable|string|in:Regular,Job Order,Contract of Service',
            'service_start_date' => 'nullable|date',
            'user_id' => 'nullable|exists:users,id|unique:employees,user_id,' . $employee->id,
            'rfid_number' => 'nullable|string|max:255|unique:employees,rfid_number,' . $employee->id,
        ], [
            'notification_email.required' => 'The Recovery / OTP Email field is required.',
            'notification_email.unique' => 'This Recovery / OTP Email is already assigned to another employee.',
            'rfid_number.unique' => 'This RFID Number is already assigned to another employee.',
        ]);

        $salary = app(\App\Services\EmployeeSalaryService::class)->values($request);
        DB::transaction(function () use ($employee, $validated, $salary) {
            $employee->update([...$validated, ...$salary]);
            app(\App\Services\EmployeeSalaryService::class)->syncPds($employee, array_keys($employee->getChanges()));
        });

        AuditLogListener::logActivity(
            'Update Employee',
            'Employees',
            'Updated employee record for ' . trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? '')),
            auth()->user()
        );

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee): RedirectResponse
    {
        DB::transaction(function () use ($employee) {
            if ($employee->user) {
                $employee->user->update(['account_status' => 'disabled']);
            }

            $employee->delete();
        });

        AuditLogListener::logActivity(
            'Delete Employee',
            'Employees',
            'Archived employee record for ' . trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? '')),
            auth()->user()
        );

        return redirect()->route('employees.index')->with('success', 'Employee archived and login access disabled successfully.');
    }

    public function restore(int $employee): RedirectResponse
    {
        $employee = Employee::onlyTrashed()->with('user')->findOrFail($employee);

        DB::transaction(function () use ($employee) {
            $employee->restore();

            if ($employee->user) {
                $employee->user->update([
                    'account_status' => 'active',
                    'is_approved' => true,
                ]);
            }
        });

        return redirect()->route('employees.index')->with('success', 'Employee restored and login access re-enabled successfully.');
    }

    public function forceDelete(int $employee): RedirectResponse
    {
        $employee = Employee::onlyTrashed()->with('user')->findOrFail($employee);

        DB::transaction(function () use ($employee) {
            $user = $employee->user;

            $this->deleteProfilePicture($employee->profile_picture);
            $employee->forceDelete();

            if ($user) {
                $user->delete();
            }
        });

        return redirect()->route('employees.index')->with('success', 'Employee record and linked account permanently deleted.');
    }

    /**
     * Upload profile picture.
     */
    public function uploadProfilePicture(Request $request, Employee $employee): RedirectResponse
    {
        // Profile photos are self-service only; HR/admin roles can view records but cannot replace another employee's image.
        if (auth()->user()->employee?->id !== $employee->id) {
            abort(403);
        }

        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('profile_picture')) {
            $this->deleteProfilePicture($employee->profile_picture);

            $path = $request->file('profile_picture')->store('profile_pictures', 'public_uploads');
            $employee->update(['profile_picture' => $path]);
        }

        return back()->with('success', 'Profile picture updated successfully.');
    }

    /**
     * Upload reusable employee e-signature for printable HRIS documents.
     */
    public function uploadESignature(Request $request, Employee $employee): RedirectResponse
    {
        if (!in_array(auth()->user()->role, ['admin', 'hrstaff']) && auth()->user()->employee?->id !== $employee->id) {
            abort(403);
        }

        $request->validate([
            'e_signature' => 'required|image|mimes:png|max:2048',
        ]);

        if ($request->hasFile('e_signature')) {
            $existingPdsSignature = $employee->pdsGovId?->signature_path;
            if ($existingPdsSignature && $existingPdsSignature !== $employee->e_signature_path) {
                $this->deleteESignature($existingPdsSignature);
            }
            $this->deleteESignature($employee->e_signature_path);

            $path = $request->file('e_signature')->store('employee-signatures', 'signatures');
            $employee->update(['e_signature_path' => $path]);

            $employee->pdsGovId()->updateOrCreate(
                ['employee_id' => $employee->id],
                ['signature_path' => $path]
            );
        }

        return back()->with('success', 'Reusable e-signature updated successfully.');
    }

    public function destroyESignature(Employee $employee): RedirectResponse
    {
        if (!in_array(auth()->user()->role, ['admin', 'hrstaff']) && auth()->user()->employee?->id !== $employee->id) {
            abort(403);
        }

        $signaturePath = $employee->e_signature_path;

        if ($signaturePath) {
            $this->deleteESignature($signaturePath);
            $employee->update(['e_signature_path' => null]);

            if ($employee->pdsGovId?->signature_path === $signaturePath) {
                $employee->pdsGovId()->update(['signature_path' => null]);
            }
        }

        return back()->with('success', 'Reusable e-signature removed successfully.');
    }

    private function deleteProfilePicture(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (Storage::disk('public_uploads')->exists($path)) {
            Storage::disk('public_uploads')->delete($path);
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function deleteESignature(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (Storage::disk('signatures')->exists($path)) {
            Storage::disk('signatures')->delete($path);
        }
    }

    private function perPage(Request $request, string $key): int
    {
        $perPage = (int) $request->query($key, 5);

        return in_array($perPage, [5, 10, 20, 50], true) ? $perPage : 5;
    }
}

