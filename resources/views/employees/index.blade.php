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

    <div class="overflow-x-hidden py-12" x-data="{ tab: 'employees' }">
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
            @if(session('account_credentials'))
                @php($credentials = session('account_credentials'))
                <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                    <div class="font-black uppercase tracking-wider text-amber-800">{{ __('Temporary Account Details') }}</div>
                    <p class="mt-1 text-amber-700">{{ __('Give these details to the employee only if the email was not received. This message appears once after account creation.') }}</p>
                    <div class="mt-3 grid gap-2 sm:grid-cols-3">
                        <div>
                            <span class="block text-[10px] font-black uppercase tracking-widest text-amber-700">{{ __('HRIS Username') }}</span>
                            <span class="font-bold">{{ $credentials['hris_username'] ?? '' }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-black uppercase tracking-widest text-amber-700">{{ __('Recovery / OTP Email') }}</span>
                            <span class="font-bold">{{ $credentials['recovery_email'] ?? '' }}</span>
                        </div>
                        <div>
                            <span class="block text-[10px] font-black uppercase tracking-widest text-amber-700">{{ __('Temporary Password') }}</span>
                            <span class="font-mono font-black">{{ $credentials['temporary_password'] ?? '' }}</span>
                        </div>
                    </div>
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

                    <div class="w-full max-w-full min-w-0 overflow-x-auto">
                        <table class="min-w-full w-full table-fixed divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="w-[26%] px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Employees Name') }}</th>
                                    <th class="w-[15%] px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Position') }}</th>
                                    <th class="w-[15%] px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Employment Status') }}</th>
                                    <th class="w-[18%] px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Linked Account') }}</th>
                                    <th class="w-[14%] px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Archived Date') }}</th>
                                    <th class="w-[12%] px-4 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
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
                                        <td class="px-4 py-4 text-center text-sm font-medium">
                                            <div class="flex items-center justify-center gap-1.5">
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

        </div>
    </div>

    <script>
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


