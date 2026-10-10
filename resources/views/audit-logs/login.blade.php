<x-app-layout>
    <x-slot name="title">{{ __('Audit Logs') }}</x-slot>

    <div class="overflow-x-hidden py-12" x-data="{ 
        tab: '{{ request()->routeIs('audit-logs.activity') ? 'activity' : 'login' }}', 
        selectedAudit: null, 
        detailModalOpen: false,
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
                let form = document.querySelector(this.tab === 'login' ? '#loginFilterForm' : '#activityFilterForm');
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

            <!-- Top Header Row (Tabs on Left, Custom Calendar Selector & Year Selector on Right) -->
            <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
                
                <!-- Left: Navigation Tabs -->
                <div class="max-w-full overflow-x-auto">
                    <div class="inline-flex rounded-xl bg-white border border-gray-100 shadow-sm p-1">
                        <button type="button"
                                @click="tab = 'login'"
                                class="px-4 py-2 text-sm font-semibold rounded-lg transition"
                                :class="tab === 'login' ? 'bg-indigo-100 text-indigo-700 font-bold' : 'text-gray-500 hover:text-gray-700'">
                            {{ __('Login Audit') }}
                        </button>
                        <button type="button"
                                @click="tab = 'activity'"
                                class="px-4 py-2 text-sm font-semibold rounded-lg transition"
                                :class="tab === 'activity' ? 'bg-indigo-100 text-indigo-700 font-bold' : 'text-gray-500 hover:text-gray-700'">
                            {{ __('Activity Audit') }}
                        </button>
                    </div>
                </div>

                <!-- Right: Custom Calendar Selector & Reactive Year Selector -->
                <div class="flex items-center gap-2.5 sm:shrink-0">
                    
                    <!-- Custom Calendar Selector -->
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

                        <!-- Calendar Popup Overlay -->
                        <div x-show="pickerOpen" 
                             x-transition.opacity.duration.150ms 
                             x-cloak 
                             style="width: 350px !important; min-width: 350px !important; max-width: 350px !important; box-sizing: border-box !important;"
                             class="absolute right-0 top-full z-50 mt-2 rounded-3xl border border-gray-100 bg-white p-6 shadow-2xl">
                            
                            <!-- Month & Year Navigation -->
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

                            <!-- Day Headers -->
                            <div style="display: grid !important; grid-template-columns: repeat(7, 1fr) !important; width: 100% !important; margin-bottom: 0.75rem;" class="text-center text-xs font-bold text-slate-400">
                                <div>Sun</div>
                                <div>Mon</div>
                                <div>Tue</div>
                                <div>Wed</div>
                                <div>Thu</div>
                                <div>Fri</div>
                                <div>Sat</div>
                            </div>

                            <!-- Days Grid -->
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
                        <label for="top_year" class="sr-only">{{ __('Year') }}</label>
                        <select 
                            id="top_year" 
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
                                let form = document.querySelector(tab === 'login' ? '#loginFilterForm' : '#activityFilterForm');
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

            <!-- TAB 1: LOGIN AUDIT PANEL -->
            <div class="w-full max-w-full min-w-0 overflow-hidden bg-white shadow-sm sm:rounded-lg border border-gray-100" x-show="tab === 'login'">
                <div class="min-w-0 p-6 text-gray-900">
                    
                    <!-- Search, Filter & Sort Bar -->
                    <form id="loginFilterForm" method="GET" action="{{ route('audit-logs.login') }}" class="mb-4 flex min-w-0 flex-col gap-2 rounded-xl border border-gray-100 bg-gray-50/70 p-2 sm:flex-row sm:items-center">
                        <input type="hidden" name="date" value="{{ $selectedDate }}" />
                        <input type="hidden" name="year" value="{{ $selectedYear }}" />

                        <!-- Search Field -->
                        <div class="relative min-w-0 sm:flex-1">
                            <label for="search" class="sr-only">{{ __('Search user') }}</label>
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M21 21l-4.35-4.35M10.75 18.5a7.75 7.75 0 100-15.5 7.75 7.75 0 000 15.5z" />
                                </svg>
                            </span>
                            <input id="search" name="search" type="search" value="{{ $search }}" placeholder="{{ __('Search user or email...') }}" onchange="this.form.submit()" onkeydown="if(event.key==='Enter') this.form.submit()" class="block h-9 w-full rounded-lg border-gray-300 pl-10 pr-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>

                        <!-- Role Filter Selector -->
                        <div class="sm:w-44 sm:shrink-0">
                            <label for="role" class="sr-only">{{ __('Role') }}</label>
                            <select id="role" name="role" onchange="this.form.submit()" class="block h-9 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('All Roles') }}</option>
                                @foreach($roleOptions as $roleKey => $roleLabel)
                                    <option value="{{ $roleKey }}" {{ (string)$role === (string)$roleKey ? 'selected' : '' }}>{{ $roleLabel }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Status Filter Selector -->
                        <div class="sm:w-40 sm:shrink-0">
                            <label for="status" class="sr-only">{{ __('Status') }}</label>
                            <select id="status" name="status" onchange="this.form.submit()" class="block h-9 w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">{{ __('Status') }}</option>
                                <option value="successful" {{ $status === 'successful' ? 'selected' : '' }}>{{ __('Successful') }}</option>
                                <option value="failed" {{ $status === 'failed' ? 'selected' : '' }}>{{ __('Failed') }}</option>
                            </select>
                        </div>

                        <!-- Reset Button -->
                        <div class="flex items-center gap-2 sm:shrink-0">
                            <a href="{{ route('audit-logs.login') }}" class="inline-flex h-9 items-center justify-center rounded-lg border border-gray-300 bg-white px-4 text-xs font-bold uppercase tracking-wider text-gray-700 transition hover:bg-gray-50">
                                {{ __('Reset') }}
                            </a>
                        </div>
                    </form>

                    <!-- Table Container -->
                    <div class="w-full max-w-full min-w-0 overflow-x-auto overscroll-x-contain">
                        <table class="min-w-full w-full table-fixed divide-y divide-gray-200">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="w-[26%] px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('User') }}</th>
                                    <th class="w-[12%] px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Role') }}</th>
                                    <th class="w-[15%] px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Date') }}</th>
                                    <th class="w-[12%] px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Time') }}</th>
                                    <th class="w-[15%] px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Time Spent') }}</th>
                                    <th class="w-[10%] px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Status') }}</th>
                                    <th class="w-[10%] px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($audits as $audit)
                                    <tr class="hover:bg-gray-50 transition-colors duration-200">
                                        <td class="px-4 py-4">
                                            <div class="truncate text-sm font-medium text-gray-900">
                                                {{ $audit->user_name ?: __('Unknown Account') }}
                                            </div>
                                            <div class="truncate text-xs text-gray-400">
                                                {{ $audit->user_email }}
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            <span class="max-w-full truncate px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800">
                                                {{ strtoupper($audit->user_role ?: 'EMPLOYEE') }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-gray-500">
                                            {{ $audit->login_at ? $audit->login_at->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-4 py-4 text-sm text-gray-500">
                                            {{ $audit->login_at ? $audit->login_at->format('h:i A') : 'N/A' }}
                                        </td>
                                        <td class="px-4 py-4 text-xs font-semibold text-slate-700">
                                            @if($audit->status === 'successful')
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 font-medium">
                                                    {{ $audit->duration }}
                                                </span>
                                            @else
                                                <span class="text-gray-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-center">
                                            @if($audit->status === 'successful')
                                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-emerald-100 text-emerald-800">
                                                    {{ __('Successful') }}
                                                </span>
                                            @else
                                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                    {{ __('Failed') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-4 text-right text-sm font-medium">
                                            <div class="flex items-center justify-end">
                                                <button
                                                    type="button"
                                                    @click="selectedAudit = @js($audit); detailModalOpen = true"
                                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-emerald-700 hover:bg-emerald-50 hover:text-blue-900 transition-colors duration-200"
                                                    title="{{ __('View details') }}"
                                                    aria-label="{{ __('View details') }}"
                                                >
                                                    <i class="fa-solid fa-eye text-base"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                            <div class="flex flex-col items-center">
                                                <svg class="w-12 h-12 text-gray-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <p class="text-base font-medium text-gray-700">{{ __('No login audit records found.') }}</p>
                                                <p class="text-xs text-gray-400 mt-1">{{ __('Log into an employee account to generate real authentication audit records.') }}</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if($audits->hasPages())
                        <div class="mt-4">
                            {{ $audits->links() }}
                        </div>
                    @endif

                </div>
            </div>

            <!-- TAB 2: ACTIVITY AUDIT PANEL -->
            <div class="w-full max-w-full min-w-0 overflow-hidden bg-white shadow-sm sm:rounded-lg border border-gray-100" x-show="tab === 'activity'" x-cloak>
                <div class="min-w-0 p-6 text-gray-900">
                    
                    <!-- Search, Filter & Sort Bar -->
                    <form id="activityFilterForm" method="GET" action="{{ route('audit-logs.activity') }}" class="mb-4 flex min-w-0 flex-col gap-2 rounded-xl border border-gray-100 bg-gray-50/70 p-2 sm:flex-row sm:items-center">
                        <input type="hidden" name="date" value="{{ $selectedDate }}" />
                        <input type="hidden" name="year" value="{{ $selectedYear }}" />

                        <!-- Search Field -->
                        <div class="relative min-w-0 sm:flex-1">
                            <label for="act_search" class="sr-only">{{ __('Search activity') }}</label>
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.4" d="M21 21l-4.35-4.35M10.75 18.5a7.75 7.75 0 100-15.5 7.75 7.75 0 000 15.5z" />
                                </svg>
                            </span>
                            <input id="act_search" name="search" type="search" value="{{ $search }}" placeholder="{{ __('Search activity, user, or action...') }}" onchange="this.form.submit()" onkeydown="if(event.key==='Enter') this.form.submit()" class="block h-9 w-full rounded-lg border-gray-300 pl-10 pr-3 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
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

        <!-- Details Overlay Modal -->
        <div x-show="detailModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="display: none;">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-xs transition-opacity" @click="detailModalOpen = false"></div>

            <div class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white p-6 shadow-2xl border border-gray-100 flex flex-col z-10" @click.away="detailModalOpen = false">
                <div class="flex items-center justify-between border-b border-gray-100 pb-4 mb-5">
                    <div>
                        <h3 class="text-base font-bold text-gray-900">{{ __('Login Event Details') }}</h3>
                        <p class="text-xs text-gray-400 mt-0.5" x-text="selectedAudit ? selectedAudit.user_email : ''"></p>
                    </div>
                    <button type="button" @click="detailModalOpen = false" class="rounded-lg p-1 text-gray-400 hover:text-gray-600 transition">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <template x-if="selectedAudit">
                    <div class="space-y-3.5 text-xs text-gray-700 overflow-y-auto max-h-[70vh] pr-1">
                        
                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 border border-gray-100">
                            <span class="font-bold text-gray-500 uppercase tracking-wider text-[10px]">{{ __('Date & Time') }}</span>
                            <span class="font-semibold text-gray-800" x-text="selectedAudit.login_at ? new Date(selectedAudit.login_at).toLocaleString() : 'N/A'"></span>
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 border border-gray-100">
                            <span class="font-bold text-gray-500 uppercase tracking-wider text-[10px]">{{ __('User Account') }}</span>
                            <span class="font-semibold text-gray-800" x-text="selectedAudit.user_name"></span>
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 border border-gray-100">
                            <span class="font-bold text-gray-500 uppercase tracking-wider text-[10px]">{{ __('Role') }}</span>
                            <span class="font-semibold text-gray-800" x-text="selectedAudit.user_role"></span>
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 border border-gray-100">
                            <span class="font-bold text-gray-500 uppercase tracking-wider text-[10px]">{{ __('Status') }}</span>
                            <span class="font-bold uppercase tracking-wider px-2.5 py-0.5 rounded-md"
                                  :class="selectedAudit.status === 'successful' ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'"
                                  x-text="selectedAudit.status"></span>
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-indigo-50/50 border border-indigo-100/80" x-show="selectedAudit.status === 'successful'">
                            <span class="font-bold text-indigo-700 uppercase tracking-wider text-[10px]">{{ __('Time Spent (Duration)') }}</span>
                            <span class="font-bold text-indigo-900" x-text="selectedAudit.duration || 'N/A'"></span>
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 border border-gray-100">
                            <span class="font-bold text-gray-500 uppercase tracking-wider text-[10px]">{{ __('IP Address') }}</span>
                            <span class="font-mono font-bold text-gray-800" x-text="selectedAudit.ip_address || '127.0.0.1'"></span>
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 border border-gray-100">
                            <span class="font-bold text-gray-500 uppercase tracking-wider text-[10px]">{{ __('Device') }}</span>
                            <span class="font-semibold text-gray-800" x-text="selectedAudit.device || 'N/A'"></span>
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 border border-gray-100">
                            <span class="font-bold text-gray-500 uppercase tracking-wider text-[10px]">{{ __('Browser') }}</span>
                            <span class="font-semibold text-gray-800" x-text="selectedAudit.browser || 'N/A'"></span>
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 border border-gray-100">
                            <span class="font-bold text-gray-500 uppercase tracking-wider text-[10px]">{{ __('Operating System') }}</span>
                            <span class="font-semibold text-gray-800" x-text="selectedAudit.os || 'N/A'"></span>
                        </div>

                        <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 border border-gray-100">
                            <span class="font-bold text-gray-500 uppercase tracking-wider text-[10px]">{{ __('Login Method') }}</span>
                            <span class="font-semibold text-gray-800" x-text="selectedAudit.login_method || 'Password'"></span>
                        </div>

                        <template x-if="selectedAudit.status === 'failed'">
                            <div class="flex flex-col gap-1 p-3 rounded-xl bg-red-50 border border-red-100">
                                <span class="font-bold text-red-500 uppercase tracking-wider text-[10px]">{{ __('Failure Reason') }}</span>
                                <span class="font-semibold text-red-800" x-text="selectedAudit.failure_reason || 'Invalid password or credentials'"></span>
                            </div>
                        </template>
                    </div>
                </template>

                <div class="pt-4 mt-4 border-t border-gray-100 flex justify-end">
                    <button
                        type="button"
                        @click="detailModalOpen = false"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold text-xs rounded-xl transition"
                    >
                        {{ __('Close') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
