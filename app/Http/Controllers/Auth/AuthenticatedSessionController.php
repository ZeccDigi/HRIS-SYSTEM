<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        if ($request->user() && ! $request->user()->privacy_consent) {
            $request->user()->forceFill([
                'privacy_consent' => true,
                'privacy_consented_at' => now(),
            ])->save();
        }

        $request->session()->regenerate();

        if ($request->user()?->must_change_password) {
            return redirect()->route('profile.edit')
                ->with('status', 'Please change your temporary password before continuing.');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    public function privacyConsentStatus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'lowercase', 'email'],
        ]);

        $user = User::query()
            ->where('email', $validated['email'])
            ->first();

        return response()->json([
            'requires_consent' => $user ? ! (bool) $user->privacy_consent : false,
        ]);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
