<?php

namespace App\Http\Controllers;

use App\Mail\AccountApprovedMail;
use App\Models\UtilityOption;
use App\Models\User;
use App\Support\UtilityOptionRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class EmployeeAccountController extends Controller
{
    private function divisionOptions(): array
    {
        UtilityOptionRegistry::ensureDefaults();

        return UtilityOption::listFor('divisions')
            ->pluck('value')
            ->values()
            ->all();
    }

    /**
     * Display a list of employee login accounts pending HR approval.
     */
    public function index(): View
    {
        $pendingAccounts = User::query()
            ->with('employee')
            ->whereNotNull('email_verified_at')
            ->where('is_approved', false)
            ->latest()
            ->get();

        $approvedAccounts = User::query()
            ->with('employee')
            ->whereNotNull('email_verified_at')
            ->where('is_approved', true)
            ->latest()
            ->get();

        return view('employees.accounts', compact('pendingAccounts', 'approvedAccounts'));
    }

    /**
     * Display the specified employee account.
     */
    public function show(User $user): View
    {
        $user->load('employee');
        $divisionOptions = $this->divisionOptions();

        return view('employees.accounts-show', compact('user', 'divisionOptions'));
    }

    /**
     * Update the specified employee account details.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'lastname' => 'required|string|max:255',
            'firstname' => 'required|string|max:255',
            'middlename' => 'nullable|string|max:255',
            'suffix' => 'nullable|string|max:255',
            'division' => ['nullable', 'string', Rule::in($this->divisionOptions())],
            'position' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
        ]);

        if ($user->employee) {
            $user->employee->update($validated);
        }

        return redirect()->route('employee-accounts.show', $user)->with('success', 'Employee data updated successfully.');
    }

    /**
     * Approve an employee account.
     */
    public function approve(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'account_role' => ['required', 'string', 'in:employee,hrstaff,recordofficer,chief,regionaldirector,admin'],
        ]);

        if (! $user->employee) {
            return back()->with('error', 'Account could not be approved because the employee record is missing.');
        }

        $user->employee->update([
            'account_role' => $validated['account_role'],
        ]);

        $user->update(['is_approved' => true]);

        $recoveryEmail = $user->employee->notification_email;

        if ($recoveryEmail) {
            Mail::to($recoveryEmail)->send(new AccountApprovedMail($user->name));
        }

        return back()->with('success', 'Account for '.$user->display_name.' has been approved successfully.');
    }

    /**
     * Reject and delete an unapproved employee account.
     */
    public function reject(User $user): RedirectResponse
    {
        $displayName = $user->display_name;

        // Delete the associated employee record first
        if ($user->employee) {
            $user->employee->delete();
        }

        $user->delete();

        return back()->with('success', 'Account for '.$displayName.' has been rejected and removed.');
    }
}

