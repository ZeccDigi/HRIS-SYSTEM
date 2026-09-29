<x-app-layout>
    <x-slot name="title">{{ __('Employee Accounts') }}</x-slot>

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

    <div class="py-12" x-data="{ tab: 'pending' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded-r-lg flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded-r-lg flex items-center gap-2">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2v6m-9-3a9 9 0 1118 0 9 9 0 01-18 0z"></path>
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            <div class="mb-6">
                <div class="inline-flex rounded-xl bg-white border border-gray-100 shadow-sm p-1">
                    <button type="button"
                            class="px-4 py-2 text-sm font-semibold rounded-lg transition"
                            :class="tab === 'pending' ? 'bg-amber-100 text-amber-700' : 'text-gray-500 hover:text-gray-700'"
                            @click="tab = 'pending'">
                        Pending Accounts ({{ $pendingAccounts->count() }})
                    </button>
                    <button type="button"
                            class="px-4 py-2 text-sm font-semibold rounded-lg transition"
                            :class="tab === 'approved' ? 'bg-green-100 text-green-700' : 'text-gray-500 hover:text-gray-700'"
                            @click="tab = 'approved'">
                        Active Accounts ({{ $approvedAccounts->count() }})
                    </button>
                </div>
            </div>

            {{-- Pending Accounts Section --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100 mb-8" x-show="tab === 'pending'" x-cloak>
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

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Employee Name') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('HRIS Username') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Division') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Position') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Account Role') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Registered At') }}</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($pendingAccounts as $user)
                                    <tr class="hover:bg-amber-50/50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <x-profile-avatar :user="$user" size="sm" variant="amber" />
                                                <div>
                                                    <p class="text-sm font-semibold text-gray-900">
                                                        {{ $user->employee?->lastname ?? '-' }}, {{ $user->employee?->firstname ?? '-' }} {{ $user->employee?->middlename ?? '' }}
                                                        @if($user->employee?->suffix)
                                                            <span class="text-gray-400">{{ $user->employee->suffix }}</span>
                                                        @endif
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $user->email }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @if($user->employee?->division)
                                                <span class="text-xs text-gray-600">{{ $user->employee->division }}</span>
                                            @else
                                                <span class="text-gray-400 italic text-xs">{{ __('Not set') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full bg-gray-100 text-gray-700 uppercase">
                                                {{ $user->employee?->position ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800">
                                                {{ $formatRole($user->employee?->account_role) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500">
                                            {{ $user->created_at->format('M d, Y h:i A') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <div class="flex items-center justify-center gap-2">
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
                                                <form action="{{ route('employee-accounts.reject', $user) }}" method="POST" class="inline">
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
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-10 text-center text-gray-500">
                                            <div class="flex flex-col items-center">
                                                <svg class="w-12 h-12 text-gray-200 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <p class="text-sm font-medium text-gray-400">{{ __('No pending accounts') }}</p>
                                                <p class="text-xs text-gray-300 mt-1">{{ __('All registered accounts have been verified.') }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Approved Accounts Section --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg border border-gray-100" x-show="tab === 'approved'" x-cloak>
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

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Employee Name') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('HRIS Username') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Division') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Position') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Account Role') }}</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($approvedAccounts as $user)
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center gap-3">
                                                <x-profile-avatar :user="$user" size="sm" variant="green" />
                                                <a href="{{ route('employee-accounts.show', $user) }}" class="text-sm font-semibold text-blue-600 hover:text-blue-800 hover:underline">
                                                    {{ $user->employee?->lastname ?? '-' }}, {{ $user->employee?->firstname ?? '-' }} {{ $user->employee?->middlename ?? '' }}
                                                </a>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">{{ $user->email }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600">{{ $user->employee?->division ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800 uppercase">
                                                {{ $user->employee?->position ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full bg-gray-100 text-gray-700">
                                                {{ $formatRole($user->employee?->account_role) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-full bg-green-100 text-green-700">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                </svg>
                                                {{ __('Approved') }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-10 text-center text-gray-400 text-sm">
                                            {{ __('No approved accounts yet.') }}
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
    </script>
</x-app-layout>
