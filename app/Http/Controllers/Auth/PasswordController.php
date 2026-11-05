<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    private function sendPasswordOtp(Request $request): void
    {
        $user = $request->user()->load('employee');
        $recoveryEmail = $user->employee?->notification_email;

        if (! $recoveryEmail) {
            throw ValidationException::withMessages([
                'otp' => 'No Recovery / OTP Email is registered for this account. Please contact HR.',
            ])->errorBag('updatePassword');
        }

        $otp = (string) random_int(100000, 999999);

        $user->forceFill([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ])->save();

        Mail::raw(
            "Your DMW-HRIS password update OTP is {$otp}.\n\nThis code expires in 10 minutes. If you did not request a password change, please contact HR immediately.",
            function ($message) use ($recoveryEmail) {
                $message
                    ->to($recoveryEmail)
                    ->subject('DMW-HRIS Password Update OTP');
            }
        );
    }

    public function sendOtp(Request $request): RedirectResponse|JsonResponse
    {
        $this->sendPasswordOtp($request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'A password update OTP has been sent to your Recovery / OTP Email.',
            ]);
        }

        return back()->with('status', 'password-otp-sent');
    }

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        $currentPasswordRules = $user->must_change_password
            ? ['nullable']
            : ['required', 'current_password'];

        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => $currentPasswordRules,
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $this->sendPasswordOtp($request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'A password update OTP has been sent to your Recovery / OTP Email.',
            ]);
        }

        return back()->with('status', 'password-otp-sent');
    }

    public function confirm(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        $validated = $request->validateWithBag('updatePassword', [
            'otp' => ['required', 'digits:6'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        if (! $user->otp || ! hash_equals((string) $user->otp, (string) $validated['otp'])) {
            throw ValidationException::withMessages([
                'otp' => 'The OTP is invalid.',
            ])->errorBag('updatePassword');
        }

        if ($user->isOtpExpired()) {
            throw ValidationException::withMessages([
                'otp' => 'The OTP has expired. Please request a new password update OTP.',
            ])->errorBag('updatePassword');
        }

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'otp' => null,
            'otp_expires_at' => null,
            'must_change_password' => false,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Password updated successfully.',
                'redirect' => route('dashboard'),
            ]);
        }

        return back()->with('status', 'password-updated');
    }
}
