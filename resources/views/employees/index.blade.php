<x-app-layout>
    <x-slot name="title">{{ __('Manage Employees') }}</x-slot>

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

    <div class="overflow-x-hidden py-12" x-data="{ tab: 'employees', accountsTab: 'pending' }">
        <div class="mx-auto w-full max-w-7xl min-w-0 px-4 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Header Actions -->
            <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
                <div class="max-w-full overflow-x-auto">
                <div class="inline-flex rounded-xl bg-white border border-gray-100 shadow-sm p-1">
                    <button type="button"
                            class="px-4 py-2 text-sm font-semibold rounded-lg transition"
                            :class="tab === 'employees' ? 'bg-indigo-100 text-indigo-700' : 'text-gray-500 hover:text-gray-700'"
                            @click="tab = 'employees'">
                        Employees Records
                    </button>
                    {{-- Registration-based account approval is hidden while HR creates employee accounts directly.
                    <button type="button"
                            class="px-4 py-2 text-sm font-semibold rounded-lg transition"
                            :class="tab === 'accounts' ? 'bg-amber-100 text-amber-700' : 'text-gray-500 hover:text-gray-700'"
                            @click="tab = 'accounts'">
                        Employees Account
                    </button>
                    --}}
                    <button type="button"
                            class="px-4 py-2 text-sm font-semibold rounded-lg transition"
                            :class="tab === 'archived' ? 'bg-slate-200 text-slate-800' : 'text-gray-500 hover:text-gray-700'"
                            @click="tab = 'archived'">
                        Archived Employees ({{ $archivedEmployees->count() }})
                    </button>
                </div>
                </div>

                <a href="{{ route('employees.create') }}"
                   class="inline-flex shrink-0 items-center justify-center rounded-xl bg-indigo-700 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    <svg class="mr-2 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    {{ __('Add Employee') }}
                </a>
            </div>

            <div class="w-full max-w-full min-w-0 overflow-hidden bg-white shadow-sm sm:rounded-lg border border-gray-100" x-show="tab === 'employees'" x-cloak>
                <div class="min-w-0 p-6 text-gray-900">
                    <form id="employeeFilterForm" method="GET" action="{{ route('employees.index') }}" data-filter-url="{{ route('employees.filter') }}" class="mb-4 flex min-w-0 flex-col gap-2 rounded-xl border border-gray-100 bg-gray-50/70 p-2 sm:flex-row sm:items-center">
                        <div class="relative min-w-0 sm:flex-1">
                            <label for="search" class="sr-only">{{ __('Search employee') }}</label>
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M21 21l-4.35-4.35M10.75 18.5a7.75 7.75 0 100-15.5 7.75 7.75 0 000 15.5z" />
                                </svg>
                            </span>
                            <input id="search" name="search" type="search" value="{{ $search }}" placeholder="{{ __('Search employee...') }}" class="block h-9 w-full rounded-lg border-gray-300 pl-10 pr-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" data-autofilter />
                        </div>
                        <div class="sm:w-56 sm:shrink-0">
                            <label for="division" class="sr-only">{{ __('Division') }}</label>
                            <select id="division" name="division" class="block h-9 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('All Divisions') }}</option>
                                @foreach($divisionOptions as $divisionOption)
                                    <option value="{{ $divisionOption }}" {{ $division === $divisionOption ? 'selected' : '' }}>{{ $divisionOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="sm:w-44 sm:shrink-0">
                            <label for="sort" class="sr-only">{{ __('Sort') }}</label>
                            <select id="sort" name="sort" class="block h-9 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="name_asc" {{ $sort === 'name_asc' ? 'selected' : '' }}>{{ __('Name A-Z') }}</option>
                                <option value="name_desc" {{ $sort === 'name_desc' ? 'selected' : '' }}>{{ __('Name Z-A') }}</option>
                                <option value="division_asc" {{ $sort === 'division_asc' ? 'selected' : '' }}>{{ __('Division') }}</option>
                                <option value="newest" {{ $sort === 'newest' ? 'selected' : '' }}>{{ __('Newest') }}</option>
                            </select>
                        </div>
                        <div class="flex items-center gap-2 sm:shrink-0">
                            <button type="button" id="employeeFilterReset" class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-xs font-bold uppercase tracking-wider text-gray-700 transition hover:bg-gray-50">
                                {{ __('Reset') }}
                            </button>
                        </div>
                    </form>

                    <div class="w-full max-w-full min-w-0 overflow-x-auto overscroll-x-contain">
                        <table class="min-w-full w-full table-fixed divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="w-[28%] px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Employees Name') }}</th>
                                    <th class="w-[18%] px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('RFID Number') }}</th>
                                    <th class="w-[27%] px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Division') }}</th>
                                    <th class="w-[15%] px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Role') }}</th>
                                    <th class="w-[12%] px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody id="employeeTableBody" class="bg-white divide-y divide-gray-200">
                                @include('employees.partials.rows', ['employees' => $employees])
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="w-full max-w-full min-w-0 overflow-hidden bg-white shadow-sm sm:rounded-lg border border-gray-100" x-show="tab === 'archived'" x-cloak>
                <div class="min-w-0 p-6 text-gray-900">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center">
                            <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M6 7v12a2 2 0 002 2h8a2 2 0 002-2V7M10 11h4" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-800">{{ __('Archived Employees') }}</h2>
                            <p class="text-xs text-gray-400">{{ __('Archived records are hidden from active lists and linked login accounts are disabled.') }}</p>
                        </div>
                    </div>

                    <div class="w-full max-w-full min-w-0 overflow-x-auto overscroll-x-contain">
                        <table class="min-w-[760px] w-full table-fixed divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="w-[22%] px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Employees Name') }}</th>
                                    <th class="w-[16%] px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Position') }}</th>
                                    <th class="w-[15%] px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Employment Status') }}</th>
                                    <th class="w-[25%] px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Linked Account') }}</th>
                                    <th class="w-[15%] px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Archived Date') }}</th>
                                    <th class="w-[7%] px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($archivedEmployees as $employee)
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-4 py-4">
                                            <div class="truncate text-sm font-medium text-gray-900">{{ $employee->lastname }}, {{ $employee->firstname }} {{ $employee->middlename ?? '-' }}</div>
                                            <div class="truncate text-xs text-gray-400">{{ $employee->notification_email ?? __('No recovery email') }}</div>
                                        </td>
                                        <td class="px-4 py-4">
                                            <span class="max-w-full truncate px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-slate-100 text-slate-800">
                                                {{ $employee->position ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4">
                                            <span class="max-w-full truncate px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-700">
                                                {{ $employee->employment_status ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-gray-500">
                                            @if($employee->user)
                                                <div class="flex min-w-0 flex-col">
                                                    <span class="truncate">{{ $employee->user->email }}</span>
                                                    <span class="mt-1 w-fit px-2 py-0.5 rounded-full bg-red-100 text-red-700 text-[10px] font-bold uppercase">{{ __('Login Disabled') }}</span>
                                                </div>
                                            @else
                                                <span class="text-gray-400 italic text-xs">{{ __('Not linked') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-sm text-gray-500">
                                            {{ $employee->deleted_at?->format('M d, Y h:i A') ?? '-' }}
                                        </td>
                                        <td class="px-4 py-4 text-right text-sm font-medium">
                                            <div class="flex items-center justify-end gap-1">
                                            <form action="{{ route('employees.restore', $employee->id) }}" method="POST" class="inline-block" onsubmit="return confirm('{{ __('Restore this employee and re-enable linked login access?') }}')">
                                                @csrf
                                                @method('PATCH')
        <button type="submit"
        class="inline-flex h-9 w-9 items-center justify-center rounded-full text-emerald-700 transition-colors duration-200 hover:bg-emerald-50 hover:text-emerald-900"
        title="{{ __('Restore') }}"
        aria-label="{{ __('Restore') }}">

    <svg xmlns="http://www.w3.org/2000/svg"
         class="h-5 w-5"
         viewBox="0 0 24 24"
         fill="none"
         stroke="currentColor"
         stroke-width="2.2"
         stroke-linecap="round"
         stroke-linejoin="round">
        <path d="M4.5 9A8 8 0 1 1 4 14" />
        <path d="M4.5 4.5V9H9" />
    </svg>


    <span class="sr-only">{{ __('Restore') }}</span>
</button>
                                            </form>
                                            @if(auth()->user()?->hasRole('admin'))
                                                <form action="{{ route('employees.force-delete', $employee->id) }}" method="POST" class="inline-block" onsubmit="return confirm('{{ __('Permanently delete this employee record and linked login account? This cannot be undone.') }}')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-red-700 transition-colors duration-200 hover:bg-red-50 hover:text-red-900" title="{{ __('Delete Permanently') }}" aria-label="{{ __('Delete Permanently') }}">
                                                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M3.75 7.5h16.5M9.75 11.25v5.5M14.25 11.25v5.5M6.75 7.5l.75 12A1.75 1.75 0 009.25 21h5.5a1.75 1.75 0 001.75-1.5l.75-12M9 7.5V5.25A1.25 1.25 0 0110.25 4h3.5A1.25 1.25 0 0115 5.25V7.5" />
                                                        </svg>
                                                        <span class="sr-only">{{ __('Delete Permanently') }}</span>
                                                    </button>
                                                </form>
                                            @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                            <div class="flex flex-col items-center">
                                                <svg class="w-12 h-12 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M6 7v12a2 2 0 002 2h8a2 2 0 002-2V7M10 11h4" />
                                                </svg>
                                                <p class="text-lg font-medium">{{ __('No archived employees') }}</p>
                                                <p class="text-sm mt-1 text-gray-400">{{ __('Archived employee records will appear here for recovery.') }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Registration-based employee account approval panel is hidden while HR-created accounts are the active flow.
            <div x-show="tab === 'accounts'" x-cloak>
                <div class="mb-6 mt-6">
                    <div class="inline-flex rounded-xl bg-white border border-gray-100 shadow-sm p-1">
                        <button type="button"
                                class="px-4 py-2 text-sm font-semibold rounded-lg transition"
                                :class="accountsTab === 'pending' ? 'bg-amber-100 text-amber-700' : 'text-gray-500 hover:text-gray-700'"
                                @click="accountsTab = 'pending'">
                            Pending Accounts ({{ $pendingAccounts->count() }})
                        </button>
                        <button type="button"
                                class="px-4 py-2 text-sm font-semibold rounded-lg transition"
                                :class="accountsTab === 'approved' ? 'bg-green-100 text-green-700' : 'text-gray-500 hover:text-gray-700'"
                                @click="accountsTab = 'approved'">
                            Active Accounts ({{ $approvedAccounts->count() }})
                        </button>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100 mb-8" x-show="accountsTab === 'pending'" x-cloak>
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-800">{{ __('Pending Approval') }}</h2>
                                <p class="text-xs text-gray-400">{{ __('Registered accounts waiting for HR verification') }}</p>
                            </div>
                            @if($pendingAccounts->count() > 0)
                                <span class="ml-auto px-3 py-1 text-xs font-bold rounded-full bg-amber-100 text-amber-700">
                                    {{ $pendingAccounts->count() }} {{ __('pending') }}
                                </span>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            @forelse($pendingAccounts as $user)
                                <div class="border border-gray-100 rounded-2xl p-5 bg-white shadow-sm hover:shadow-md transition account-card" data-link="{{ $user->employee ? route('employee-accounts.show', $user) : '' }}">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex items-center gap-3">
                                            <x-profile-avatar :user="$user" size="md" variant="amber" />
                                            <div>
                                                @if($user->employee)
                                                    <a href="{{ route('employee-accounts.show', $user) }}" class="text-sm font-semibold text-gray-900 hover:text-indigo-700 hover:underline">
                                                        {{ $user->employee->lastname }}, {{ $user->employee->firstname }} {{ $user->employee->middlename ?? '' }}
                                                        @if($user->employee->suffix)
                                                            <span class="text-gray-400">{{ $user->employee->suffix }}</span>
                                                        @endif
                                                    </a>
                                                @else
                                                    <p class="text-sm font-semibold text-gray-900">-</p>
                                                @endif
                                                <p class="text-xs text-gray-500">{{ $user->email }}</p>
                                            </div>
                                        </div>
                                        <span class="text-[10px] font-semibold text-gray-500">{{ $user->created_at->format('M d, Y') }}</span>
                                    </div>

                                    <div class="mt-4 grid grid-cols-2 gap-3 text-xs text-gray-600">
                                        <div>
                                            <span class="block text-[10px] uppercase tracking-wider text-gray-400">Division</span>
                                            <span class="font-semibold">
                                                {{ $user->employee?->division ?? 'Not set' }}

                                            </span>
                                        </div>
                                        <div>
                                            <span class="block text-[10px] uppercase tracking-wider text-gray-400">Position</span>
                                            <span class="inline-flex px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 font-semibold uppercase">
                                                {{ $user->employee?->position ?? 'N/A' }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="block text-[10px] uppercase tracking-wider text-gray-400">Account Role</span>
                                            <span class="inline-flex px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800 font-semibold">
                                                {{ $formatRole($user->employee?->account_role) }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="block text-[10px] uppercase tracking-wider text-gray-400">Status</span>
                                            <span class="inline-flex px-2 py-0.5 rounded-full bg-amber-100 text-amber-700 font-semibold uppercase">Pending</span>
                                        </div>
                                    </div>

                                    <div class="mt-4 flex items-center justify-end gap-2">
                                        <button type="button"
                                                class="inline-flex h-8 w-8 items-center justify-center bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors shadow-sm approve-btn"
                                                data-approve-action="{{ route('employee-accounts.approve', $user) }}"
                                                data-employee-name="{{ $user->employee?->lastname ?? '-' }}, {{ $user->employee?->firstname ?? '-' }}"
                                                data-current-role="{{ $user->employee?->account_role ?? '' }}"
                                                title="{{ __('Approve') }}"
                                                aria-label="{{ __('Approve') }}">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            <span class="sr-only">{{ __('Approve') }}</span>
                                        </button>
                                        <form action="{{ route('employee-accounts.reject', $user) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex h-8 w-8 items-center justify-center bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors shadow-sm" onclick="return confirm('Reject and delete this account? This action cannot be undone.')" title="{{ __('Reject') }}" aria-label="{{ __('Reject') }}">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                                <span class="sr-only">{{ __('Reject') }}</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @empty
                                <div class="col-span-full px-6 py-10 text-center text-gray-500">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <p class="text-sm font-medium text-gray-400">{{ __('No pending accounts') }}</p>
                                        <p class="text-xs text-gray-300 mt-1">{{ __('All registered accounts have been verified.') }}</p>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100" x-show="accountsTab === 'approved'" x-cloak>
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-800">{{ __('Approved Accounts') }}</h2>
                                <p class="text-xs text-gray-400">{{ __('Active employee accounts') }}</p>
                            </div>
                            <span class="ml-auto px-3 py-1 text-xs font-bold rounded-full bg-green-100 text-green-700">
                                {{ $approvedAccounts->count() }} {{ __('active') }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            @forelse($approvedAccounts as $user)
                                <div class="border border-gray-100 rounded-2xl p-5 bg-white shadow-sm hover:shadow-md transition account-card" data-link="{{ $user->employee ? route('employee-accounts.show', $user) : '' }}">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex items-center gap-3">
                                            <x-profile-avatar :user="$user" size="md" variant="green" />
                                            <div>
                                                @if($user->employee)
                                                    <a href="{{ route('employee-accounts.show', $user) }}" class="text-sm font-semibold text-gray-900 hover:text-indigo-700 hover:underline">
                                                        {{ $user->employee->lastname }}, {{ $user->employee->firstname }} {{ $user->employee->middlename ?? '' }}
                                                    </a>
                                                @else
                                                    <p class="text-sm font-semibold text-gray-900">-</p>
                                                @endif
                                                <p class="text-xs text-gray-500">{{ $user->email }}</p>
                                            </div>
                                        </div>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-700">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                            {{ __('Approved') }}
                                        </span>
                                    </div>

                                    <div class="mt-4 grid grid-cols-2 gap-3 text-xs text-gray-600">
                                        <div>
                                            <span class="block text-[10px] uppercase tracking-wider text-gray-400">Division</span>
                                            <span class="font-semibold">
                                                {{ $user->employee?->division ?? '-' }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="block text-[10px] uppercase tracking-wider text-gray-400">Position</span>
                                            <span class="inline-flex px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-800 font-semibold uppercase">
                                                {{ $user->employee?->position ?? 'N/A' }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="block text-[10px] uppercase tracking-wider text-gray-400">Account Role</span>
                                            <span class="inline-flex px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 font-semibold">
                                                {{ $formatRole($user->employee?->account_role) }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-span-full px-6 py-10 text-center text-gray-400 text-sm">
                                    {{ __('No approved accounts yet.') }}
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            --}}
        </div>
    </div>

    <div id="approveModal" class="fixed inset-0 z-50 hidden items-center justify-center px-4">
        <div class="absolute inset-0 bg-black/50" id="approveBackdrop"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 sm:p-8">
            <div class="flex items-start justify-between gap-4">
                <h3 class="text-lg font-bold text-gray-800">Approve Account</h3>
                <button type="button" class="text-gray-400 hover:text-gray-600" id="approveClose">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <p class="mt-2 text-sm text-gray-600" id="approveEmployeeName"></p>
            <form method="POST" id="approveForm" class="mt-5 space-y-4">
                @csrf
                @method('PATCH')
                <div>
                    <label class="block text-xs font-semibold text-gray-600 mb-1">Assign Account Role</label>
                    <select name="account_role" id="approveRole" required
                            class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-800 transition duration-200 text-sm bg-white">
                        <option value="">-- Select Role --</option>
                        <option value="employee">Employee</option>
                        <option value="hrstaff">HR Admin</option>
                        <option value="recordofficer">Record Officer</option>
                        <option value="chief">Chief</option>
                        <option value="regionaldirector">Regional Director</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="flex items-center justify-end gap-3">
                    <button type="button"
                            class="px-4 py-2.5 text-sm font-semibold text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50"
                            id="approveCancel">
                        Cancel
                    </button>
                    <button type="submit"
                            class="px-4 py-2.5 text-sm font-bold text-white bg-green-600 rounded-lg hover:bg-green-700">
                        Approve
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const approveButtons = document.querySelectorAll('.approve-btn');
        const approveModal = document.getElementById('approveModal');
        const approveBackdrop = document.getElementById('approveBackdrop');
        const approveClose = document.getElementById('approveClose');
        const approveCancel = document.getElementById('approveCancel');
        const approveForm = document.getElementById('approveForm');
        const approveRole = document.getElementById('approveRole');
        const approveEmployeeName = document.getElementById('approveEmployeeName');

        function openApproveModal(event) {
            const button = event.currentTarget;
            const action = button.getAttribute('data-approve-action');
            const employeeName = button.getAttribute('data-employee-name');
            const currentRole = button.getAttribute('data-current-role');

            approveForm.setAttribute('action', action);
            approveEmployeeName.textContent = 'Assign account role for: ' + employeeName;
            approveRole.value = currentRole || '';

            approveModal.classList.remove('hidden');
            approveModal.classList.add('flex');
        }

        function closeApproveModal() {
            approveModal.classList.add('hidden');
            approveModal.classList.remove('flex');
        }

        approveButtons.forEach((button) => {
            button.addEventListener('click', openApproveModal);
        });
        approveBackdrop.addEventListener('click', closeApproveModal);
        approveClose.addEventListener('click', closeApproveModal);
        approveCancel.addEventListener('click', closeApproveModal);

        const accountCards = document.querySelectorAll('.account-card');
        accountCards.forEach((card) => {
            card.addEventListener('click', (event) => {
                if (event.target.closest('button, a, form')) {
                    return;
                }

                const link = card.getAttribute('data-link');
                if (link) {
                    window.location.href = link;
                }
            });
        });

        const employeeFilterForm = document.getElementById('employeeFilterForm');
        const employeeSearchInput = document.querySelector('[data-autofilter]');
        const employeeDivisionSelect = document.getElementById('division');
        const employeeSortSelect = document.getElementById('sort');
        const employeeTableBody = document.getElementById('employeeTableBody');
        const employeeFilterReset = document.getElementById('employeeFilterReset');
        let employeeSearchTimer;

        async function filterEmployees() {
            if (!employeeFilterForm || !employeeTableBody) {
                return;
            }

            const filterUrl = employeeFilterForm.dataset.filterUrl;
            const params = new URLSearchParams(new FormData(employeeFilterForm));

            employeeTableBody.style.opacity = '0.55';

            try {
                const response = await fetch(`${filterUrl}?${params.toString()}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    throw new Error('Unable to filter employees.');
                }

                const data = await response.json();
                employeeTableBody.innerHTML = data.html;

                const nextUrl = `${employeeFilterForm.action}?${params.toString()}`;
                window.history.replaceState({}, '', nextUrl);
            } catch (error) {
                employeeFilterForm.submit();
            } finally {
                employeeTableBody.style.opacity = '1';
            }
        }

        if (employeeFilterForm) {
            employeeFilterForm.addEventListener('submit', (event) => {
                event.preventDefault();
                filterEmployees();
            });
        }

        if (employeeFilterForm && employeeSearchInput) {
            employeeSearchInput.addEventListener('input', () => {
                clearTimeout(employeeSearchTimer);
                employeeSearchTimer = setTimeout(() => {
                    filterEmployees();
                }, 600);
            });
        }

        if (employeeDivisionSelect) {
            employeeDivisionSelect.addEventListener('change', filterEmployees);
        }

        if (employeeSortSelect) {
            employeeSortSelect.addEventListener('change', filterEmployees);
        }

        if (employeeFilterReset) {
            employeeFilterReset.addEventListener('click', () => {
                employeeSearchInput.value = '';
                employeeDivisionSelect.value = '';
                employeeSortSelect.value = 'name_asc';
                filterEmployees();
            });
        }
    </script>
</x-app-layout>


