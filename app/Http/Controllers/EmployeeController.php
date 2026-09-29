<?php

namespace App\Http\Controllers;

use App\Listeners\AuditLogListener;
use App\Mail\HrisAccountCreatedMail;
use App\Models\Employee;
use App\Models\UtilityOption;
use App\Models\User;
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
        $sort = $request->query('sort', 'name_asc');

        $employees = $this->employeeIndexQuery($search, $division, $sort, $divisionOptions)->get();
        $archivedEmployees = Employee::onlyTrashed()->with('user')->latest('deleted_at')->get();
        $pendingAccounts = User::query()
            ->with('employee')
            ->whereNotNull('email_verified_at')
            ->where('is_approved', false)
            ->where('account_status', '!=', 'disabled')
            ->latest()
            ->get();

        $approvedAccounts = User::query()
            ->with('employee')
            ->whereNotNull('email_verified_at')
            ->where('is_approved', true)
            ->where('account_status', 'active')
            ->latest()
            ->get();

        return view('employees.index', compact('employees', 'archivedEmployees', 'pendingAccounts', 'approvedAccounts', 'divisionOptions', 'search', 'division', 'sort'));
    }

    public function filter(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $division = (string) $request->query('division', '');
        $sort = $request->query('sort', 'name_asc');
        $employees = $this->employeeIndexQuery($search, $division, $sort, $this->divisionOptions())->get();

        return response()->json([
            'html' => view('employees.partials.rows', compact('employees'))->render(),
            'count' => $employees->count(),
        ]);
    }

    private function employeeIndexQuery(string $search, string $division, string $sort, array $divisionOptions)
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
            ->when(in_array($division, $divisionOptions, true), fn ($query) => $query->where('division', $division));

        return match ($sort) {
            'name_desc' => $query->orderByDesc('lastname')->orderByDesc('firstname'),
            'division_asc' => $query->orderBy('division')->orderBy('lastname')->orderBy('firstname'),
            'newest' => $query->latest(),
            default => $query->orderBy('lastname')->orderBy('firstname'),
        };
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
        ]);

        $validated = $request->validate([
            'lastname' => 'required|string|max:255',
            'firstname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'notification_email' => 'required|email|max:255',
            'division' => ['required', 'string', Rule::in($this->divisionOptions())],
            'position' => 'required|string|max:255',
            'account_role' => 'required|string|in:employee,hrstaff,recordofficer,chief,regionaldirector,admin',
            'employment_status' => 'nullable|string|in:Regular,Job Order,Contract of Service',
            'service_start_date' => 'nullable|date',
            'hris_username' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9._-]+$/'],
            'rfid_number' => 'nullable|string|max:255|unique:employees,rfid_number',
        ], [
            'notification_email.required' => 'The Recovery / OTP Email field is required.',
            'hris_username.required' => 'The HRIS Username field is required.',
            'hris_username.regex' => 'The HRIS Username may only contain lowercase letters, numbers, dots, underscores, and hyphens.',
        ]);

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
            $temporaryPassword = Str::password(12, true, true, false, false);
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
            Employee::create($validated);
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
                    ->with('error', 'The login account was created, but the account details could not be sent to the Recovery / OTP Email. Please check the mail settings.');
            }
        }

        AuditLogListener::logActivity(
            'Create Employee',
            'Employees',
            'Created new employee record for ' . trim(($validated['firstname'] ?? '') . ' ' . ($validated['lastname'] ?? '')),
            auth()->user()
        );

        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee): View
    {
        $employee->ensureLeaveCredits(now()->year);

        $employee->load([
            'pdsPersonal', 'pdsFamily', 'pdsChildren', 'pdsEducation', 
            'pdsEligibilities', 'pdsWorkExperiences', 'pdsVoluntaryWorks', 
            'pdsTrainings', 'pdsOthers', 'pdsQuestionnaire', 'pdsReferences', 'pdsGovId',
            'pdsSectionReviews', 'salns', 'leaveCredits.leaveType'
        ]);

        $pdsReviewChangedFields = $this->pdsReviewChangedFields($employee);

        return view('employees.show', compact('employee', 'pdsReviewChangedFields'));
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
            'contact_number' => 'nullable|string|max:20',
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
        $validated = $request->validate([
            'lastname' => 'required|string|max:255',
            'firstname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'contact_number' => 'nullable|string|max:20',
            'notification_email' => 'required|email|max:255',
            'division' => ['required', 'string', Rule::in($this->divisionOptions())],
            'position' => 'required|string|max:255',
            'account_role' => 'required|string|in:employee,hrstaff,recordofficer,chief,regionaldirector,admin',
            'employment_status' => 'nullable|string|in:Regular,Job Order,Contract of Service',
            'service_start_date' => 'nullable|date',
            'user_id' => 'nullable|exists:users,id|unique:employees,user_id,' . $employee->id,
            'rfid_number' => 'nullable|string|max:255|unique:employees,rfid_number,' . $employee->id,
        ], [
            'notification_email.required' => 'The Recovery / OTP Email field is required.',
        ]);

        $employee->update($validated);

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
        if (!in_array(auth()->user()->role, ['admin', 'hrstaff', 'chief', 'regionaldirector']) && auth()->user()->employee?->id !== $employee->id) {
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

            $path = $request->file('e_signature')->store('employee-signatures', 'public');
            $employee->update(['e_signature_path' => $path]);

            $employee->pdsGovId()->updateOrCreate(
                ['employee_id' => $employee->id],
                ['signature_path' => $path]
            );
        }

        return back()->with('success', 'Reusable e-signature updated successfully.');
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

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}

