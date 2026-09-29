<x-app-layout>
    <x-slot name="title">{{ __('Activity Audit') }}</x-slot>

    <div class="overflow-x-hidden py-12" x-data="{ 
        tab: 'activity', 
        pickerOpen: false,
        selectedYear: '{{ $selectedYear ?: date('Y') }}',
        currentYear: {{ $selectedYear ? (int)$selectedYear : ($selectedDate ? (int)date('Y', strtotime($selectedDate)) : (int)date('Y')) }},
        currentMonth: {{ $selectedDate ? (date('n', strtotime($selectedDate)) - 1) : (date('n') - 1) }},
        selectedDateStr: '{{ $selectedDate }}',
        monthNames: ['JANUARY', 'FEBRUARY', 'MARCH', 'APRIL', 'MAY', 'JUNE', 'JULY', 'AUGUST', 'SEPTEMBER', 'OCTOBER', 'NOVEMBER', 'DECEMBER'],
        
        get monthYearHeader() {
            return this.monthNames[this.currentMonth] + ' ' + this.currentYear;
        },
        
        get daysInMonth() {
            return new Date(this.currentYear, this.currentMonth + 1, 0).getDate();
        },
        
        get firstDayOfWeek() {
            return new Date(this.currentYear, this.currentMonth, 1).getDay();
        },
        
        prevMonth() {
            if (this.currentMonth === 0) {
                this.currentMonth = 11;
                this.currentYear--;
            } else {
                this.currentMonth--;
            }
        },
        
        nextMonth() {
            if (this.currentMonth === 11) {
                this.currentMonth = 0;
                this.currentYear++;
            } else {
                this.currentMonth++;
            }
        },
        
        selectDay(day) {
            let m = (this.currentMonth + 1).toString().padStart(2, '0');
            let d = day.toString().padStart(2, '0');
            this.selectedDateStr = `${this.currentYear}-${m}-${d}`;
            this.pickerOpen = false;
            
            this.$nextTick(() => {
                let form = document.querySelector('#activityFilterForm');
                if (form) {
                    let dateInput = form.querySelector('[name=date]');
                    if (dateInput) dateInput.value = this.selectedDateStr;
                    form.submit();
                }
            });
        },
        
        formatDisplayDate(str) {
            if (!str) return 'Select date';
            let parts = str.split('-');
            if (parts.length !== 3) return str;
            let d = new Date(parts[0], parts[1] - 1, parts[2]);
            return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        }
    }">
        <div class="mx-auto w-full max-w-7xl min-w-0 px-4 sm:px-6 lg:px-8">

            <!-- Header Tabs & Top Selectors in the Same Row -->
            <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
                <div class="max-w-full overflow-x-auto">
                    <div class="inline-flex rounded-xl bg-white border border-gray-100 shadow-sm p-1">
                        <a href="{{ route('audit-logs.login') }}"
                           class="px-4 py-2 text-sm font-semibold rounded-lg transition text-gray-500 hover:text-gray-700">
                            {{ __('Login Audit') }}
                        </a>
                        <button type="button"
                           class="px-4 py-2 text-sm font-semibold rounded-lg transition bg-indigo-100 text-indigo-700 font-bold">
                            {{ __('Activity Audit') }}
                        </button>
                    </div>
                </div>

                <!-- Right: Custom Calendar Selector & Year Selector in Top Row -->
                <div class="flex items-center gap-2.5 sm:shrink-0">
                    
                    <!-- Custom Calendar Selector Container -->
                    <div class="relative w-48 sm:w-52" @click.outside="pickerOpen = false">
                        <button 
                            type="button" 
                            @click="pickerOpen = !pickerOpen"
                            class="flex h-9 w-full items-center justify-between rounded-xl border bg-white px-3.5 py-2 text-left text-sm font-semibold text-gray-800 shadow-sm transition focus:outline-none"
                            :class="pickerOpen ? 'border-blue-600 ring-2 ring-blue-100' : 'border-gray-300 hover:border-gray-400'"
                        >
                            <span x-text="formatDisplayDate(selectedDateStr)" :class="selectedDateStr ? 'text-gray-900 font-bold' : 'text-gray-400'"></span>
                            <svg class="h-4 w-4 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3M5 11h14M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </button>

                        <!-- Calendar Popup Overlay (Guaranteed 350px Width, High Readability & Un-cramped Spacing) -->
                        <div x-show="pickerOpen" 
                             x-transition.opacity.duration.150ms 
                             x-cloak 
                             style="width: 350px !important; min-width: 350px !important; max-width: 350px !important; box-sizing: border-box !important;"
                             class="absolute right-0 top-full z-50 mt-2 rounded-3xl border border-gray-100 bg-white p-6 shadow-2xl">
                            
                            <!-- Month & Year Navigation (Arrows at Far Left & Far Right Corners) -->
                            <div class="mb-5 flex items-center justify-between w-full" style="width: 100% !important; display: flex !important; align-items: center !important; justify-content: space-between !important;">
                                <button type="button" 
                                        @click="prevMonth()" 
                                        class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-600 hover:bg-slate-100 transition focus:outline-none"
                                        aria-label="{{ __('Previous Month') }}">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                                    </svg>
                                </button>

                                <div class="text-sm font-extrabold uppercase tracking-wider text-slate-800 whitespace-nowrap" x-text="monthYearHeader"></div>

                                <button type="button" 
                                        @click="nextMonth()" 
                                        class="flex h-9 w-9 items-center justify-center rounded-xl text-slate-600 hover:bg-slate-100 transition focus:outline-none"
                                        aria-label="{{ __('Next Month') }}">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Day Headers (Spacious 7 Equal Columns, Clean Title Case) -->
                            <div style="display: grid !important; grid-template-columns: repeat(7, 1fr) !important; width: 100% !important; margin-bottom: 0.75rem;" class="text-center text-xs font-bold text-slate-400">
                                <div>Sun</div>
                                <div>Mon</div>
                                <div>Tue</div>
                                <div>Wed</div>
                                <div>Thu</div>
                                <div>Fri</div>
                                <div>Sat</div>
                            </div>

                            <!-- Days Grid (Spacious 38x38px tiles) -->
                            <div style="display: grid !important; grid-template-columns: repeat(7, 1fr) !important; width: 100% !important; gap: 4px !important;" class="text-center">
                                <template x-for="blank in firstDayOfWeek">
                                    <div style="height: 38px; width: 38px;" class="mx-auto"></div>
                                </template>
                                <template x-for="day in daysInMonth">
                                    <div class="flex items-center justify-center">
                                        <button 
                                            type="button" 
                                            @click="selectDay(day)"
                                            x-text="day"
                                            style="height: 38px; width: 38px;"
                                            class="flex items-center justify-center rounded-xl text-xs font-bold transition focus:outline-none"
                                            :class="selectedDateStr === `${currentYear}-${(currentMonth + 1).toString().padStart(2, '0')}-${day.toString().padStart(2, '0')}` 
                                                ? 'bg-blue-600 text-white shadow-md' 
                                                : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900'"
                                        ></button>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <!-- Year Selector -->
                    <div class="w-32">
                        <label for="act_top_year" class="sr-only">{{ __('Year') }}</label>
                        <select 
                            id="act_top_year" 
                            name="year" 
                            x-model="selectedYear"
                            @change="
                                if (!selectedYear) {
                                    let today = new Date();
                                    currentYear = today.getFullYear();
                                    currentMonth = today.getMonth();
                                    selectedDateStr = '';
                                } else {
                                    currentYear = parseInt(selectedYear);
                                }
                                let form = document.querySelector('#activityFilterForm');
                                if (form) {
                                    let yearInput = form.querySelector('[name=year]');
                                    if (yearInput) yearInput.value = selectedYear;
                                    let dateInput = form.querySelector('[name=date]');
                                    if (!selectedYear && dateInput) dateInput.value = '';
                                    form.submit();
                                }
                            "
                            class="block h-9 w-full rounded-xl border-gray-300 bg-white text-sm font-semibold text-gray-800 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            @foreach(range(date('Y'), 2024) as $y)
                                <option value="{{ $y }}" {{ (string)($selectedYear ?: date('Y')) === (string)$y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <!-- Main White Card Container -->
            <div class="w-full max-w-full min-w-0 overflow-hidden bg-white shadow-sm sm:rounded-lg border border-gray-100">
                <div class="min-w-0 p-6 text-gray-900">
                    
                    <!-- Search, Filter & Sort Bar -->
                    <form id="activityFilterForm" method="GET" action="{{ route('audit-logs.activity') }}" class="mb-4 flex min-w-0 flex-col gap-2 rounded-xl border border-gray-100 bg-gray-50/70 p-2 sm:flex-row sm:items-center">
                        <input type="hidden" name="date" value="{{ $selectedDate }}" />
                        <input type="hidden" name="year" value="{{ $selectedYear }}" />

                        <!-- Search Field -->
                        <div class="relative min-w-0 sm:flex-1">
                            <label for="search" class="sr-only">{{ __('Search activity') }}</label>
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M21 21l-4.35-4.35M10.75 18.5a7.75 7.75 0 100-15.5 7.75 7.75 0 000 15.5z" />
                                </svg>
                            </span>
                            <input id="search" name="search" type="search" value="{{ $search }}" placeholder="{{ __('Search activity, user, or action...') }}" onchange="this.form.submit()" onkeydown="if(event.key==='Enter') this.form.submit()" class="block h-9 w-full rounded-lg border-gray-300 pl-10 pr-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>

                        <!-- Module Filter Selector -->
                        <div class="sm:w-44 sm:shrink-0">
                            <label for="module" class="sr-only">{{ __('Module') }}</label>
                            <select id="module" name="module" onchange="this.form.submit()" class="block h-9 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('All Modules') }}</option>
                                @foreach($modules as $mod)
                                    <option value="{{ $mod }}" {{ (string)$module === (string)$mod ? 'selected' : '' }}>{{ $mod }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Role Filter Selector -->
                        <div class="sm:w-44 sm:shrink-0">
                            <label for="act_role" class="sr-only">{{ __('Role') }}</label>
                            <select id="act_role" name="role" onchange="this.form.submit()" class="block h-9 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('All Roles') }}</option>
                                @foreach($roleOptions as $roleKey => $roleLabel)
                                    <option value="{{ $roleKey }}" {{ (string)$role === (string)$roleKey ? 'selected' : '' }}>{{ $roleLabel }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Reset Button -->
                        <div class="flex items-center gap-2 sm:shrink-0">
                            <a href="{{ route('audit-logs.activity') }}" class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-xs font-bold uppercase tracking-wider text-gray-700 transition hover:bg-gray-50">
                                {{ __('Reset') }}
                            </a>
                        </div>
                    </form>

                    <!-- Table Container -->
                    <div class="w-full max-w-full min-w-0 overflow-x-auto overscroll-x-contain">
                        <table class="min-w-full w-full table-fixed divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="w-[20%] px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('User') }}</th>
                                    <th class="w-[18%] px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Action / Event') }}</th>
                                    <th class="w-[15%] px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Module') }}</th>
                                    <th class="w-[23%] px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Description') }}</th>
                                    <th class="w-[12%] px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Date') }}</th>
                                    <th class="w-[12%] px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Time') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($activityAudits as $act)
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-4 py-4 text-sm font-medium text-gray-900">
                                            <div class="truncate text-sm font-medium text-gray-900">
                                                {{ $act->user_name ?: __('System') }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-sm font-medium text-gray-900">
                                            <div class="truncate">{{ $act->action }}</div>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <span class="max-w-full truncate px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-slate-100 text-slate-800">
                                                {{ $act->module ?: 'General' }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-gray-500">
                                            <div class="truncate">{{ $act->description }}</div>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-gray-500">
                                            {{ $act->created_at ? $act->created_at->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-4 py-4 text-sm text-gray-500">
                                            {{ $act->created_at ? $act->created_at->format('h:i A') : 'N/A' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                            <div class="flex flex-col items-center">
                                                <svg class="w-12 h-12 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <p class="text-base font-medium text-gray-700">{{ __('No activity audit records found.') }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($activityAudits->hasPages())
                        <div class="mt-4">
                            {{ $activityAudits->links() }}
                        </div>
                    @endif

                </div>
            </div>

        </div>
    </div>
</x-app-layout>
