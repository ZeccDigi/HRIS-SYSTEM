@forelse($employees as $employee)
    @php
        $roleLabels = [
            'employee' => 'Employee',
            'hrstaff' => 'HR Admin',
            'recordofficer' => 'Record Officer',
            'chief' => 'Chief',
            'regionaldirector' => 'Regional Director',
            'admin' => 'Admin',
        ];
        $accountRole = strtolower((string) ($employee->account_role ?? ''));
    @endphp
    <tr class="hover:bg-gray-50 transition-colors duration-200">
        <td class="px-3 py-4 text-sm font-medium text-gray-900">
            <div class="truncate">{{ $employee->lastname }}, {{ $employee->firstname }} {{ $employee->middlename ?? '-' }}</div>
        </td>
        <td class="px-3 py-4 text-sm text-gray-500">
            @if($employee->rfid_number)
                <span class="px-2 py-1 inline-flex text-xs font-medium rounded-md bg-blue-100 text-blue-800">{{ $employee->rfid_number }}</span>
            @else
                <span class="text-gray-400 italic text-xs">{{ __('No RFID') }}</span>
            @endif
        </td>
        <td class="px-3 py-4 text-sm text-gray-600">
            <div class="truncate">{{ $employee->division ?? '-' }}</div>
        </td>
        <td class="px-3 py-4 text-sm font-medium text-gray-700">
            <span class="block truncate">
                {{ $roleLabels[$accountRole] ?? 'Unassigned' }}
            </span>
        </td>
        <td class="px-3 py-4 text-center text-sm font-medium">
            <div class="flex items-center justify-center gap-1">
                <a href="{{ route('employees.show', $employee) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-indigo-700 hover:bg-indigo-50 hover:text-indigo-900 transition-colors duration-200" title="{{ __('View') }}" aria-label="{{ __('View') }}">
                    <i class="fa-solid fa-eye text-sm" aria-hidden="true"></i>
                    <span class="sr-only">{{ __('View') }}</span>
                </a>
                <a href="{{ route('employees.edit', $employee) }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-blue-700 hover:bg-blue-50 hover:text-blue-900 transition-colors duration-200" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
                    <i class="fa-solid fa-pen text-sm" aria-hidden="true"></i>
                    <span class="sr-only">{{ __('Edit') }}</span>
                </a>
                <form action="{{ route('employees.destroy', $employee) }}" method="POST" class="inline-block">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="archiveAction = $el.closest('form').action; archiveEmployeeName = @js(trim(($employee->lastname ?? '').', '.($employee->firstname ?? '').' '.($employee->middlename ?? ''))); archiveConfirmOpen = true" class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-red-700 hover:bg-red-50 hover:text-red-900 transition-colors duration-200" title="{{ __('Archive') }}" aria-label="{{ __('Archive') }}">
                        <i class="fa-solid fa-box-archive text-sm" aria-hidden="true"></i>
                        <span class="sr-only">{{ __('Archive') }}</span>
                    </button>
                </form>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="5" class="px-6 py-10 text-center text-gray-500">
            <div class="flex flex-col items-center">
                <svg class="w-12 h-12 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <p class="text-lg font-medium">{{ __('No employees found') }}</p>
                <p class="text-sm mt-1 text-gray-400">{{ __('Try adjusting the search or division filter.') }}</p>
            </div>
        </td>
    </tr>
@endforelse

