<?php

namespace App\Http\Controllers;

use App\Models\ActivityAudit;
use App\Models\LoginAudit;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditLogController extends Controller
{
    /**
     * Display Login & Activity Audit page with fully functional filters.
     */
    public function login(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $role = strtolower(trim((string) $request->input('role')));
        $status = strtolower((string) $request->input('status'));
        $module = trim((string) $request->input('module'));
        $selectedDate = $request->input('date');
        $selectedYear = $request->has('year') && $request->input('year') !== '' ? $request->input('year') : date('Y');


        // -----------------------
        // 1. LOGIN AUDIT QUERY
        // -----------------------
        $loginQuery = LoginAudit::with('user.employee')->latest('login_at');

        if ($search) {
            $loginQuery->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                  ->orWhere('user_email', 'like', "%{$search}%")
                  ->orWhere('user_role', 'like', "%{$search}%")
                  ->orWhere('ip_address', 'like', "%{$search}%");
            });
        }

        if ($role) {
            $loginQuery->where('user_role', $role);
        }

        if (in_array($status, ['successful', 'failed'], true)) {
            $loginQuery->where('status', $status);
        }

        if ($selectedDate) {
            $loginQuery->whereDate('login_at', $selectedDate);
        }

        if ($selectedYear) {
            $loginQuery->whereYear('login_at', $selectedYear);
        }

        $audits = $loginQuery->paginate(15, ['*'], 'login_page')->withQueryString();

        // -----------------------
        // 2. ACTIVITY AUDIT QUERY
        // -----------------------
        $activityQuery = ActivityAudit::with('user.employee')->latest();

        if ($search) {
            $activityQuery->where(function ($q) use ($search) {
                $q->where('user_name', 'like', "%{$search}%")
                  ->orWhere('action', 'like', "%{$search}%")
                  ->orWhere('module', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($role) {
            $activityQuery->where('user_role', $role);
        }

        if ($module) {
            $activityQuery->where('module', $module);
        }

        if ($selectedDate) {
            $activityQuery->whereDate('created_at', $selectedDate);
        }

        if ($selectedYear) {
            $activityQuery->whereYear('created_at', $selectedYear);
        }

        $activityAudits = $activityQuery->paginate(15, ['*'], 'activity_page')->withQueryString();

        // Dynamically fetch unique modules from DB, fallback to defaults if none exist yet
        $dbModules = ActivityAudit::whereNotNull('module')->distinct()->pluck('module');
        $defaultModules = collect(['Employees', 'Leaves', 'Attendance', 'Audit Logs', 'System', 'Auth']);
        $modules = $dbModules->isNotEmpty() ? $dbModules->merge($defaultModules)->unique()->values() : $defaultModules;

        $roleOptions = [
            'employee' => 'Employee',
            'hrstaff' => 'HR Admin',
            'recordofficer' => 'Record Officer',
            'chief' => 'Chief',
            'regionaldirector' => 'Regional Director',
            'admin' => 'Admin',
        ];

        return view('audit-logs.login', compact(
            'audits', 
            'activityAudits', 
            'modules', 
            'roleOptions', 
            'role', 
            'search', 
            'status', 
            'module', 
            'selectedDate', 
            'selectedYear'
        ));
    }

    /**
     * Display Activity Audit page.
     */
    public function activity(Request $request): View
    {
        return $this->login($request);
    }
}
