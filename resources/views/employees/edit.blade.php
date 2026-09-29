<x-app-layout>
    <x-slot name="title">{{ __('Edit Employee: ') }}</x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4">
                <a href="{{ route('employees.show', $employee) }}" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition-colors hover:bg-slate-50">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    {{ __('Back to Record') }}
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100">
                <div class="p-8 text-gray-900 border-b border-gray-200">
                    <form action="{{ route('employees.update', $employee) }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PATCH')
                        
                        <div class="pb-6 border-b border-gray-100">
                            <div class="mb-5">
                                <h3 class="text-lg font-medium text-gray-900 mb-1">{{ __('Personal Information') }}</h3>
                                <p class="text-sm text-gray-500">{{ __('Use the employee\'s official name as per their government records.') }}</p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="lastname" :value="__('Last Name')" />
                                    <x-text-input id="lastname" name="lastname" type="text" class="mt-1 block w-full" :value="old('lastname', $employee->lastname)" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('lastname')" />
                                </div>
                                <div>
                                    <x-input-label for="firstname" :value="__('First Name')" />
                                    <x-text-input id="firstname" name="firstname" type="text" class="mt-1 block w-full" :value="old('firstname', $employee->firstname)" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('firstname')" />
                                </div>
                                <div>
                                    <x-input-label for="middlename" :value="__('Middle Name')" />
                                    <x-text-input id="middlename" name="middlename" type="text" class="mt-1 block w-full" :value="old('middlename', $employee->middlename)" />
                                    <x-input-error class="mt-2" :messages="$errors->get('middlename')" />
                                </div>
                                <div>
                                    <x-input-label for="contact_number" :value="__('Contact Number')" />
                                    <x-text-input id="contact_number" name="contact_number" type="text" class="mt-1 block w-full" :value="old('contact_number', $employee->contact_number)" placeholder="e.g., 09123456789" />
                                    <x-input-error class="mt-2" :messages="$errors->get('contact_number')" />
                                </div>
                                <div>
                                    <x-input-label for="notification_email" :value="__('Recovery / OTP Email')" />
                                    <x-text-input id="notification_email" name="notification_email" type="email" class="mt-1 block w-full" :value="old('notification_email', $employee->notification_email)" placeholder="juandelacruz@gmail.com" />
                                    <p class="mt-1 text-xs text-gray-500">{{ __('Enter an active email address where the employee can receive account details, OTP verification codes, and security notifications.') }}</p>
                                    <x-input-error class="mt-2" :messages="$errors->get('notification_email')" />
                                </div>
                                <div>
                                    <x-input-label for="rfid_number" :value="__('RFID Number')" />
                                    <x-text-input id="rfid_number" name="rfid_number" type="text" class="mt-1 block w-full" :value="old('rfid_number', $employee->rfid_number)" placeholder="e.g., RFID-12345" />
                                    <x-input-error class="mt-2" :messages="$errors->get('rfid_number')" />
                                </div>
                            </div>
                        </div>

                        <div class="pb-6 pt-4 border-b border-gray-100">
                            <div class="mb-5">
                                <h3 class="text-lg font-medium text-gray-900 mb-1">{{ __('Employment Details') }}</h3>
                                <p class="text-sm text-gray-500">{{ __('Set the employee division, job position, system role, and employment status.') }}</p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="division" :value="__('Division')" />
                                    <select id="division" name="division" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                        <option value="">{{ __('Select division') }}</option>
                                        @foreach($divisionOptions as $division)
                                            <option value="{{ $division }}" {{ old('division', $employee->division) == $division ? 'selected' : '' }}>{{ $division }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error class="mt-2" :messages="$errors->get('division')" />
                                </div>
                                <div>
                                    <x-input-label for="position" :value="__('Job Position / Designation')" />
                                    <x-text-input id="position" name="position" type="text" class="mt-1 block w-full" :value="old('position', $employee->position)" placeholder="e.g., Secretary, Administrative Aide" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('position')" />
                                </div>
                                <div>
                                    <x-input-label for="account_role" :value="__('Role')" />
                                    <select id="account_role" name="account_role" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                        <option value="">{{ __('Select a role') }}</option>
                                        @php
                                            $roleLabels = [
                                                'employee' => 'Employee',
                                                'hrstaff' => 'HR Admin',
                                                'recordofficer' => 'Record Officer',
                                                'chief' => 'Chief',
                                                'regionaldirector' => 'Regional Director',
                                                'admin' => 'Admin',
                                            ];
                                        @endphp
                                        @foreach($roles as $role)
                                            <option value="{{ $role }}" {{ old('account_role', $employee->account_role) == $role ? 'selected' : '' }}>{{ $roleLabels[$role] ?? strtoupper($role) }}</option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-gray-500">{{ __('Assign the employee role for system access.') }}</p>
                                    <x-input-error class="mt-2" :messages="$errors->get('account_role')" />
                                </div>
                                <div>
                                    <x-input-label for="employment_status" :value="__('Employment Status')" />
                                    <select id="employment_status" name="employment_status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                        <option value="">{{ __('Select employment status') }}</option>
                                        @foreach($employmentStatuses as $status)
                                            <option value="{{ $status }}" {{ old('employment_status', $employee->employment_status) == $status ? 'selected' : '' }}>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error class="mt-2" :messages="$errors->get('employment_status')" />
                                </div>
                                <div>
                                    <x-input-label for="service_start_date" :value="__('Service Start Date')" />
                                    <x-text-input id="service_start_date" name="service_start_date" type="date" class="mt-1 block w-full" :value="old('service_start_date', optional($employee->service_start_date)->format('Y-m-d') ?? $employee->service_start_date)" />
                                    <p class="mt-1 text-xs text-gray-500">{{ __('Used as the starting point for organization leave-credit accrual. If blank, the employee record creation date is used.') }}</p>
                                    <x-input-error class="mt-2" :messages="$errors->get('service_start_date')" />
                                </div>
                            </div>
                        </div>

                        <div class="pt-4">
                            <div class="mb-5">
                                <h3 class="text-lg font-medium text-gray-900 mb-1">{{ __('Account Access') }}</h3>
                                <p class="text-sm text-gray-500">{{ __('The HRIS username is used only for system sign-in. Account messages are sent to the Recovery / OTP Email above.') }}</p>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="hris_username" :value="__('HRIS Username')" />
                                    <input id="hris_username" type="text" class="mt-1 block w-full rounded-md border-gray-200 bg-gray-50 text-sm text-gray-500 shadow-sm" value="{{ $employee->user?->email ?? __('No login account linked') }}" readonly>
                                    <p class="mt-1 text-xs text-gray-500">{{ __("This is the employee's username for signing in to the HRIS. The @dmw.gov.ph suffix is part of the login username and is not an email inbox.") }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center justify-end gap-4 mt-10 pt-6 border-t border-gray-100">
                            <x-primary-button>
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                                </svg>
                                {{ __('Update Employee') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

