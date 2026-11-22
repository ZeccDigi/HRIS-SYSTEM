<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
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

        $key = 'password-update-otp-cooldown:'.$user->id;
        if (!Cache::add($key, now()->timestamp + 30, 30)) {
            $wait = max(1, (int) Cache::get($key) - now()->timestamp);
            $this->otpFailure($request, "Please wait {$wait} seconds before requesting another OTP.", $wait, 429);
        }
        $otp = (string) random_int(100000, 999999);
        try {
        Mail::raw(
            "Your DMW-HRIS password update OTP is {$otp}.\n\nThis code expires in 10 minutes. If you did not request a password change, please contact HR immediately.",
            function ($message) use ($recoveryEmail) {
                $message
                    ->to($recoveryEmail)
                    ->subject('DMW-HRIS Password Update OTP');
            }
        );
        } catch (\Throwable $exception) {
            Log::warning('Password update OTP email delivery failed.', ['user_id' => $user->id, 'exception' => $exception::class]);
            $this->otpFailure($request, 'The OTP email could not be sent. Check the internet connection and mail settings, then try again.', 30, 503);
        }
        $user->forceFill(['otp' => $otp, 'otp_expires_at' => now()->addMinutes(10)])->save();
    }

    private function otpFailure(Request $request, string $message, int $wait, int $status): never
    {
        if ($request->expectsJson()) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json([
                'message' => $message, 'errors' => ['otp' => [$message]], 'retry_after' => $wait,
            ], $status)->header('Retry-After', (string) $wait));
        }
        throw ValidationException::withMessages(['otp' => $message])->errorBag('updatePassword');
    }

    public function sendOtp(Request $request): RedirectResponse|JsonResponse
    {
        $this->sendPasswordOtp($request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'A password update OTP has been sent to your Recovery / OTP Email.',
                'retry_after' => 30,
            ]);
        }

        return back()->with('status', 'password-otp-sent');
    }

    /**
     * Update the user's password.
     */
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $this->sendPasswordOtp($request);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'A password update OTP has been sent to your Recovery / OTP Email.',
                'retry_after' => 30,
            ]);
        }

        return back()->with('status', 'password-otp-sent');
    }

    public function confirm(Request $request): RedirectResponse|JsonResponse
    {
        $attemptKey = 'otp-update-verify:'.hash('sha256', (string)$request->user()->id);
        if (\Illuminate\Support\Facades\RateLimiter::tooManyAttempts($attemptKey, 5)) {
            throw new \Illuminate\Http\Exceptions\HttpResponseException(response()->json(['message'=>'Too many OTP attempts. Please wait before trying again.'], 429)->header('Retry-After', (string)\Illuminate\Support\Facades\RateLimiter::availableIn($attemptKey)));
        }
        \Illuminate\Support\Facades\RateLimiter::hit($attemptKey, 600);

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

        \Illuminate\Support\Facades\RateLimiter::clear($attemptKey);
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Password updated successfully.',
                'redirect' => route('dashboard'),
            ]);
        }

        return back()->with('status', 'password-updated');
    }
}
