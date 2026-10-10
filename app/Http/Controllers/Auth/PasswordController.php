<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class PasswordController extends Controller
{
    public function sendOtp(Request $request): RedirectResponse
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

        return back()->with('status', 'password-otp-sent');
    }

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'otp' => ['required', 'digits:6'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user = $request->user();

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

        return back()->with('status', 'password-updated');
    }
}
