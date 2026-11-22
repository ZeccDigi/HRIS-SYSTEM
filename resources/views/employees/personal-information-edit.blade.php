<x-app-layout>
    <x-slot name="title">{{ __('Edit Personal Information') }}</x-slot>

    <div class="py-12 bg-slate-50 min-h-screen flex items-center justify-center sm:px-6 lg:px-8">
        <div class="max-w-4xl w-full">
            
            <!-- Back Navigation Link -->
            <div class="mb-4">
                <a href="{{ route('personal-information.show') }}" class="inline-flex items-center text-sm font-semibold text-slate-500 hover:text-slate-800 transition-colors group">
                    <svg class="h-4 w-4 mr-2 transform group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                    </svg>
                    {{ __('Back to Profile') }}
                </a>
            </div>

            <!-- Main Container -->
            <div class="bg-white rounded-2xl shadow-xl shadow-slate-100 border border-slate-100 overflow-hidden grid grid-cols-1 md:grid-cols-12">
                
                <!-- Left Sidebar: Context / Styling -->
                <div class="md:col-span-4 bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-800 p-8 flex flex-col justify-between text-white relative overflow-hidden">
                    <!-- Subtle background graphic pattern -->
                    <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#fff_1px,transparent_1px)] [background-size:16px_16px]"></div>
                    
                    <div class="relative z-10">
                        <div class="inline-flex items-center justify-center p-3 bg-white/10 rounded-xl backdrop-blur-md mb-6">
                            <svg class="h-6 w-6 text-indigo-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <h1 class="text-2xl font-bold tracking-tight">{{ __('Personal Info') }}</h1>
                        <p class="text-indigo-100/80 text-sm mt-2 leading-relaxed">
                            {{ __('Keep your profile data accurate so HR and team members can reach you seamlessly.') }}
                        </p>
                    </div>

                    <div class="relative z-10 mt-8 md:mt-0 pt-6 border-t border-indigo-500/30 text-xs text-indigo-200/70">
                        {{ __('Secured Profile Management System') }}
                    </div>
                </div>

                <!-- Right Sidebar: The Actual Form -->
                <div class="md:col-span-8 p-8 sm:p-10">
                    <div class="mb-6">
                        <h2 class="text-xl font-bold text-slate-800">{{ __('Edit Profile Details') }}</h2>
                        <p class="text-sm text-slate-400 mt-1">{{ __('Update your personal records below.') }}</p>
                    </div>

                    <form action="{{ route('personal-information.update') }}" method="POST" class="space-y-5">
                        @csrf
                        @method('PATCH')

                        <!-- Name Grid -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- First Name -->
                            <div>
                                <x-input-label for="firstname" :value="__('First Name')" class="text-xs font-bold text-slate-600 uppercase tracking-wider" />
                                <input type="text" name="firstname" id="firstname" value="{{ old('firstname', $employee->firstname) }}" class="mt-1.5 block w-full rounded-lg border-slate-200 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200/50 sm:text-sm text-slate-700 transition-colors" required>
                                <x-input-error class="mt-1" :messages="$errors->get('firstname')" />
                            </div>

                            <!-- Last Name -->
                            <div>
                                <x-input-label for="lastname" :value="__('Last Name')" class="text-xs font-bold text-slate-600 uppercase tracking-wider" />
                                <input type="text" name="lastname" id="lastname" value="{{ old('lastname', $employee->lastname) }}" class="mt-1.5 block w-full rounded-lg border-slate-200 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200/50 sm:text-sm text-slate-700 transition-colors" required>
                                <x-input-error class="mt-1" :messages="$errors->get('lastname')" />
                            </div>

                            <!-- Middle Name -->
                            <div class="sm:col-span-2">
                                <x-input-label for="middlename" :value="__('Middle Name')" class="text-xs font-bold text-slate-600 uppercase tracking-wider" />
                                <input type="text" name="middlename" id="middlename" value="{{ old('middlename', $employee->middlename) }}" class="mt-1.5 block w-full rounded-lg border-slate-200 shadow-sm focus:border-indigo-500 focus:ring focus:ring-indigo-200/50 sm:text-sm text-slate-700 transition-colors">
                                <x-input-error class="mt-1" :messages="$errors->get('middlename')" />
                            </div>
                        </div>

                        <!-- Info Divider Line -->
                        <hr class="border-slate-100 my-2">

                        <!-- Contact Number -->
                        <div>
                            <x-input-label for="contact_number" :value="__('Contact Number')" class="text-xs font-bold text-slate-600 uppercase tracking-wider" />
                            <div class="relative mt-1.5 rounded-lg shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                </div>
                                <input type="text" name="contact_number" inputmode="numeric" minlength="11" maxlength="11" pattern="[0-9]{11}" oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)" id="contact_number" value="{{ old('contact_number', $employee->contact_number ?? $employee->pdsPersonal?->mobile_no) }}" class="block w-full rounded-lg border-slate-200 pl-9 focus:border-indigo-500 focus:ring focus:ring-indigo-200/50 sm:text-sm text-slate-700 transition-colors" placeholder="e.g. 09123456789">
                            </div>
                            <x-input-error class="mt-1" :messages="$errors->get('contact_number')" />
                        </div>

                        <!-- Position (Readonly UI) -->
                        <div class="bg-slate-50 border border-slate-100 p-3.5 rounded-xl flex items-start justify-between gap-4">
                            <div>
                                <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">{{ __('Position') }}</span>
                                <span class="text-sm font-semibold text-slate-700 block mt-0.5">{{ strtoupper($employee->position) }}</span>
                            </div>
                            <span class="text-[11px] bg-slate-200 text-slate-600 px-2 py-0.5 rounded-md font-medium shrink-0">{{ __('Locked') }}</span>
                        </div>

                        <!-- Email (Readonly UI) -->
                        <div class="bg-slate-50 border border-slate-100 p-3.5 rounded-xl">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block">{{ __('Email Address') }}</span>
                                    <span class="text-sm font-semibold text-slate-600 block mt-0.5 break-all">
                                        {{ $employee->notification_email ?: ($employee->pdsPersonal?->email_address ?: __('N/A')) }}
                                    </span>
                                </div>
                            </div>
                            <p class="text-[11px] text-slate-400 mt-2 pt-2 border-t border-slate-200/60">
                                {{ __('To change your email, visit ') }} 
                                <a href="{{ route('profile.edit') }}" class="text-indigo-600 hover:text-indigo-700 font-semibold underline underline-offset-2">{{ __('Account Settings') }}</a>.
                            </p>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100 mt-6">
                            <a href="{{ route('personal-information.show') }}" class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 hover:bg-slate-50 rounded-xl transition-colors">
                                {{ __('Cancel') }}
                            </a>
                            <button type="submit" class="inline-flex items-center justify-center px-5 py-2.5 bg-indigo-600 border border-transparent rounded-xl font-semibold text-sm text-white hover:bg-indigo-700 active:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-all shadow-md shadow-indigo-100">
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
