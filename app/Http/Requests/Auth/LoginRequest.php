<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'lowercase', 'email', 'ends_with:@dmw.gov.ph'],
            'password' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.ends_with' => 'Invalid HRIS Username. Please use your @dmw.gov.ph HRIS login username.',
            'email.lowercase' => 'Invalid HRIS Username. Please use lowercase letters for your HRIS login username.',
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        // Block unverified users — log them out so the controller can redirect to OTP
        $user = Auth::user();

        if ($user instanceof User && $user->email_verified_at === null) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Your HRIS account is not yet verified. Please contact HR to activate your account.',
            ]);
        }

        // Block unapproved users — they cannot access the system at all
        if ($user instanceof User && ! $user->is_approved) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Your account is still pending HR approval. Please wait up to 24 hours for your account to be activated.',
            ]);
        }

        if ($user instanceof User && $user->account_status === 'disabled') {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Your account has been disabled. Please contact HR if you need access restored.',
            ]);
        }

        if ($user instanceof User && ! $user->privacy_consent && ! $this->boolean('privacy_consent')) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'Please read and agree to the Data Privacy Act notice before signing in.',
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
