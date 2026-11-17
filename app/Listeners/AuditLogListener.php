<?php

namespace App\Listeners;

use App\Models\ActivityAudit;
use App\Models\LoginAudit;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Request;

class AuditLogListener
{
    /**
     * Handle user login events.
     */
    public function handleLogin(Login $event): void
    {
        $user = $event->user;
        $userAgent = Request::header('User-Agent') ?? '';
        $ip = Request::ip();

        $agentDetails = $this->parseUserAgent($userAgent);

        $userName = $user->employee
            ? trim($user->employee->firstname . ' ' . $user->employee->lastname)
            : $user->name;

        LoginAudit::create([
            'user_id' => $user->id,
            'user_name' => $userName ?: $user->email,
            'user_email' => $user->email,
            'user_role' => ucfirst($user->role ?? 'Employee'),
            'status' => 'successful',
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device' => $agentDetails['device'],
            'browser' => $agentDetails['browser'],
            'os' => $agentDetails['os'],
            'login_method' => 'Password',
            'failure_reason' => null,
            'login_at' => now(),
            'session_id' => session()->getId(),
        ]);

    }

    /**
     * Handle failed login attempt events.
     */
    public function handleFailed(Failed $event): void
    {
        $credentials = $event->credentials;
        $email = $credentials['email'] ?? $credentials['username'] ?? 'Unknown';
        $user = $event->user;
        $userAgent = Request::header('User-Agent') ?? '';
        $ip = Request::ip();

        $agentDetails = $this->parseUserAgent($userAgent);

        $userName = null;
        $userRole = null;

        if ($user) {
            $userName = $user->employee
                ? trim($user->employee->firstname . ' ' . $user->employee->lastname)
                : $user->name;
            $userRole = ucfirst($user->role ?? 'Employee');
        }

        LoginAudit::create([
            'user_id' => $user?->id,
            'user_name' => $userName ?: 'Unknown User',
            'user_email' => $email,
            'user_role' => $userRole ?: 'N/A',
            'status' => 'failed',
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device' => $agentDetails['device'],
            'browser' => $agentDetails['browser'],
            'os' => $agentDetails['os'],
            'login_method' => 'Password',
            'failure_reason' => 'Invalid password or credentials',
            'login_at' => now(),
        ]);

    }

    /**
     * Handle user logout events.
     */
    public function handleLogout(Logout $event): void
    {
        if (! $event->user) {
            return;
        }

        $audit = LoginAudit::where('user_id', $event->user->id)
            ->where('status', 'successful')
            ->whereNull('logout_at')
            ->latest('login_at')
            ->first();

        if ($audit) {
            $audit->update(['logout_at' => now()]);
        }

    }

    /**
     * Reusable helper to log activity audit events across the application.
     */
    public static function logActivity(string $action, string $module, string $description, ?\App\Models\User $user = null): void
    {
        $user = $user ?? auth()->user();

        $userName = $user?->employee
            ? trim($user->employee->firstname . ' ' . $user->employee->lastname)
            : $user?->name;

        ActivityAudit::create([
            'user_id' => $user?->id,
            'user_name' => $userName ?: 'System',
            'user_role' => ucfirst($user?->role ?? 'Employee'),
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'ip_address' => Request::ip() ?? '127.0.0.1',
        ]);
    }

    /**
     * Parse User Agent string to extract Device, Browser, and OS details.
     */
    protected function parseUserAgent(string $ua): array
    {
        $browser = 'Unknown Browser';
        $os = 'Unknown OS';
        $device = 'Desktop PC';

        if (preg_match('/windows nt 10/i', $ua)) {
            $os = 'Windows 10 / 11';
            $device = 'Windows PC';
        } elseif (preg_match('/windows nt 6\.3/i', $ua)) {
            $os = 'Windows 8.1';
            $device = 'Windows PC';
        } elseif (preg_match('/windows nt 6\.1/i', $ua)) {
            $os = 'Windows 7';
            $device = 'Windows PC';
        } elseif (preg_match('/mac os x/i', $ua)) {
            $os = 'macOS';
            $device = 'Macintosh';
        } elseif (preg_match('/iphone/i', $ua)) {
            $os = 'iOS';
            $device = 'iPhone';
        } elseif (preg_match('/ipad/i', $ua)) {
            $os = 'iPadOS';
            $device = 'iPad';
        } elseif (preg_match('/android/i', $ua)) {
            $os = 'Android';
            $device = 'Mobile Device';
        } elseif (preg_match('/linux/i', $ua)) {
            $os = 'Linux';
            $device = 'Linux Workstation';
        }

        if (preg_match('/edg/i', $ua)) {
            $browser = 'Microsoft Edge';
        } elseif (preg_match('/chrome/i', $ua)) {
            $browser = 'Google Chrome';
        } elseif (preg_match('/firefox/i', $ua)) {
            $browser = 'Mozilla Firefox';
        } elseif (preg_match('/safari/i', $ua)) {
            $browser = 'Apple Safari';
        } elseif (preg_match('/opera|opr/i', $ua)) {
            $browser = 'Opera';
        }

        return [
            'device' => $device,
            'browser' => $browser,
            'os' => $os,
        ];
    }
}
