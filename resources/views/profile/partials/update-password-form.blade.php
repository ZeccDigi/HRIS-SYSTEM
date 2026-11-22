<section
    x-data="passwordUpdateFlow({
        updateUrl: @js(route('password.update')),
        confirmUrl: @js(route('password.confirm-otp')),
        csrf: @js(csrf_token()),
    })"
>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Update Password') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Set a new password. An OTP will be sent after you click Save.') }}
        </p>
    </header>

    <form class="mt-6 space-y-6" @submit.prevent="requestOtp">
        <div>
            <x-input-label for="update_password_password" :value="__('New Password')" />
            <div class="relative mt-1">
                <x-text-input id="update_password_password" type="password" class="block w-full pr-14" autocomplete="new-password" x-model="password" />
                <button type="button" @click="togglePassword('update_password_password', $el)" class="absolute inset-y-0 right-0 flex items-center pr-4 text-sm font-semibold text-gray-500 hover:text-[#0038a8] focus:outline-none transition-colors">{{ __('Show') }}</button>
            </div>
            <p x-show="errors.password" x-text="errors.password" class="mt-2 text-sm text-red-600"></p>
            <x-password-strength input-id="update_password_password" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
            <div class="relative mt-1">
                <x-text-input id="update_password_password_confirmation" type="password" class="block w-full pr-14" autocomplete="new-password" x-model="passwordConfirmation" />
                <button type="button" @click="togglePassword('update_password_password_confirmation', $el)" class="absolute inset-y-0 right-0 flex items-center pr-4 text-sm font-semibold text-gray-500 hover:text-[#0038a8] focus:outline-none transition-colors">{{ __('Show') }}</button>
            </div>

            <div x-show="passwordConfirmation.length > 0 && password === passwordConfirmation" style="display: none;" class="mt-2 text-sm font-bold text-green-600 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                {{ __('Passwords match!') }}
            </div>
            <div x-show="passwordConfirmation.length > 0 && password !== passwordConfirmation" style="display: none;" class="mt-2 text-sm font-bold text-red-500 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                {{ __('Passwords do not match') }}
            </div>
            <p x-show="errors.password_confirmation" x-text="errors.password_confirmation" class="mt-2 text-sm text-red-600"></p>
        </div>

        <p x-show="message" x-text="message" class="text-sm font-semibold text-green-600"></p>
        <p x-show="generalError" x-text="generalError" class="text-sm font-semibold text-red-600"></p>

        <div class="flex items-center gap-4">
            <x-primary-button type="submit" x-bind:disabled="submitting || confirming || cooldown > 0" class="disabled:cursor-not-allowed disabled:opacity-60">
                <span x-text="submitting ? @js(__('Sending OTP...')) : @js(__('Save'))">{{ __('Save') }}</span>
            </x-primary-button>
        </div>
    </form>

    <div x-show="otpOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 px-4">
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-xs font-black uppercase tracking-[0.22em] text-blue-800">{{ __('Password Verification') }}</p>
                    <h3 class="mt-2 text-xl font-black text-slate-900">{{ __('Enter OTP') }}</h3>
                    <p class="mt-1 text-sm text-slate-500">{{ __('Check your Recovery / OTP Email for the 6-digit code.') }}</p>
                </div>
                <button type="button" @click="otpOpen = false" class="rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700">
                    <span class="sr-only">{{ __('Close') }}</span>
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <form class="mt-6 space-y-5" @submit.prevent="confirmOtp">
                <div>
                    <x-input-label for="update_password_otp" :value="__('Password Update OTP')" />
                    <x-text-input
                        id="update_password_otp"
                        type="text"
                        inputmode="numeric"
                        maxlength="6"
                        pattern="[0-9]{6}"
                        class="mt-1 block w-full text-center text-lg font-black tracking-[0.5em]"
                        autocomplete="one-time-code"
                        x-model="otp"
                    />
                    <p class="mt-1 text-xs text-gray-500">{{ __('The code expires after 10 minutes.') }}</p>
                    <p x-show="errors.otp" x-text="errors.otp" class="mt-2 text-sm text-red-600"></p>
                </div>

                <p x-show="generalError" x-text="generalError" class="text-sm font-semibold text-red-600"></p>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="button" @click="requestOtp" :disabled="submitting || confirming || cooldown > 0" class="inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-black uppercase tracking-widest text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50">
                        <span x-text="submitting ? 'Sending…' : cooldown > 0 ? 'Resend OTP (' + cooldown + 's)' : 'Resend OTP'"></span>
                    </button>
                    <x-primary-button type="submit" x-bind:disabled="confirming" class="disabled:cursor-not-allowed disabled:opacity-60">
                        <span x-text="confirming ? @js(__('Confirming...')) : @js(__('Confirm OTP'))">{{ __('Confirm OTP') }}</span>
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function passwordUpdateFlow(config) {
            return {
                password: '',
                passwordConfirmation: '',
                otp: '',
                otpOpen: false,
                submitting: false,
                confirming: false,
                message: '',
                generalError: '',
                errors: {},
                cooldown: 0, cooldownUntil: 0, cooldownTimer: null,
                cooldownKey: @js('password-otp-cooldown:'.auth()->id()),
                init() {
                    try { this.cooldownUntil = Number(sessionStorage.getItem(this.cooldownKey) || 0); } catch (_) {}
                    this.startCooldown(Math.max(0, Math.ceil((this.cooldownUntil - Date.now()) / 1000)));
                },
                startCooldown(seconds) {
                    clearInterval(this.cooldownTimer);
                    this.cooldownUntil = Date.now() + seconds * 1000;
                    this.cooldown = Math.max(0, seconds);
                    try { sessionStorage.setItem(this.cooldownKey, String(this.cooldownUntil)); } catch (_) {}
                    if (seconds > 0) this.cooldownTimer = setInterval(() => {
                        this.cooldown = Math.max(0, Math.ceil((this.cooldownUntil - Date.now()) / 1000));
                        if (this.cooldown === 0) clearInterval(this.cooldownTimer);
                    }, 1000);
                },
                destroy() { clearInterval(this.cooldownTimer); },
                payload() {
                    return {
                        password: this.password,
                        password_confirmation: this.passwordConfirmation,
                    };
                },
                async requestOtp() {
                    if (this.submitting || this.confirming || this.cooldown > 0) return;
                    this.submitting = true;
                    this.message = '';
                    this.generalError = '';
                    this.errors = {};

                    try {
                        const data = await this.submit(config.updateUrl, this.payload(), 'PUT');
                        this.startCooldown(data.retry_after || 30);
                        this.message = data.message || @js(__('A password update OTP has been sent to your Recovery / OTP Email.'));
                        this.otp = '';
                        this.otpOpen = true;
                        this.$nextTick(() => document.getElementById('update_password_otp')?.focus());
                    } catch (error) {
                        if (error.retry_after) this.startCooldown(error.retry_after);
                        this.applyErrors(error);
                    } finally {
                        this.submitting = false;
                    }
                },
                async confirmOtp() {
                    this.confirming = true;
                    this.generalError = '';
                    this.errors = {};

                    try {
                        const data = await this.submit(config.confirmUrl, {
                            otp: this.otp,
                            password: this.password,
                            password_confirmation: this.passwordConfirmation,
                        }, 'PUT');
                        this.message = data.message || @js(__('Password updated successfully.'));
                        this.otpOpen = false;
                        window.location.href = data.redirect || @js(route('dashboard'));
                    } catch (error) {
                        this.applyErrors(error);
                    } finally {
                        this.confirming = false;
                    }
                },
                async submit(url, body, method) {
                    const response = await fetch(url, {
                        method,
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': config.csrf,
                        },
                        body: JSON.stringify(body),
                    });
                    const data = await response.json().catch(() => ({}));

                    if (! response.ok) {
                        throw data;
                    }

                    return data;
                },
                applyErrors(data) {
                    const incoming = data?.errors || {};
                    this.errors = Object.fromEntries(Object.entries(incoming).map(([key, value]) => [key, value?.[0] || value]));
                    this.generalError = !this.otpOpen && this.errors.otp ? this.errors.otp : (Object.keys(this.errors).length ? '' : (data?.message || @js(__('Unable to update password. Please try again.'))));
                },
                togglePassword(id, button) {
                    const input = document.getElementById(id);
                    if (! input) return;
                    input.type = input.type === 'password' ? 'text' : 'password';
                    button.innerText = input.type === 'password' ? @js(__('Show')) : @js(__('Hide'));
                },
            };
        }
    </script>
</section>
