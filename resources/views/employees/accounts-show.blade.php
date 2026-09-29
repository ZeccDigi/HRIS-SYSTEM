<x-app-layout>
    <x-slot name="title">{{ __('Account Details') }}</x-slot>

    @php
        $roleLabels = [
            'employee' => 'Employee',
            'hrstaff' => 'HR Admin',
            'recordofficer' => 'Record Officer',
            'chief' => 'Chief',
            'regionaldirector' => 'Regional Director',
            'admin' => 'Admin',
        ];
        $formatRole = fn ($role) => $roleLabels[strtolower((string) $role)] ?? 'Unassigned';
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-bold text-gray-800">{{ __('Employee Account Details') }}</h2>
                        <a href="{{ route('employee-accounts.index') }}" class="text-sm text-gray-500 hover:text-gray-700 underline flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            {{ __('Back to List') }}
                        </a>
                    </div>

                    @if(session('success'))
                        <div class="mb-6 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded-r-lg flex items-center gap-2">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-6 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-r-lg flex items-center gap-2">
                            <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2v6m-9-3a9 9 0 1118 0 9 9 0 01-18 0z"></path>
                            </svg>
                            {{ session('error') }}
                        </div>
                    @endif

                    <form action="{{ route('employee-accounts.update', $user) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- HRIS Username (Readonly) -->
                            <div class="col-span-1 md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('HRIS Username') }}</label>
                                <input type="text" value="{{ $user->email }}" readonly class="block w-full bg-gray-50 border-gray-300 text-gray-500 rounded-lg shadow-sm focus:ring-0 focus:border-gray-300 cursor-not-allowed sm:text-sm">
                                <p class="mt-1 text-xs text-gray-500">{{ __('This username is only for HRIS sign-in. It is not an email inbox.') }}</p>
                            </div>

                            <!-- Lastname -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('Lastname') }} <span class="text-red-500">*</span></label>
                                <input type="text" name="lastname" value="{{ old('lastname', $user->employee?->lastname) }}" required class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                @error('lastname') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            <!-- Firstname -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('Firstname') }} <span class="text-red-500">*</span></label>
                                <input type="text" name="firstname" value="{{ old('firstname', $user->employee?->firstname) }}" required class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                                @error('firstname') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                            </div>

                            <!-- Middlename -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('Middlename') }}</label>
                                <input type="text" name="middlename" value="{{ old('middlename', $user->employee?->middlename) }}" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>

                            <!-- Suffix -->
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('Suffix') }}</label>
                                <input type="text" name="suffix" value="{{ old('suffix', $user->employee?->suffix) }}" placeholder="e.g. Jr, Sr, III" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm">
                            </div>

                            <!-- Division -->
                            <div class="col-span-1">
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Division</label>
                                <select name="division" id="division" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 transition duration-200 text-sm bg-white">
                                    <option value="">-- Select Division --</option>
                                    @foreach($divisionOptions as $divisionOption)
                                        <option value="{{ $divisionOption }}" {{ old('division', $user->employee?->division) === $divisionOption ? 'selected' : '' }}>
                                            {{ $divisionOption }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Position -->
                            <div class="col-span-1">
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Position</label>
                                <select name="position" id="position" 
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 transition duration-200 text-sm bg-white">
                                    <option value="">-- Select Position --</option>
                                    <option value="hrstaff" {{ $user->employee?->position == 'hrstaff' ? 'selected' : '' }}>HR Admin</option>
                                    <option value="chief" {{ $user->employee?->position == 'chief' ? 'selected' : '' }}>Chief</option>
                                    <option value="regionaldirector" {{ $user->employee?->position == 'regionaldirector' ? 'selected' : '' }}>Regional Director</option>
                                    <option value="admin" {{ $user->employee?->position == 'admin' ? 'selected' : '' }}>Admin</option>
                                    <option value="employee" {{ $user->employee?->position == 'employee' ? 'selected' : '' }}>Regular Employee</option>
                                </select>
                            </div>

                            <!-- Remarks -->
                            <div class="col-span-1 md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-1">{{ __('Remarks') }}</label>
                                <textarea name="remarks" rows="3" class="block w-full border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm" placeholder="Add optional remarks or notes about this employee account...">{{ old('remarks', $user->employee?->remarks) }}</textarea>
                            </div>

                        </div>

                        <div class="mt-8 flex justify-end">
                            <button type="submit" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                {{ __('Save Changes') }}
                            </button>
                        </div>
                    </form>

                    <hr class="my-8 border-gray-200">

                    <!-- Approve Section and Status -->
                    <div class="bg-gray-50/50 p-6 rounded-xl border border-gray-200">
                        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            {{ __('Account Status & Role') }}
                        </h3>
                        
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-6">
                            <div class="space-y-3">
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-semibold text-gray-600 w-24">{{ __('Current Role:') }}</span> 
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800 border border-indigo-200">
                                        {{ $formatRole($user->employee?->account_role) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-3">
                                    <span class="text-sm font-semibold text-gray-600 w-24">{{ __('Status:') }}</span> 
                                    @if($user->is_approved)
                                        <span class="inline-flex items-center gap-1 text-sm font-bold text-green-600">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                                            {{ __('Approved') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-sm font-bold text-amber-600">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            {{ __('Pending Approval') }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            @if(!$user->is_approved)
                            <div class="bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
                                <form action="{{ route('employee-accounts.approve', $user) }}" method="POST" class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                                    @csrf
                                    @method('PATCH')
                                    <select name="account_role" required class="text-sm border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                                        <option value="">-- Select Role to Approve --</option>
                                        <option value="employee" {{ $user->employee?->account_role === 'employee' ? 'selected' : '' }}>Employee</option>
                                        <option value="hrstaff" {{ $user->employee?->account_role === 'hrstaff' ? 'selected' : '' }}>HR Admin</option>
                                        <option value="recordofficer" {{ $user->employee?->account_role === 'recordofficer' ? 'selected' : '' }}>Record Officer</option>
                                        <option value="chief" {{ $user->employee?->account_role === 'chief' ? 'selected' : '' }}>Chief</option>
                                        <option value="regionaldirector" {{ $user->employee?->account_role === 'regionaldirector' ? 'selected' : '' }}>Regional Director</option>
                                        <option value="admin" {{ $user->employee?->account_role === 'admin' ? 'selected' : '' }}>Admin</option>
                                    </select>
                                    <button type="submit" class="inline-flex justify-center items-center px-4 py-2 bg-green-600 border border-transparent rounded-lg font-bold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-sm">
                                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        {{ __('Approve Account') }}
                                    </button>
                                </form>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>


