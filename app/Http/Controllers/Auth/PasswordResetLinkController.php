<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::query()
            ->with('employee')
            ->where('email', strtolower($request->email))
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'We could not find an account with that HRIS Username.',
            ]);
        }

        $recoveryEmail = $user->employee?->notification_email;

        if (! $recoveryEmail) {
            throw ValidationException::withMessages([
                'email' => 'No Recovery / OTP Email is registered for this account. Please contact HR.',
            ]);
        }

        if (!\Illuminate\Support\Facades\Cache::add('password-reset-otp-cooldown:'.$user->id, true, 30)) {
            throw ValidationException::withMessages(['email'=>'Please wait 30 seconds before requesting another OTP.']);
        }

        $otp = (string) random_int(100000, 999999);


        try {
        Mail::raw(
            "Your DMW-HRIS password reset OTP is {$otp}.\n\nThis code expires in 10 minutes. If you did not request a password reset, please ignore this message or contact HR.",
            function ($message) use ($recoveryEmail) {
                $message
                    ->to($recoveryEmail)
                    ->subject('DMW-HRIS Password Reset OTP');
            }
        );

        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::warning('Password reset OTP delivery failed.', ['user_id'=>$user->id, 'exception'=>$exception::class]);
            throw ValidationException::withMessages(['email'=>'The OTP email could not be sent. Please try again or contact HR.']);
        }
        $user->forceFill([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(10),
        ])->save();

        return redirect()
            ->route('password.otp.reset', ['email' => $user->email])
            ->with('status', 'A password reset OTP has been sent to your Recovery / OTP Email.');
    }
}
