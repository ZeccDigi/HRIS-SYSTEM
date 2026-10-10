<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-black text-gray-900">{{ __('Reset Password') }}</h1>
        <p class="mt-2 text-sm text-gray-600">
            {{ __('Enter the OTP sent to your Recovery / OTP Email, then choose a new password.') }}
        </p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-semibold text-green-700">
            {{ session('status') }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.otp.store') }}">
        @csrf

        <div>
            <x-input-label for="email" :value="__('HRIS Username')" />
            <x-text-input
                id="email"
                class="mt-1 block w-full"
                type="email"
                name="email"
                :value="old('email', $email)"
                required
                autofocus
                autocomplete="username"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="otp" :value="__('Reset OTP')" />
            <x-text-input
                id="otp"
                class="mt-1 block w-full tracking-[0.4em]"
                type="text"
                name="otp"
                inputmode="numeric"
                maxlength="6"
                pattern="[0-9]{6}"
                :value="old('otp')"
                required
                autocomplete="one-time-code"
            />
            <p class="mt-1 text-xs text-gray-500">{{ __('The OTP expires after 10 minutes.') }}</p>
            <x-input-error :messages="$errors->get('otp')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password" :value="__('New Password')" />
            <x-text-input id="password" class="mt-1 block w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm New Password')" />
            <x-text-input id="password_confirmation" class="mt-1 block w-full" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-6 flex items-center justify-between gap-4">
            <a href="{{ route('password.request') }}" class="text-sm font-semibold text-gray-600 hover:text-blue-900">
                {{ __('Request another OTP') }}
            </a>

            <x-primary-button>
                {{ __('Reset Password') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
