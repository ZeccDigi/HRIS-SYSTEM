<x-app-layout>
    <x-slot name="title">{{ __('Daily Attendance Monitoring') }}</x-slot>

    <div
        class="py-8"
        x-data="attendanceMonitor({
            search: @js($search),
            division: @js($divisionFilter),
            employmentStatus: @js($employmentStatusFilter),
            date: @js($selectedDate),
        })"
        x-init="init()"
    >
        <div class="mx-auto max-w-7xl space-y-5 sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="no-print rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm font-bold text-emerald-700 shadow-sm">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="no-print rounded-xl border border-red-200 bg-red-50 px-5 py-4 text-sm font-bold text-red-700 shadow-sm">
                    {{ session('error') }}
                </div>
            @endif

            <div
                x-data="{
                    rawScansModalOpen: false,
                    selectedEmployeeName: '',
                    selectedEmployeeNo: '',
                    selectedDepartment: '',
                    selectedPosition: '',
                    selectedEmploymentStatus: '',
                    selectedRfid: '',
                    selectedStatus: '',
                    selectedHoursWorked: '',
                    selectedLateMinutes: '',
                    selectedUndertimeMinutes: '',
                    selectedRemarks: '',
                    rawScans: []
                }"
            >
                <div class="no-print flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="max-w-xl">
                        <h1 class="text-2xl font-black tracking-tight text-slate-900">
                            {{ __('Daily Attendance Monitoring') }}
                        </h1>
                        <p class="mt-1 flex items-center gap-2 text-sm font-medium text-slate-500">
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <span>{{ \Carbon\Carbon::parse($selectedDate)->format('F j, Y') }}</span>
                        </p>
                    </div>

                    <div class="relative w-full max-w-sm lg:ml-auto lg:w-80" @click.outside="activePicker = false">
                            <button
                                id="attendance_date_picker"
                                type="button"
                                @click.stop="toggleDatePicker()"
                                class="flex h-11 w-full items-center justify-between rounded-xl border border-gray-100 bg-gray-50/50 px-4 text-left text-sm font-bold text-gray-900 shadow-sm transition focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                :class="activePicker ? 'border-indigo-500 ring-2 ring-indigo-500' : 'hover:border-indigo-200'"
                            >
                                <span x-text="formatDate(date)"></span>
                                <svg class="h-5 w-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </button>

                            <div x-show="activePicker" x-transition x-cloak @click.stop class="absolute right-0 z-50 mt-2 w-full rounded-2xl border border-gray-100 bg-white p-4 shadow-2xl">
                            <div class="mb-3 space-y-2">
                                <select
                                    :value="String(calendarYear)"
                                    @change.stop="calendarYear = Number($event.target.value); changeCalendarYear()"
                                    x-effect="$el.value = String(calendarYear)"
                                    autocomplete="off"
                                    class="h-8 w-full rounded-lg border-gray-100 bg-gray-50 px-3 text-xs font-black text-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                >
                                    <template x-for="year in calendarYears()" :key="year">
                                        <option :value="String(year)" x-text="year"></option>
                                    </template>
                                </select>
                                <div class="flex items-center gap-2">
                                    <button type="button" @click.stop="moveMonth(-1)" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-indigo-50 hover:text-indigo-700">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                                        </svg>
                                    </button>
                                    <div class="min-w-0 flex-1 truncate text-center text-xs font-black uppercase tracking-widest text-gray-700" x-text="monthName"></div>
                                    <button type="button" @click.stop="moveMonth(1)" class="flex h-8 w-8 items-center justify-center rounded-lg text-gray-500 hover:bg-indigo-50 hover:text-indigo-700">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-2 grid grid-cols-7 gap-1 text-center text-[10px] font-black uppercase tracking-widest text-gray-400">
                                <span>Sun</span>
                                <span>Mon</span>
                                <span>Tue</span>
                                <span>Wed</span>
                                <span>Thu</span>
                                <span>Fri</span>
                                <span>Sat</span>
                            </div>

                            <div class="grid grid-cols-7 gap-1">
                                <template x-for="day in calendarDays()" :key="day.key">
                                    <div>
                                        <template x-if="day.blank">
                                            <span class="block h-9"></span>
                                        </template>
                                        <template x-if="!day.blank">
                                            <button
                                                type="button"
                                                x-text="day.day"
                                                @click.stop="selectDate(day.value)"
                                                class="flex h-9 w-full items-center justify-center rounded-lg text-xs font-black transition"
                                                :class="day.selected ? 'bg-indigo-600 text-white shadow-md' : (day.today ? 'border border-indigo-200 bg-indigo-50 text-indigo-700' : 'text-gray-700 hover:bg-indigo-50 hover:text-indigo-700')"
                                            ></button>
                                        </template>
                                    </div>
                                </template>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="mt-6 grid grid-cols-2 gap-5 md:grid-cols-4">
                    <div class="flex flex-col justify-between rounded-2xl border border-slate-200/60 bg-white p-4 shadow-sm">
                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                            {{ __('Total Employees') }}
                        </span>
                        <span class="mt-2 text-2xl font-black text-slate-900">
                            {{ $stats['total'] }}
                        </span>
                    </div>

                    <div class="flex flex-col justify-between rounded-2xl border border-slate-200/60 bg-white p-4 shadow-sm">
                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                            {{ __('Present') }}
                        </span>
                        <span class="mt-2 text-2xl font-black text-emerald-600">
                            {{ $stats['present'] }}
                        </span>
                    </div>

                    <div class="flex flex-col justify-between rounded-2xl border border-slate-200/60 bg-white p-4 shadow-sm">
                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                            {{ __('Late') }}
                        </span>
                        <span class="mt-2 text-2xl font-black text-amber-500">
                            {{ $stats['late'] }}
                        </span>
                    </div>

                    <div class="flex flex-col justify-between rounded-2xl border border-slate-200/60 bg-white p-4 shadow-sm">
                        <span class="text-[9px] font-black uppercase tracking-wider text-slate-400">
                            {{ __('Completed') }}
                        </span>
                        <span class="mt-2 text-2xl font-black text-blue-600">
                            {{ $stats['completed'] }}
                        </span>
                    </div>
                </div>

                <div class="no-print mt-5 border-b border-slate-200 bg-slate-50/70 p-3">
                    <div class="flex min-w-0 flex-col gap-3 sm:flex-row sm:items-center">
                        <div class="relative min-w-0 sm:flex-1">
                            <label for="attendance_search" class="sr-only">{{ __('Search attendance records') }}</label>
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35m1.35-5.65a7 7 0 1 1-14 0 7 7 0 0 1 14 0Z" />
                                </svg>
                            </span>
                            <input
                                id="attendance_search"
                                type="search"
                                x-model="search"
                                @input.debounce.200ms="applyFilters()"
                                placeholder="{{ __('Search employee, department, position, or ID...') }}"
                                class="block h-9 w-full rounded-lg border-slate-300 bg-white pl-10 pr-3 text-sm shadow-sm focus:border-[#2b428f] focus:ring-[#2b428f]"
                            />
                        </div>

                        <select
                            x-model="division"
                            @change="applyFilters()"
                            class="block h-9 rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-[#2b428f] focus:ring-[#2b428f] sm:w-48"
                        >
                            <option value="all">{{ __('Departments') }}</option>
                            @foreach($divisions as $divisionOption)
                                <option value="{{ $divisionOption }}">{{ $divisionOption }}</option>
                            @endforeach
                        </select>

                        <select
                            x-model="employmentStatus"
                            @change="applyFilters()"
                            class="block h-9 rounded-lg border-slate-300 bg-white text-sm shadow-sm focus:border-[#2b428f] focus:ring-[#2b428f] sm:w-48"
                        >
                            <option value="all">{{ __('Status') }}</option>
                            @foreach($employmentStatuses as $employmentStatusOption)
                                <option value="{{ $employmentStatusOption }}">{{ $employmentStatusOption }}</option>
                            @endforeach
                        </select>

                        <button
                            type="button"
                            @click="reset()"
                            class="inline-flex h-9 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-xs font-bold uppercase tracking-wider text-slate-700 transition hover:bg-slate-100"
                        >
                            {{ __('Reset') }}
                        </button>
                    </div>
                </div>

                <div class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="overflow-x-auto">
                        <table class="w-full border-collapse text-left text-[11px] leading-tight">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-50 text-[9px] font-black uppercase tracking-wider text-slate-400">
                                    <th class="px-3 py-3">Employee</th>
                                    <th class="px-3 py-3">Department</th>
                                    <th class="px-3 py-3">Position</th>
                                    <th class="px-3 py-3">AM In</th>
                                    <th class="px-3 py-3">AM Out</th>
                                    <th class="px-3 py-3">PM In</th>
                                    <th class="px-3 py-3">PM Out</th>
                                    <th class="px-3 py-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                @forelse($attendanceRecords as $item)
                                    @php
                                        $employee = $item['employee'];
                                        $displayName = trim(($employee->firstname ?? '') . ' ' . ($employee->lastname ?? ''));
                                        $employeeNumber = $employee->employee_no ?? ('EMP-' . str_pad((string) $employee->id, 4, '0', STR_PAD_LEFT));
                                        $searchText = Str::lower(implode(' ', [
                                            $displayName,
                                            $employee->division ?? '',
                                            $employee->position ?? '',
                                            $employee->employment_status ?? '',
                                            $employeeNumber,
                                        ]));
                                        $scans = [];

                                        if ($item['time_in']) {
                                            $scans[] = [
                                                'time' => \Carbon\Carbon::parse($item['time_in'])->format('h:i A'),
                                                'type' => 'AM IN',
                                                'device' => 'RFID Reader #1',
                                                'status' => $item['late_minutes'] > 0 ? 'Late Arrival' : 'Valid',
                                            ];
                                        }

                                        if ($item['am_out']) {
                                            $scans[] = [
                                                'time' => \Carbon\Carbon::parse($item['am_out'])->format('h:i A'),
                                                'type' => 'AM OUT',
                                                'device' => 'RFID Reader #1',
                                                'status' => 'Valid',
                                            ];
                                        }

                                        if ($item['pm_in']) {
                                            $scans[] = [
                                                'time' => \Carbon\Carbon::parse($item['pm_in'])->format('h:i A'),
                                                'type' => 'PM IN',
                                                'device' => 'RFID Reader #1',
                                                'status' => 'Valid',
                                            ];
                                        }

                                        if ($item['time_out']) {
                                            $scans[] = [
                                                'time' => \Carbon\Carbon::parse($item['time_out'])->format('h:i A'),
                                                'type' => 'PM OUT',
                                                'device' => 'RFID Reader #1',
                                                'status' => 'Valid',
                                            ];
                                        }
                                    @endphp
                                    <tr
                                        class="transition hover:bg-slate-50/50"
                                        data-attendance-row
                                        data-search="{{ $searchText }}"
                                        data-division="{{ Str::lower($employee->division ?? 'n/a') }}"
                                        data-employment-status="{{ Str::lower($employee->employment_status ?? 'n/a') }}"
                                    >
                                        <td class="px-3 py-3 align-top">
                                            <div class="min-w-[12rem] max-w-[14rem] whitespace-normal break-words">
                                                <p class="text-[11px] font-bold leading-tight text-slate-900">{{ $displayName ?: 'N/A' }}</p>
                                                <p class="mt-0.5 text-[9px] font-semibold uppercase tracking-wider text-slate-400">{{ $employee->employment_status ?: 'N/A' }}</p>
                                            </div>
                                        </td>
                                        <td class="px-3 py-3 align-top text-[11px] font-semibold text-slate-600">
                                            <div class="max-w-[11rem] whitespace-normal break-words">{{ $employee->division ?: 'N/A' }}</div>
                                        </td>
                                        <td class="px-3 py-3 align-top text-[11px] font-semibold text-slate-600">
                                            <div class="max-w-[11rem] whitespace-normal break-words">{{ $employee->position ?: 'N/A' }}</div>
                                        </td>
                                        <td class="px-3 py-3 align-top text-[11px] font-bold text-slate-900 whitespace-normal break-words">{{ $item['time_in'] ? \Carbon\Carbon::parse($item['time_in'])->format('h:i A') : 'N/A' }}</td>
                                        <td class="px-3 py-3 align-top text-[11px] font-bold text-slate-900 whitespace-normal break-words">{{ $item['am_out'] ? \Carbon\Carbon::parse($item['am_out'])->format('h:i A') : 'N/A' }}</td>
                                        <td class="px-3 py-3 align-top text-[11px] font-bold text-slate-900 whitespace-normal break-words">{{ $item['pm_in'] ? \Carbon\Carbon::parse($item['pm_in'])->format('h:i A') : 'N/A' }}</td>
                                        <td class="px-3 py-3 align-top text-[11px] font-bold text-slate-900 whitespace-normal break-words">{{ $item['time_out'] ? \Carbon\Carbon::parse($item['time_out'])->format('h:i A') : 'N/A' }}</td>
                                        <td class="px-3 py-3 text-center align-top">
                                            <button
                                                type="button"
                                                data-scans="{{ json_encode($scans) }}"
                                                data-name="{{ $displayName }}"
                                                data-no="{{ $employeeNumber }}"
                                                data-division="{{ $employee->division ?: 'N/A' }}"
                                                data-position="{{ $employee->position ?: 'N/A' }}"
                                                data-status="{{ $employee->employment_status ?: 'N/A' }}"
                                                data-rfid="{{ $employee->rfid_number ?: 'N/A' }}"
                                                data-attendance-status="{{ $item['status'] }}"
                                                data-hours="{{ $item['hours_worked'] }} hrs"
                                                data-late="{{ $item['late_minutes'] > 0 ? $item['late_minutes'] . 'm' : 'N/A' }}"
                                                data-undertime="{{ $item['undertime_minutes'] > 0 ? $item['undertime_minutes'] . 'm' : 'N/A' }}"
                                                data-remarks="{{ $item['remarks'] ?: 'N/A' }}"
                                                @click="
                                                    rawScans = JSON.parse($el.dataset.scans);
                                                    selectedEmployeeName = $el.dataset.name;
                                                    selectedEmployeeNo = $el.dataset.no;
                                                    selectedDepartment = $el.dataset.division;
                                                    selectedPosition = $el.dataset.position;
                                                    selectedEmploymentStatus = $el.dataset.status;
                                                    selectedRfid = $el.dataset.rfid;
                                                    selectedStatus = $el.dataset.attendanceStatus;
                                                    selectedHoursWorked = $el.dataset.hours;
                                                    selectedLateMinutes = $el.dataset.late;
                                                    selectedUndertimeMinutes = $el.dataset.undertime;
                                                    selectedRemarks = $el.dataset.remarks;
                                                    rawScansModalOpen = true;
                                                "
                                                class="rounded-lg bg-slate-100 px-2.5 py-1 text-[9px] font-bold uppercase tracking-wider text-slate-700 transition hover:bg-slate-200"
                                            >
                                                {{ __('View') }}
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                                            <p class="text-sm font-semibold">{{ __('No attendance records found.') }}</p>
                                        </td>
                                    </tr>
                                @endforelse

                                @if($attendanceRecords->isNotEmpty())
                                    <tr x-show="noResults" style="display: none;">
                                        <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                                            <p class="text-sm font-semibold">{{ __('No attendance records match the selected filters.') }}</p>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <div x-show="rawScansModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
                    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="rawScansModalOpen = false"></div>
                    <div class="relative w-full max-w-xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl flex flex-col max-h-[50vh]">
                        <div class="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-6 py-4 shrink-0">
                            <div>
                                <h3 class="text-[9px] font-black uppercase tracking-wider text-slate-400">{{ __('Attendance Details') }}</h3>
                                <h2 class="text-lg font-bold text-slate-900" x-text="selectedEmployeeName"></h2>
                                <p class="text-[9px] font-semibold text-slate-400">Employee ID: <span x-text="selectedEmployeeNo"></span> | Date: {{ \Carbon\Carbon::parse($selectedDate)->format('M d, Y') }}</p>
                            </div>
                            <button type="button" @click="rawScansModalOpen = false" class="rounded-lg p-1 text-slate-400 hover:text-slate-600">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>

                        <div class="p-6 overflow-y-auto flex-1">
                            <div class="space-y-5">
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Department</p>
                                        <p class="mt-1 text-sm font-bold text-slate-900" x-text="selectedDepartment"></p>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Position</p>
                                        <p class="mt-1 text-sm font-bold text-slate-900" x-text="selectedPosition"></p>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Employment Status</p>
                                        <p class="mt-1 text-sm font-bold text-slate-900" x-text="selectedEmploymentStatus"></p>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">RFID Number</p>
                                        <p class="mt-1 text-sm font-bold text-slate-900" x-text="selectedRfid"></p>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Attendance Status</p>
                                        <p class="mt-1 text-sm font-bold text-slate-900" x-text="selectedStatus"></p>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Worked Hours</p>
                                        <p class="mt-1 text-sm font-bold text-slate-900" x-text="selectedHoursWorked"></p>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Late</p>
                                        <p class="mt-1 text-sm font-bold text-slate-900" x-text="selectedLateMinutes"></p>
                                    </div>
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">
                                        <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Undertime</p>
                                        <p class="mt-1 text-sm font-bold text-slate-900" x-text="selectedUndertimeMinutes"></p>
                                    </div>
                                </div>

                                <div class="rounded-xl border border-slate-200 bg-white px-4 py-3">
                                    <p class="text-[9px] font-black uppercase tracking-wider text-slate-400">Remarks</p>
                                    <p class="mt-1 text-sm font-medium text-slate-700" x-text="selectedRemarks"></p>
                                </div>

                                <template x-if="rawScans.length === 0">
                                    <div class="py-6 text-center text-slate-400">
                                        <p class="text-sm font-medium">No raw RFID scan history available for this date.</p>
                                    </div>
                                </template>

                                <template x-if="rawScans.length > 0">
                                    <div class="overflow-hidden rounded-xl border border-slate-200">
                                        <div class="grid grid-cols-4 border-b border-slate-200 bg-slate-50 px-4 py-2 text-[9px] font-black uppercase tracking-wider text-slate-400">
                                            <div>Time</div>
                                            <div>Type</div>
                                            <div>Device</div>
                                            <div class="text-right">Status</div>
                                        </div>

                                        <template x-for="scan in rawScans" :key="scan.time + scan.type">
                                            <div class="grid grid-cols-4 items-center border-b border-slate-100 px-4 py-3 text-xs last:border-b-0">
                                                <div class="font-bold text-slate-900" x-text="scan.time"></div>
                                                <div>
                                                    <span class="inline-flex items-center rounded px-2 py-0.5 text-center text-[9px] font-bold uppercase" :class="scan.type.includes('IN') ? 'border border-emerald-100 bg-emerald-50 text-emerald-700' : 'border border-blue-100 bg-blue-50 text-blue-700'" x-text="scan.type"></span>
                                                </div>
                                                <div class="text-slate-500" x-text="scan.device"></div>
                                                <div class="text-right font-semibold" :class="scan.status === 'Valid' ? 'text-emerald-600' : 'text-amber-600'" x-text="scan.status"></div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="flex items-center justify-end border-t border-slate-200 bg-slate-50 px-6 py-3.5 shrink-0">
                            <button
                                type="button"
                                @click="rawScansModalOpen = false"
                                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-bold uppercase tracking-wider text-slate-700 transition hover:bg-slate-100"
                            >
                                {{ __('Close') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function attendanceMonitor(config) {
            return {
                search: config.search || '',
                division: config.division || 'all',
                employmentStatus: config.employmentStatus || 'all',
                date: config.date,
                activePicker: false,
                calendarMonth: new Date(`${config.date}T00:00:00`),
                calendarYear: new Date(`${config.date}T00:00:00`).getFullYear(),
                todayDate: new Date().toISOString().slice(0, 10),
                monthNames: [
                    'January', 'February', 'March', 'April', 'May', 'June',
                    'July', 'August', 'September', 'October', 'November', 'December',
                ],
                rows: [],
                noResults: false,
                get monthName() {
                    return this.monthNames[this.calendarMonth.getMonth()];
                },

                init() {
                    this.rows = Array.from(document.querySelectorAll('[data-attendance-row]'));
                    this.applyFilters();
                },

                applyFilters() {
                    const search = this.search.trim().toLowerCase();
                    let visibleCount = 0;

                    this.rows.forEach((row) => {
                        const rowSearch = row.dataset.search || '';
                        const rowDivision = row.dataset.division || 'n/a';
                        const rowEmploymentStatus = row.dataset.employmentStatus || 'n/a';

                        const visible =
                            (!search || rowSearch.includes(search)) &&
                            (this.division === 'all' || rowDivision === this.division.toLowerCase()) &&
                            (this.employmentStatus === 'all' || rowEmploymentStatus === this.employmentStatus.toLowerCase());

                        row.classList.toggle('hidden', !visible);

                        if (visible) {
                            visibleCount++;
                        }
                    });

                    this.noResults = this.rows.length > 0 && visibleCount === 0;
                },

                toggleDatePicker() {
                    this.calendarMonth = new Date(`${this.date}T00:00:00`);
                    this.calendarYear = this.calendarMonth.getFullYear();
                    this.activePicker = ! this.activePicker;
                },

                moveMonth(offset) {
                    this.calendarMonth = new Date(this.calendarMonth.getFullYear(), this.calendarMonth.getMonth() + offset, 1);
                    this.calendarYear = this.calendarMonth.getFullYear();
                },

                changeCalendarYear() {
                    this.calendarMonth = new Date(Number(this.calendarYear), this.calendarMonth.getMonth(), 1);
                },

                calendarYears() {
                    const selectedYear = new Date(`${this.date}T00:00:00`).getFullYear();
                    const currentYear = new Date().getFullYear();
                    const minYear = Math.min(selectedYear, currentYear - 4);
                    const maxYear = Math.max(selectedYear, currentYear);
                    const years = [];

                    for (let year = maxYear; year >= minYear; year--) {
                        years.push(year);
                    }

                    return years;
                },

                calendarDays() {
                    const year = this.calendarMonth.getFullYear();
                    const month = this.calendarMonth.getMonth();
                    const firstDay = new Date(year, month, 1);
                    const lastDay = new Date(year, month + 1, 0);
                    const days = [];

                    for (let index = 0; index < firstDay.getDay(); index++) {
                        days.push({ key: `blank-${index}`, blank: true });
                    }

                    for (let day = 1; day <= lastDay.getDate(); day++) {
                        const value = this.dateValue(new Date(year, month, day));
                        days.push({
                            key: value,
                            blank: false,
                            day,
                            value,
                            selected: value === this.date,
                            today: value === this.todayDate,
                        });
                    }

                    return days;
                },

                selectDate(value) {
                    this.date = value;
                    this.activePicker = false;
                    this.goToDate(value);
                },

                goToDate(value) {
                    const url = new URL(window.location.href);
                    url.searchParams.set('date', value);
                    url.searchParams.set('search', this.search);
                    url.searchParams.set('division', this.division);
                    url.searchParams.set('employment_status', this.employmentStatus);
                    window.location.href = url.toString();
                },

                dateValue(date) {
                    return [
                        date.getFullYear(),
                        String(date.getMonth() + 1).padStart(2, '0'),
                        String(date.getDate()).padStart(2, '0'),
                    ].join('-');
                },

                formatDate(value) {
                    if (!value) {
                        return 'Select date';
                    }

                    return new Date(`${value}T00:00:00`).toLocaleDateString(undefined, {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                    });
                },


                reset() {
                    this.search = '';
                    this.division = 'all';
                    this.employmentStatus = 'all';
                    this.applyFilters();
                },
            };
        }
    </script>
</x-app-layout>
