<x-app-layout>
    <x-slot name="title">{{ __('Profile') }}</x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(auth()->user()?->must_change_password)
                <div class="p-4 sm:p-6 bg-amber-50 border border-amber-200 shadow sm:rounded-lg">
                    <h2 class="text-base font-bold text-amber-900">{{ __('Temporary Password Must Be Changed') }}</h2>
                    <p class="mt-1 text-sm text-amber-800">{{ __('For your account security, please update your password before continuing to use the system.') }}</p>
                </div>
            @endif

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            @if($user->employee)
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <div class="max-w-xl">
                        <section>
                            <header>
                                <h2 class="text-lg font-medium text-gray-900">
                                    {{ __('Reusable E-Signature') }}
                                </h2>

                                <p class="mt-1 text-sm text-gray-600">
                                    {{ __('Upload one signature image that will automatically appear on printable HRIS documents.') }}
                                </p>
                            </header>

                            <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                    <div class="h-20 w-full rounded-lg border border-dashed border-slate-300 bg-white p-2 sm:w-48 flex items-center justify-center text-[10px] font-bold uppercase tracking-widest text-slate-400">
                                        @if($user->employee->effective_signature_url)
                                            <img src="{{ $user->employee->effective_signature_url }}" alt="{{ __('E-signature preview') }}" class="h-full w-full object-contain">
                                        @else
                                            {{ __('No Signature') }}
                                        @endif
                                    </div>

                                    <div class="flex-1">
                                        <p class="text-sm font-semibold text-slate-700">
                                            {{ $user->employee->effective_signature_url ? __('Signature ready for PDS, SALN, Leave, CTO, Locator Slip, and Pass Slip.') : __('No reusable signature uploaded yet.') }}
                                        </p>
                                        <p class="mt-1 text-xs text-slate-500">
                                            {{ __('Accepted file: PNG only, maximum 2MB.') }}
                                        </p>
                                    </div>
                                </div>

                                <div class="mt-4 flex flex-wrap items-center gap-3">
                                    <form id="account_e_signature_form" action="{{ route('employees.e-signature', $user->employee) }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        <label for="account_e_signature_upload" class="inline-flex cursor-pointer items-center justify-center rounded-md bg-blue-900 px-4 py-2 text-xs font-semibold uppercase tracking-widest text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                            {{ $user->employee->effective_signature_url ? __('Change Signature') : __('Upload Signature') }}
                                        </label>
                                        <input type="file" id="account_e_signature_upload" name="e_signature" accept="image/png,.png" class="hidden" onchange="document.getElementById('account_e_signature_form').submit()">
                                    </form>

                                    @if($user->employee->effective_signature_url)
                                        <form action="{{ route('employees.e-signature.destroy', $user->employee) }}" method="POST" onsubmit="return confirm('{{ __('Remove your reusable e-signature?') }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center justify-center rounded-md border border-red-100 bg-white px-4 py-2 text-xs font-semibold uppercase tracking-widest text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                                {{ __('Remove Signature') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                @error('e_signature')
                                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </section>
                    </div>
                </div>
            @endif

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
