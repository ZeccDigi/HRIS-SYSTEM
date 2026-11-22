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
        <td class="px-2 py-2.5 text-sm font-semibold text-gray-900">
            <div class="truncate">{{ $employee->lastname }}, {{ $employee->firstname }} {{ $employee->middlename ?? '-' }}</div>
        </td>
        <td class="px-2 py-2.5 text-sm text-gray-500">
            @if($employee->rfid_number)
                <span class="inline-flex rounded-md bg-blue-100 px-1.5 py-0.5 text-[11px] font-medium text-blue-800">{{ $employee->rfid_number }}</span>
            @else
                <span class="text-[11px] italic text-gray-400">{{ __('No RFID') }}</span>
            @endif
        </td>
        <td class="px-2 py-2.5 text-sm text-gray-600">
            <div class="truncate">{{ $employee->division ?? '-' }}</div>
        </td>
        <td class="px-2 py-2.5 text-sm font-medium text-gray-700">
            <span class="block truncate">
                {{ $roleLabels[$accountRole] ?? 'Unassigned' }}
            </span>
        </td>
        <td class="px-2 py-2.5 text-sm text-gray-600">
            <span class="inline-flex max-w-full truncate rounded-full bg-slate-100 px-2 py-1 text-xs font-bold text-slate-700">{{ $employee->employment_status ?? '—' }}</span>
        </td>
        <td class="px-2 py-2.5 text-sm text-gray-600">
            {{ $employee->service_start_date?->format('Y') ?? $employee->created_at?->format('Y') ?? '—' }}
        </td>
        <td class="px-2 py-2.5 text-center text-sm font-medium">
            <div class="flex items-center justify-center gap-1">
                <a href="{{ route('employees.show', $employee) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-indigo-700 hover:bg-indigo-50 hover:text-indigo-900 transition-colors duration-200" title="{{ __('View') }}" aria-label="{{ __('View') }}">
                    <i class="fa-solid fa-eye text-sm" aria-hidden="true"></i>
                    <span class="sr-only">{{ __('View') }}</span>
                </a>
                <a href="{{ route('employees.edit', $employee) }}" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-blue-700 hover:bg-blue-50 hover:text-blue-900 transition-colors duration-200" title="{{ __('Edit') }}" aria-label="{{ __('Edit') }}">
                    <i class="fa-solid fa-pen text-sm" aria-hidden="true"></i>
                    <span class="sr-only">{{ __('Edit') }}</span>
                </a>
                <form action="{{ route('employees.destroy', $employee) }}" method="POST" class="inline-block">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="archiveAction = $el.closest('form').action; archiveEmployeeName = @js(trim(($employee->lastname ?? '').', '.($employee->firstname ?? '').' '.($employee->middlename ?? ''))); archiveConfirmOpen = true" class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-700 hover:bg-red-50 hover:text-red-900 transition-colors duration-200" title="{{ __('Archive') }}" aria-label="{{ __('Archive') }}">
                        <i class="fa-solid fa-box-archive text-sm" aria-hidden="true"></i>
                        <span class="sr-only">{{ __('Archive') }}</span>
                    </button>
                </form>
            </div>
        </td>
    </tr>
@empty
    <tr>
        <td colspan="7" class="px-6 py-10 text-center text-gray-500">
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

