<x-app-layout>
    <x-slot name="title">{{ __('Reports') }}</x-slot>

    <style>
        .reports-main-filters {
            display: grid;
            grid-template-columns: minmax(13rem, 1.05fr) minmax(13rem, 1.15fr) minmax(10rem, 0.7fr) minmax(10rem, 0.7fr);
            gap: 0.75rem;
            align-items: end;
        }

        .reports-main-filters > * {
            min-width: 0;
        }

        .reports-detail-filters {
            display: grid;
            grid-template-columns: minmax(22rem, 1.45fr) minmax(13rem, 0.75fr) minmax(16rem, 0.8fr);
            gap: 0.75rem;
            align-items: center;
        }

        .report-chart-compact {
            display: grid;
            grid-template-columns: minmax(7rem, 0.8fr) minmax(0, 1fr);
            gap: 0.75rem;
            align-items: center;
        }

        .chart-count-cards {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.5rem;
        }

        .gender-count-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(4.5rem, 1fr));
            gap: 0.5rem;
        }

        @media (max-width: 1023px) {
            .reports-main-filters,
            .reports-detail-filters {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 767px) {
            .report-chart-compact {
                grid-template-columns: 1fr;
            }

            .chart-count-cards,
            .gender-count-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="min-h-screen bg-slate-50 py-4" x-data="reportsDashboard()" x-init="initCharts()">
        <div class="mx-auto w-full px-0 sm:px-1 lg:px-2">
            <div class="mb-4">
                <h1 class="text-2xl font-black tracking-tight text-slate-900">{{ __('Report Summary') }}</h1>
            </div>

            <div class="rounded-xl border border-blue-100 bg-white p-4 shadow-sm">
                <div class="reports-main-filters">
                    <label>
                        <span class="text-[11px] font-black uppercase tracking-wider text-slate-600">{{ __('Report Type') }}</span>
                        <select x-model="selectedType" @change="changeReportType()" class="mt-2 h-11 w-full rounded-lg border-blue-100 bg-white text-sm font-semibold text-slate-700 focus:border-[#0038a8] focus:ring-[#0038a8]">
                            <template x-for="type in reportTypes" :key="type.value">
                                <option :value="type.value" x-text="type.label"></option>
                            </template>
                        </select>
                    </label>

                    <label x-show="visibleFilters().includes('division')" x-cloak>
                        <span class="text-[11px] font-black uppercase tracking-wider text-slate-600">{{ __('Division') }}</span>
                        <select x-model="filters.division" @change="applyFilters()" class="mt-2 h-11 w-full rounded-lg border-blue-100 bg-white text-sm font-semibold text-slate-700 focus:border-[#0038a8] focus:ring-[#0038a8]">
                            <option>All Divisions</option>
                            <template x-for="division in divisions" :key="division">
                                <option :value="division" x-text="division"></option>
                            </template>
                        </select>
                    </label>

                    <label x-show="visibleFilters().includes('dateRange')" x-cloak>
                        <span class="text-[11px] font-black uppercase tracking-wider text-slate-600">{{ __('From') }}</span>
                        <input type="date" x-model="filters.from" @change="applyFilters()" class="mt-2 h-11 w-full rounded-lg border-blue-100 bg-white text-sm font-semibold text-slate-700 focus:border-[#0038a8] focus:ring-[#0038a8]">
                    </label>

                    <label x-show="visibleFilters().includes('dateRange')" x-cloak>
                        <span class="text-[11px] font-black uppercase tracking-wider text-slate-600">{{ __('To') }}</span>
                        <input type="date" x-model="filters.to" @change="applyFilters()" class="mt-2 h-11 w-full rounded-lg border-blue-100 bg-white text-sm font-semibold text-slate-700 focus:border-[#0038a8] focus:ring-[#0038a8]">
                    </label>
                </div>
            </div>

            <div class="mt-5 rounded-xl border border-blue-100 bg-white shadow-sm">
                <div class="flex flex-col gap-4 border-b border-blue-50 px-5 py-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex items-start gap-3">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-blue-50 text-[#0038a8]">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="active.icon"></path>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-black text-slate-900" x-text="active.title"></h2>
                            <p class="mt-1 text-xs font-semibold text-slate-500">
                                <span>From </span>
                                <span x-text="filters.from"></span>
                                <span> to </span>
                                <span x-text="filters.to"></span>
                                <span class="px-2 text-slate-300">|</span>
                                <span x-text="filters.division"></span>
                            </p>
                        </div>
                    </div>
                    <div class="text-left text-xs font-semibold text-slate-500 lg:text-right">
                        <p>Generated on: <span x-text="generatedAt"></span></p>
                        
                    </div>
                </div>

                <div class="p-4">
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        <template x-for="metric in active.metrics" :key="metric.label">
                            <div class="rounded-lg border border-blue-100 bg-white p-3 shadow-sm">
                                <div class="flex items-center gap-2.5">
                                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full" :class="metric.badge">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="metric.icon"></path>
                                        </svg>
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-slate-500" x-text="metric.label"></p>
                                        <p class="text-xl font-black" :class="metric.text" x-text="metric.value"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-3">
                        <div class="rounded-lg border border-blue-100 bg-white p-4 shadow-sm">
                            <div class="mb-3 flex items-center justify-between">
                                <h3 class="text-sm font-black text-slate-900" x-text="visibleCharts[0]?.title"></h3>
                                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Breakdown</span>
                            </div>
                            <div class="relative h-52">
                                <canvas x-ref="chartOne" x-show="hasChartData(visibleCharts[0])"></canvas>
                                <div x-show="!hasChartData(visibleCharts[0])" x-cloak class="absolute inset-0 flex items-center justify-center rounded-lg bg-slate-50 text-center text-xs font-bold text-slate-500">
                                    No chart data for this selection.
                                </div>
                            </div>
                        </div>
                        <div class="rounded-lg border border-blue-100 bg-white p-4 shadow-sm">
                            <div class="mb-3 flex items-center justify-between">
                                <h3 class="text-sm font-black text-slate-900" x-text="visibleCharts[1]?.title"></h3>
                                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Summary</span>
                            </div>
                            <div :class="visibleCharts[1]?.type === 'doughnut' ? 'report-chart-compact' : 'h-52'">
                                <div :class="visibleCharts[1]?.type === 'doughnut' ? 'relative mx-auto h-28 w-28' : 'relative h-full'">
                                    <canvas x-ref="chartTwo" x-show="hasChartData(visibleCharts[1])"></canvas>
                                    <div x-show="!hasChartData(visibleCharts[1])" x-cloak class="absolute inset-0 flex items-center justify-center rounded-lg bg-slate-50 text-center text-xs font-bold text-slate-500">
                                        No chart data.
                                    </div>
                                </div>
                                <div x-show="visibleCharts[1]?.type === 'doughnut'" x-cloak class="min-w-0 space-y-2 text-[11px] font-bold text-slate-500">
                                    <template x-for="item in (visibleCharts[1]?.items || [])" :key="item.label">
                                        <span class="flex min-w-0 items-center gap-1.5" :title="item.fullLabel || item.label">
                                            <span class="h-2 w-2 rounded-full" :style="`background-color: ${item.color}`"></span>
                                            <span class="truncate" x-text="item.shortLabel || item.label"></span>
                                        </span>
                                    </template>
                                </div>
                            </div>
                            <div x-show="visibleCharts[1]?.type === 'doughnut'" x-cloak class="gender-count-cards mt-3">
                                <template x-for="item in (visibleCharts[1]?.items || [])" :key="`count-${item.label}`">
                                    <div class="min-w-0 rounded-lg border border-blue-100 bg-slate-50 px-2.5 py-2">
                                        <p class="truncate text-[10px] font-black uppercase tracking-wider text-slate-500" x-text="item.shortLabel || item.label"></p>
                                        <p class="text-lg font-black text-slate-900" x-text="item.value"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <div class="rounded-lg border border-blue-100 bg-white p-4 shadow-sm">
                            <div class="mb-3 flex items-center justify-between">
                                <h3 class="text-sm font-black text-slate-900" x-text="visibleCharts[2]?.title"></h3>
                                <span class="text-[10px] font-black uppercase tracking-wider text-slate-400">Summary</span>
                            </div>
                            <div :class="visibleCharts[2]?.type === 'doughnut' ? 'report-chart-compact' : 'h-52'">
                                <div :class="visibleCharts[2]?.type === 'doughnut' ? 'relative mx-auto h-28 w-28 shrink-0' : 'relative h-full'">
                                    <canvas x-ref="chartThree" x-show="hasChartData(visibleCharts[2])"></canvas>
                                    <div x-show="!hasChartData(visibleCharts[2])" x-cloak class="absolute inset-0 flex items-center justify-center rounded-lg bg-slate-50 text-center text-xs font-bold text-slate-500">
                                        No chart data.
                                    </div>
                                </div>
                                <div x-show="visibleCharts[2]?.type === 'doughnut'" x-cloak class="min-w-0 space-y-2 text-[11px] font-bold text-slate-500">
                                    <template x-for="item in (visibleCharts[2]?.items || [])" :key="`${item.label}-legend`">
                                        <span class="flex min-w-0 items-center gap-1.5" :title="item.fullLabel || item.label">
                                            <span class="h-2 w-2 rounded-full" :style="`background-color: ${item.color}`"></span>
                                            <span class="truncate" x-text="item.shortLabel || item.label"></span>
                                        </span>
                                    </template>
                                </div>
                            </div>
                            <div x-show="visibleCharts[2]?.type === 'doughnut'" x-cloak class="chart-count-cards mt-3">
                                <template x-for="item in (visibleCharts[2]?.items || [])" :key="item.label">
                                    <div class="min-w-0 rounded-lg border border-blue-100 bg-slate-50 px-2.5 py-2">
                                        <p class="truncate text-[10px] font-black uppercase tracking-wider text-slate-500" x-text="item.shortLabel || item.label"></p>
                                        <p class="text-lg font-black text-slate-900" x-text="item.value"></p>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 rounded-lg border border-blue-100 bg-white shadow-sm">
                        <div class="flex flex-col gap-3 border-b border-blue-50 px-4 py-4 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <h3 class="text-lg font-black text-slate-900">{{ __('Detailed Report') }}</h3>
                                <p class="mt-1 text-xs font-semibold text-slate-500">Showing <span x-text="filteredRows().length"></span> of <span x-text="active.recordCount"></span> records</p>
                                <div x-show="generatedType === 'overtime_cto'" x-cloak class="mt-3 inline-flex rounded-lg border border-blue-100 bg-blue-50 p-1">
                                    <button type="button" @click="ctoTab = 'overtime'; renderCharts()" :class="ctoTab === 'overtime' ? 'bg-[#0038a8] text-white shadow-sm' : 'text-slate-600 hover:bg-white'" class="rounded-md px-4 py-2 text-[11px] font-black uppercase tracking-wider transition">Overtime</button>
                                    <button type="button" @click="ctoTab = 'cto'; renderCharts()" :class="ctoTab === 'cto' ? 'bg-[#0038a8] text-white shadow-sm' : 'text-slate-600 hover:bg-white'" class="rounded-md px-4 py-2 text-[11px] font-black uppercase tracking-wider transition">CTO</button>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-2">
                                <button type="button" onclick="window.print()" style="background-color: #0038a8; border-color: #002f8f; color: #ffffff;" class="inline-flex h-10 min-w-[8.5rem] items-center justify-center rounded-lg border px-4 text-xs font-black uppercase tracking-wider shadow-sm hover:opacity-90">{{ __('Export PDF') }}</button>
                                <button type="button" @click="exportExcel()" style="background-color: #059669; border-color: #047857; color: #ffffff;" class="inline-flex h-10 min-w-[8.5rem] items-center justify-center rounded-lg border px-4 text-xs font-black uppercase tracking-wider shadow-sm hover:opacity-90">{{ __('Export Excel') }}</button>
                                <button type="button" onclick="window.print()" style="background-color: #ffffff; border-color: #dbeafe; color: #475569;" class="inline-flex h-10 min-w-[8.5rem] items-center justify-center rounded-lg border px-4 text-xs font-black uppercase tracking-wider shadow-sm hover:bg-blue-50">{{ __('Print') }}</button>
                            </div>
                        </div>

                        <div class="reports-detail-filters px-4 py-4">
                            <input type="search" x-model="search" placeholder="Search by name, ID, or division..." class="h-10 w-full rounded-lg border-blue-100 text-sm font-semibold text-slate-700 placeholder:text-slate-400 focus:border-[#0038a8] focus:ring-[#0038a8]">
                            <select x-model="filters.division" class="h-10 w-full rounded-lg border-blue-100 bg-white text-sm font-semibold text-slate-700 focus:border-[#0038a8] focus:ring-[#0038a8]">
                                <option>All Divisions</option>
                                <template x-for="division in divisions" :key="`detail-${division}`">
                                    <option :value="division" x-text="division"></option>
                                </template>
                            </select>
                            <select x-model="filters.employmentStatus" class="h-10 w-full rounded-lg border-blue-100 bg-white text-sm font-semibold text-slate-700 focus:border-[#0038a8] focus:ring-[#0038a8]">
                                <option>All Employment Status</option>
                                <template x-for="status in employmentStatuses" :key="status">
                                    <option :value="status" x-text="status"></option>
                                </template>
                            </select>
                        </div>

                        <div class="overflow-x-auto px-4 pb-4">
                            <table class="w-full min-w-[980px] overflow-hidden rounded-lg text-left text-sm">
                                <thead class="bg-blue-50 text-[11px] font-black uppercase tracking-wider text-slate-600">
                                    <tr>
                                        <template x-for="column in active.columns" :key="column">
                                            <th class="px-4 py-3" x-text="column"></th>
                                        </template>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-blue-50">
                                    <template x-for="row in filteredRows()" :key="row.key">
                                        <tr class="hover:bg-blue-50/40">
                                            <template x-for="(cell, index) in displayCells(row)" :key="`${row.key}-${index}`">
                                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-slate-700">
                                                    <span x-show="['Active', 'Approved', 'Present'].includes(cell)" class="rounded-full bg-emerald-50 px-3 py-1 text-xs font-black text-emerald-700" x-text="cell"></span>
                                                    <span x-show="['Pending', 'Late'].includes(cell)" class="rounded-full bg-amber-50 px-3 py-1 text-xs font-black text-amber-700" x-text="cell"></span>
                                                    <span x-show="['Inactive', 'Rejected', 'Absent'].includes(cell)" class="rounded-full bg-red-50 px-3 py-1 text-xs font-black text-red-700" x-text="cell"></span>
                                                    <span x-show="!['Active', 'Approved', 'Present', 'Pending', 'Late', 'Inactive', 'Rejected', 'Absent'].includes(cell)" x-text="cell"></span>
                                                </td>
                                            </template>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex flex-col gap-3 border-t border-blue-50 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex gap-1">
                                <button type="button" class="h-8 w-8 rounded-lg bg-[#0038a8] text-xs font-black text-white">1</button>
                                <button type="button" class="h-8 w-8 rounded-lg border border-blue-100 bg-white text-xs font-black text-slate-500">2</button>
                                <button type="button" class="h-8 w-8 rounded-lg border border-blue-100 bg-white text-xs font-black text-slate-500">3</button>
                            </div>
                            <select class="h-9 rounded-lg border-blue-100 text-xs font-bold text-slate-600">
                                <option>10 / page</option>
                                <option>25 / page</option>
                                <option>50 / page</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function reportsDashboard() {
            const today = new Date().toISOString().slice(0, 10);
            const firstDay = new Date(new Date().getFullYear(), new Date().getMonth(), 1).toISOString().slice(0, 10);

            const reportTypes = [
                { value: 'employee_report', label: 'Employee Report', filters: ['dateRange', 'division'] },
                { value: 'attendance_report', label: 'Attendance Report', filters: ['dateRange', 'division'] },
                { value: 'leave_report', label: 'Leave Report', filters: ['dateRange', 'division'] },
                { value: 'leave_balance', label: 'Leave Balance', filters: ['dateRange', 'division'] },
                { value: 'locator_slip', label: 'Locator Slip Report', filters: ['dateRange', 'division'] },
                { value: 'overtime_cto', label: 'Overtime & CTO', filters: ['dateRange', 'division'] },
            ];

            const employmentStatus = {
                total: 128,
                items: [
                    { label: 'Regular', value: 87, percent: 68, color: '#0038a8' },
                    { label: 'Job Order', value: 24, percent: 19, color: '#f59e0b' },
                    { label: 'Contract of Service', value: 17, percent: 13, color: '#10b981' },
                ],
            };

            const icon = 'M9 17v-6m4 6V7m4 10v-4';
            const checkIcon = 'M5 13l4 4L19 7';
            const clockIcon = 'M12 8v4l3 3';
            const xIcon = 'M6 18L18 6M6 6l12 12';
            const userIcon = 'M17 20h5v-2a4 4 0 00-4-4h-1M9 20H4v-2a4 4 0 014-4h1m4-4a4 4 0 11-8 0 4 4 0 018 0zm8 0a4 4 0 11-8 0 4 4 0 018 0z';
            const calendarIcon = 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z';

            const data = {
                employee_report: {
                    title: 'Employee Report',
                    icon: userIcon,
                    gender: { total: 128, male: 54, female: 74, malePercent: 42 },
                    metrics: [
                        { label: 'Total Employees', value: '128', badge: 'bg-blue-50 text-blue-700', text: 'text-[#0038a8]', icon: userIcon },
                        { label: 'Active Accounts', value: '119', badge: 'bg-emerald-50 text-emerald-700', text: 'text-emerald-700', icon: checkIcon },
                        { label: 'Divisions', value: '5', badge: 'bg-blue-50 text-blue-700', text: 'text-[#0038a8]', icon: icon },
                        { label: 'Regular', value: '87', badge: 'bg-blue-50 text-blue-700', text: 'text-[#0038a8]', icon: icon },
                    ],
                    chartTwoTitle: 'Employees by Division',
                    bars: [
                        { label: 'WRSD', value: 42, percent: 88 },
                        { label: 'Admin', value: 31, percent: 66 },
                        { label: 'Finance', value: 22, percent: 47 },
                        { label: 'Operations', value: 28, percent: 60 },
                        { label: 'Legal', value: 12, percent: 26 },
                    ],
                    recordCount: 128,
                    columns: ['Employee ID', 'Employee Name', 'Division', 'Position', 'Employment Status'],
                    rows: [
                        ['DMW-00123', 'Juan Dela Cruz', 'WRSD', 'IT Officer I', 'Regular'],
                        ['DMW-00456', 'Maria Santos', 'Admin', 'Admin Officer II', 'Regular'],
                        ['DMW-00789', 'Ramon Reyes', 'Finance', 'Accountant I', 'Regular'],
                        ['DMW-01012', 'Ana Perez', 'Operations', 'HR Assistant', 'Contract of Service'],
                        ['DMW-01345', 'Luis Garcia', 'Legal', 'Legal Officer I', 'Regular'],
                    ],
                },
                attendance_report: {
                    title: 'Attendance Report',
                    icon: clockIcon,
                    gender: { total: 128, male: 54, female: 74, malePercent: 42 },
                    metrics: [
                        { label: 'Total Employees', value: '128', badge: 'bg-blue-50 text-blue-700', text: 'text-[#0038a8]', icon: userIcon },
                        { label: 'Present', value: '2,340', badge: 'bg-emerald-50 text-emerald-700', text: 'text-emerald-700', icon: checkIcon },
                        { label: 'Absent', value: '31', badge: 'bg-red-50 text-red-700', text: 'text-red-600', icon: xIcon },
                        { label: 'Late', value: '84', badge: 'bg-amber-50 text-amber-700', text: 'text-amber-600', icon: clockIcon },
                        { label: 'Undertime', value: '12', badge: 'bg-blue-50 text-blue-700', text: 'text-[#0038a8]', icon: icon },
                    ],
                    chartTwoTitle: 'Attendance by Division',
                    bars: [
                        { label: 'WRSD', value: 42, percent: 82 },
                        { label: 'Admin', value: 31, percent: 67 },
                        { label: 'Finance', value: 22, percent: 52 },
                        { label: 'Operations', value: 28, percent: 64 },
                        { label: 'Legal', value: 12, percent: 30 },
                    ],
                    recordCount: 128,
                    columns: ['Employee', 'Division', 'Working Days', 'Present', 'Absent', 'Late', 'Undertime', 'Total Hours'],
                    rows: [
                        ['Juan Dela Cruz', 'WRSD', '21', '20', '0', '1', '0', '168.0'],
                        ['Maria Santos', 'Admin', '21', '19', '0', '2', '1', '160.5'],
                        ['Ramon Reyes', 'Finance', '21', '21', '0', '0', '0', '176.0'],
                        ['Ana Perez', 'Operations', '21', '18', '1', '2', '1', '151.0'],
                        ['Luis Garcia', 'Legal', '21', '20', '0', '1', '0', '167.0'],
                    ],
                },
                leave_report: {
                    title: 'Leave Report',
                    icon: calendarIcon,
                    gender: { total: 128, male: 54, female: 74, malePercent: 42 },
                    metrics: [
                        { label: 'Filed', value: '46', badge: 'bg-blue-50 text-blue-700', text: 'text-[#0038a8]', icon: calendarIcon },
                        { label: 'Approved', value: '33', badge: 'bg-emerald-50 text-emerald-700', text: 'text-emerald-700', icon: checkIcon },
                        { label: 'Pending', value: '9', badge: 'bg-amber-50 text-amber-700', text: 'text-amber-600', icon: clockIcon },
                        { label: 'Rejected', value: '4', badge: 'bg-red-50 text-red-700', text: 'text-red-600', icon: xIcon },
                        { label: 'Leave Days', value: '112', badge: 'bg-blue-50 text-blue-700', text: 'text-[#0038a8]', icon: icon },
                    ],
                    chartTwoTitle: 'Leave by Type',
                    bars: [
                        { label: 'Vacation', value: 18, percent: 78 },
                        { label: 'Sick', value: 14, percent: 61 },
                        { label: 'Special', value: 8, percent: 35 },
                        { label: 'Forced', value: 6, percent: 26 },
                    ],
                    recordCount: 46,
                    columns: ['Employee', 'Division', 'Leave Type', 'Date From', 'Date To', 'Days', 'Status', 'Approved By'],
                    rows: [
                        ['Juan Dela Cruz', 'WRSD', 'Vacation Leave', 'Sep 2, 2026', 'Sep 3, 2026', '2', 'Approved', 'HR Admin'],
                        ['Maria Santos', 'Admin', 'Sick Leave', 'Sep 5, 2026', 'Sep 5, 2026', '1', 'Approved', 'HR Admin'],
                        ['Ramon Reyes', 'Finance', 'Vacation Leave', 'Sep 12, 2026', 'Sep 12, 2026', '1', 'Pending', 'N/A'],
                        ['Ana Perez', 'Operations', 'Sick Leave', 'Sep 15, 2026', 'Sep 16, 2026', '2', 'Rejected', 'HR Admin'],
                        ['Luis Garcia', 'Legal', 'Special Leave', 'Sep 20, 2026', 'Sep 20, 2026', '1', 'Approved', 'HR Admin'],
                    ],
                },
                leave_balance: {
                    title: 'Leave Credits and Entitlements',
                    icon: calendarIcon,
                    gender: { total: 128, male: 54, female: 74, malePercent: 42 },
                    metrics: [
                        { label: 'Employees', value: '128', badge: 'bg-blue-50 text-blue-700', text: 'text-[#0038a8]', icon: userIcon },
                        { label: 'Avg Vacation', value: '8.5', badge: 'bg-blue-50 text-blue-700', text: 'text-[#0038a8]', icon: icon },
                        { label: 'Avg Sick', value: '10.2', badge: 'bg-emerald-50 text-emerald-700', text: 'text-emerald-700', icon: checkIcon },
                        { label: 'Low Credits', value: '7', badge: 'bg-amber-50 text-amber-700', text: 'text-amber-600', icon: clockIcon },
                        { label: 'Updated', value: 'Today', badge: 'bg-blue-50 text-blue-700', text: 'text-[#0038a8]', icon: calendarIcon },
                    ],
                    chartTwoTitle: 'Leave Credits and Entitlements by Division',
                    bars: [
                        { label: 'WRSD', value: 210, percent: 78 },
                        { label: 'Admin', value: 185, percent: 68 },
                        { label: 'Finance', value: 132, percent: 49 },
                        { label: 'Operations', value: 166, percent: 61 },
                        { label: 'Legal', value: 80, percent: 30 },
                    ],
                    recordCount: 128,
                    columns: ['Employee', 'Vacation Leave Balance', 'Sick Leave Balance', 'Other Annual/Statutory Entitlements'],
                    rows: [
                        ['Juan Dela Cruz', '12.5', '15.0', '3.0'],
                        ['Maria Santos', '8.0', '11.5', '2.0'],
                        ['Ramon Reyes', '10.0', '9.0', '5.0'],
                        ['Ana Perez', '4.5', '7.0', '1.0'],
                        ['Luis Garcia', '14.0', '16.5', '4.0'],
                    ],
                },
                locator_slip: {
                    title: 'Locator Slip Report',
                    icon: 'M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z',
                    gender: { total: 128, male: 54, female: 74, malePercent: 42 },
                    metrics: [
                        { label: 'Filed', value: '58', badge: 'bg-blue-50 text-blue-700', text: 'text-[#0038a8]', icon: icon },
                        { label: 'Official Business', value: '34', badge: 'bg-blue-50 text-blue-700', text: 'text-[#0038a8]', icon: checkIcon },
                        { label: 'Pass Slip', value: '24', badge: 'bg-amber-50 text-amber-700', text: 'text-amber-600', icon: clockIcon },
                        { label: 'Approved', value: '49', badge: 'bg-emerald-50 text-emerald-700', text: 'text-emerald-700', icon: checkIcon },
                        { label: 'Pending', value: '9', badge: 'bg-amber-50 text-amber-700', text: 'text-amber-600', icon: clockIcon },
                    ],
                    chartTwoTitle: 'Locator Slips by Division',
                    bars: [
                        { label: 'WRSD', value: 18, percent: 80 },
                        { label: 'Admin', value: 12, percent: 54 },
                        { label: 'Finance', value: 8, percent: 36 },
                        { label: 'Operations', value: 14, percent: 63 },
                        { label: 'Legal', value: 6, percent: 27 },
                    ],
                    recordCount: 58,
                    columns: ['Employee', 'Division', 'Type', 'Date', 'Purpose', 'Destination', 'Time Out', 'Time In', 'Status'],
                    rows: [
                        ['Juan Dela Cruz', 'WRSD', 'Official Business', 'Sep 2, 2026', 'Meeting', 'DMW Central', '09:00 AM', '01:00 PM', 'Approved'],
                        ['Maria Santos', 'Admin', 'Pass Slip', 'Sep 4, 2026', 'Personal errand', 'Bank', '02:00 PM', '03:30 PM', 'Approved'],
                        ['Ramon Reyes', 'Finance', 'Official Business', 'Sep 9, 2026', 'Document filing', 'COA', '10:00 AM', '12:00 PM', 'Pending'],
                        ['Ana Perez', 'Operations', 'Official Business', 'Sep 12, 2026', 'Coordination', 'POEA Office', '08:30 AM', '11:30 AM', 'Approved'],
                        ['Luis Garcia', 'Legal', 'Pass Slip', 'Sep 18, 2026', 'Medical', 'Clinic', '01:00 PM', '04:00 PM', 'Approved'],
                    ],
                },
                overtime: {
                    title: 'Overtime Summary',
                    icon: clockIcon,
                    gender: { total: 128, male: 54, female: 74, malePercent: 42 },
                    metrics: [
                        { label: 'Requests', value: '27', badge: 'bg-blue-50 text-blue-700', text: 'text-[#0038a8]', icon: icon },
                        { label: 'Approved', value: '21', badge: 'bg-emerald-50 text-emerald-700', text: 'text-emerald-700', icon: checkIcon },
                        { label: 'Pending', value: '5', badge: 'bg-amber-50 text-amber-700', text: 'text-amber-600', icon: clockIcon },
                        { label: 'Rejected', value: '1', badge: 'bg-red-50 text-red-700', text: 'text-red-600', icon: xIcon },
                        { label: 'Total Hours', value: '96.5', badge: 'bg-blue-50 text-blue-700', text: 'text-[#0038a8]', icon: clockIcon },
                    ],
                    chartTwoTitle: 'Overtime Hours by Division',
                    bars: [
                        { label: 'WRSD', value: 28, percent: 78 },
                        { label: 'Admin', value: 18, percent: 50 },
                        { label: 'Finance', value: 16, percent: 44 },
                        { label: 'Operations', value: 24, percent: 67 },
                        { label: 'Legal', value: 10, percent: 28 },
                    ],
                    recordCount: 27,
                    columns: ['Employee', 'Division', 'Date', 'Purpose', 'Start Time', 'End Time', 'Total Hours', 'Status'],
                    rows: [
                        ['Juan Dela Cruz', 'WRSD', 'Sep 3, 2026', 'System maintenance', '05:00 PM', '08:00 PM', '3.0', 'Approved'],
                        ['Maria Santos', 'Admin', 'Sep 7, 2026', 'Records encoding', '05:00 PM', '07:30 PM', '2.5', 'Approved'],
                        ['Ramon Reyes', 'Finance', 'Sep 10, 2026', 'Month-end closing', '05:00 PM', '09:00 PM', '4.0', 'Pending'],
                        ['Ana Perez', 'Operations', 'Sep 15, 2026', 'Deployment support', '06:00 PM', '09:00 PM', '3.0', 'Approved'],
                        ['Luis Garcia', 'Legal', 'Sep 20, 2026', 'Case review', '05:00 PM', '07:00 PM', '2.0', 'Approved'],
                    ],
                },
                cto: {
                    title: 'CTO Summary',
                    icon: clockIcon,
                    gender: { total: 128, male: 54, female: 74, malePercent: 42 },
                    metrics: [
                        { label: 'Employees', value: '31', badge: 'bg-blue-50 text-blue-700', text: 'text-[#0038a8]', icon: userIcon },
                        { label: 'CTO Earned', value: '96.5', badge: 'bg-emerald-50 text-emerald-700', text: 'text-emerald-700', icon: checkIcon },
                        { label: 'CTO Used', value: '42.0', badge: 'bg-amber-50 text-amber-700', text: 'text-amber-600', icon: clockIcon },
                        { label: 'Remaining', value: '54.5', badge: 'bg-blue-50 text-blue-700', text: 'text-[#0038a8]', icon: icon },
                        { label: 'Low Balance', value: '3', badge: 'bg-red-50 text-red-700', text: 'text-red-600', icon: xIcon },
                    ],
                    chartTwoTitle: 'CTO Balance by Division',
                    bars: [
                        { label: 'WRSD', value: 16, percent: 78 },
                        { label: 'Admin', value: 12, percent: 58 },
                        { label: 'Finance', value: 8, percent: 39 },
                        { label: 'Operations', value: 14, percent: 68 },
                        { label: 'Legal', value: 5, percent: 24 },
                    ],
                    recordCount: 31,
                    columns: ['Employee', 'CTO Earned', 'CTO Used', 'Remaining Balance'],
                    rows: [
                        ['Juan Dela Cruz', '12.0', '4.0', '8.0'],
                        ['Maria Santos', '9.5', '3.5', '6.0'],
                        ['Ramon Reyes', '7.0', '2.0', '5.0'],
                        ['Ana Perez', '15.0', '8.0', '7.0'],
                        ['Luis Garcia', '6.0', '1.0', '5.0'],
                    ],
                },
            };

            const serverReportData = @json($reportData);

            return {
                selectedType: serverReportData.selectedType,
                generatedType: serverReportData.selectedType,
                ctoTab: 'overtime',
                reportTypes: serverReportData.reportTypes,
                divisions: serverReportData.divisions,
                employmentStatuses: serverReportData.employmentStatuses,
                search: '',
                filters: serverReportData.filters,
                charts: {
                    chartOne: null,
                    chartTwo: null,
                    chartThree: null,
                },
                generatedAt: new Date().toLocaleString([], { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }),
                get active() {
                    const report = this.generatedType === 'overtime_cto'
                        ? serverReportData.reports[this.ctoTab]
                        : (serverReportData.reports[this.generatedType] || serverReportData.reports.employee_report);

                    return {
                        ...report,
                        employment: report.employment || employmentStatus,
                    };
                },
                get visibleCharts() {
                    return this.filteredCharts();
                },
                visibleFilters() {
                    return this.reportTypes.find((type) => type.value === this.selectedType)?.filters || [];
                },
                changeReportType() {
                    this.generatedType = this.selectedType;
                    this.ctoTab = 'overtime';
                    this.search = '';
                    this.applyFilters();
                },
                applyFilters() {
                    const params = new URLSearchParams({
                        type: this.selectedType,
                        from: this.filters.from,
                        to: this.filters.to,
                        division: this.filters.division,
                        employment_status: this.filters.employmentStatus,
                    });

                    window.history.replaceState({}, '', `{{ route('reports.index') }}?${params.toString()}`);
                    this.generatedAt = new Date().toLocaleString([], { year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
                    this.renderCharts();
                },
                initCharts() {
                    this.$watch('filters.division', () => this.renderCharts());
                    this.$watch('filters.employmentStatus', () => this.renderCharts());
                    this.$nextTick(() => this.renderCharts());
                },
                renderCharts() {
                    this.$nextTick(() => {
                        if (!window.Chart) {
                            return;
                        }

                        this.renderReportChart('chartOne', this.$refs.chartOne, this.visibleCharts?.[0]);
                        this.renderReportChart('chartTwo', this.$refs.chartTwo, this.visibleCharts?.[1]);
                        this.renderReportChart('chartThree', this.$refs.chartThree, this.visibleCharts?.[2]);
                    });
                },
                chartRows() {
                    return this.active.rows.filter((row) => {
                        const division = this.filters.division;
                        const employmentStatus = this.filters.employmentStatus;
                        const matchesDivision = division === 'All Divisions' || row.division === '' || row.division === division;
                        const matchesEmployment = employmentStatus === 'All Employment Status' || row.employmentStatus === '' || row.employmentStatus === employmentStatus;

                        return matchesDivision && matchesEmployment;
                    });
                },
                filteredCharts() {
                    const rows = this.chartRows();
                    const fallback = this.active.charts || [];

                    if (this.generatedType === 'employee_report') {
                        return [
                            this.makeChart(fallback[0]?.title || 'Employees by Division', 'bar', this.countRows(rows, 2)),
                            this.makeChart(fallback[1]?.title || 'Gender Distribution', 'doughnut', this.countRowValues(rows, (row) => this.displayValue(row.sex), [
                                { label: 'Male', color: '#0038a8' },
                                { label: 'Female', color: '#ec4899' },
                            ])),
                            this.makeChart(fallback[2]?.title || 'Employment Status', 'doughnut', this.countRowValues(rows, (row) => this.displayValue(row.employmentStatus), [
                                { label: 'Regular', shortLabel: 'REG.', color: '#0038a8' },
                                { label: 'Job Order', shortLabel: 'J.O.', color: '#f59e0b' },
                                { label: 'Contract of Service', shortLabel: 'C.O.S.', color: '#10b981' },
                            ])),
                        ];
                    }

                    if (this.generatedType === 'attendance_report') {
                        return [
                            fallback[0],
                            this.makeChart(fallback[1]?.title || 'Attendance Status', 'doughnut', [
                                { label: 'Present', value: this.sumCell(rows, 3), color: '#10b981' },
                                { label: 'Absent', value: this.sumCell(rows, 4), color: '#ef4444' },
                                { label: 'Late', value: this.sumCell(rows, 5), color: '#f59e0b' },
                                { label: 'Undertime', value: this.sumCell(rows, 6), color: '#3b82f6' },
                            ]),
                            this.makeChart(fallback[2]?.title || 'Attendance by Division', 'bar', this.countRows(rows, 1)),
                        ];
                    }

                    if (this.generatedType === 'leave_report') {
                        return [
                            this.makeChart(fallback[0]?.title || 'Leave Applications by Type', 'bar', this.countRows(rows, 2)),
                            this.makeChart(fallback[1]?.title || 'Leave Status', 'doughnut', this.countRowValues(rows, (row) => this.displayValue(row.cells[6]), [
                                { label: 'Approved', color: '#10b981' },
                                { label: 'Pending', color: '#f59e0b' },
                                { label: 'Rejected', color: '#ef4444' },
                            ])),
                            this.makeChart(fallback[2]?.title || 'Leave Applications by Division', 'bar', this.countRows(rows, 1)),
                        ];
                    }

                    if (this.generatedType === 'locator_slip') {
                        return [
                            this.makeChart(fallback[0]?.title || 'Locator Slips by Type', 'doughnut', this.countRows(rows, 2)),
                            this.makeChart(fallback[1]?.title || 'Locator Slips by Division', 'bar', this.countRows(rows, 1)),
                            this.makeChart(fallback[2]?.title || 'Daily Usage', 'line', this.countRows(rows, 3)),
                        ];
                    }

                    if (this.generatedType === 'overtime_cto' && this.ctoTab === 'overtime') {
                        return [
                            this.makeChart(fallback[0]?.title || 'OT by Division', 'bar', this.sumRows(rows, 1, 6)),
                            this.makeChart(fallback[1]?.title || 'OT Status', 'doughnut', this.countRowValues(rows, (row) => this.displayValue(row.cells[7]), [
                                { label: 'Approved', color: '#10b981' },
                                { label: 'Pending', color: '#f59e0b' },
                                { label: 'Rejected', color: '#ef4444' },
                            ])),
                            this.makeChart(fallback[2]?.title || 'Hours Rendered', 'line', this.sumRows(rows, 2, 6)),
                        ];
                    }

                    if (this.generatedType === 'overtime_cto' && this.ctoTab === 'cto') {
                        return [
                            this.makeChart(fallback[0]?.title || 'CTO by Division', 'bar', this.countRowValues(rows, (row) => this.displayValue(row.division))),
                            this.makeChart(fallback[1]?.title || 'CTO Earned vs Used', 'doughnut', [
                                { label: 'Earned', value: this.sumCell(rows, 1), color: '#10b981' },
                                { label: 'Used', value: this.sumCell(rows, 2), color: '#f59e0b' },
                            ]),
                            this.makeChart(fallback[2]?.title || 'Credits Remaining', 'bar', rows.map((row) => ({
                                label: row.cells[0],
                                fullLabel: row.cells[0],
                                shortLabel: this.abbreviate(row.cells[0]),
                                value: Number(row.cells[3] || 0),
                            }))),
                        ];
                    }

                    return fallback;
                },
                makeChart(title, type, items) {
                    const colors = ['#0038a8', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];

                    return {
                        title,
                        type,
                        items: (items || []).map((item, index) => ({
                            label: item.label || 'N/A',
                            fullLabel: item.fullLabel || item.label || 'N/A',
                            shortLabel: item.shortLabel || this.abbreviate(item.label || 'N/A'),
                            value: Number(item.value || 0),
                            color: item.color || colors[index % colors.length],
                        })),
                    };
                },
                countRows(rows, cellIndex) {
                    return this.countRowValues(rows, (row) => this.displayValue(row.cells[cellIndex]));
                },
                countRowValues(rows, valueCallback, seededItems = []) {
                    const counts = new Map();

                    rows.forEach((row) => {
                        const label = this.displayValue(valueCallback(row));
                        counts.set(label, (counts.get(label) || 0) + 1);
                    });

                    seededItems.forEach((item) => {
                        if (!counts.has(item.label)) {
                            counts.set(item.label, 0);
                        }
                    });

                    return Array.from(counts.entries()).map(([label, value]) => {
                        const seed = seededItems.find((item) => item.label === label) || {};

                        return {
                            label,
                            shortLabel: seed.shortLabel || this.abbreviate(label),
                            value,
                            color: seed.color,
                        };
                    });
                },
                sumRows(rows, labelCellIndex, valueCellIndex) {
                    const sums = new Map();

                    rows.forEach((row) => {
                        const label = this.displayValue(row.cells[labelCellIndex]);
                        sums.set(label, (sums.get(label) || 0) + Number(row.cells[valueCellIndex] || 0));
                    });

                    return Array.from(sums.entries()).map(([label, value]) => ({
                        label,
                        shortLabel: this.abbreviate(label),
                        value,
                    }));
                },
                sumCell(rows, cellIndex) {
                    return rows.reduce((total, row) => total + Number(row.cells[cellIndex] || 0), 0);
                },
                displayValue(value) {
                    const text = String(value || '').trim();

                    return text === '' ? 'N/A' : text;
                },
                abbreviate(label) {
                    const text = this.displayValue(label);
                    const known = {
                        'Migrant Workers Processing Division': 'MWPD',
                        'Finance and Administrative Division': 'FAD',
                        'Migrant Workers Protection Division': 'MWPrD',
                        'Welfare and Reintegration Services Division': 'WRSD',
                        'Regular': 'REG.',
                        'Job Order': 'J.O.',
                        'Contract of Service': 'C.O.S.',
                    };

                    if (known[text]) {
                        return known[text];
                    }

                    return text
                        .replace(/[^A-Za-z0-9\s]/g, ' ')
                        .split(/\s+/)
                        .filter((word) => word && !['and', 'of', 'the', 'for'].includes(word.toLowerCase()))
                        .slice(0, 5)
                        .map((word) => word[0].toUpperCase())
                        .join('') || text;
                },
                hasChartData(chart) {
                    return (chart?.items || []).some((item) => Number(item.value || 0) > 0);
                },
                replaceChart(name, canvas, config) {
                    if (!canvas) {
                        return;
                    }

                    if (this.charts[name]) {
                        this.charts[name].destroy();
                    }

                    this.charts[name] = new window.Chart(canvas, config);
                },
                renderReportChart(name, canvas, chart) {
                    if (!chart) {
                        return;
                    }

                    const items = chart.items || [];
                    if (!this.hasChartData(chart)) {
                        if (this.charts[name]) {
                            this.charts[name].destroy();
                            this.charts[name] = null;
                        }

                        return;
                    }

                    const type = chart.type === 'line' ? 'line' : (chart.type === 'doughnut' ? 'doughnut' : 'bar');
                    const isDoughnut = type === 'doughnut';

                    this.replaceChart(name, canvas, {
                        type,
                        data: {
                            labels: items.map((item) => type === 'bar' ? [item.shortLabel || item.label, String(item.value)] : (item.shortLabel || item.label)),
                            datasets: [{
                                label: chart.title,
                                data: items.map((item) => Number(item.value || 0)),
                                backgroundColor: isDoughnut ? items.map((item) => item.color) : '#0038a8',
                                borderColor: isDoughnut ? '#ffffff' : '#002f8f',
                                borderWidth: isDoughnut ? 4 : 1,
                                borderRadius: type === 'bar' ? 7 : 0,
                                tension: type === 'line' ? 0.35 : 0,
                                fill: type === 'line',
                                pointRadius: type === 'line' ? 3 : 0,
                                maxBarThickness: 42,
                                categoryPercentage: 0.62,
                                barPercentage: 0.88,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: isDoughnut ? '68%' : undefined,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    callbacks: {
                                        title: (items) => {
                                            const item = chart.items[items[0]?.dataIndex] || {};
                                            return item.fullLabel || item.label || '';
                                        },
                                        label: (context) => {
                                            const value = isDoughnut ? context.parsed : context.parsed.y;
                                            return `Count: ${value}`;
                                        },
                                    },
                                },
                            },
                            scales: isDoughnut ? {} : {
                                x: {
                                    grid: { display: false },
                                    ticks: {
                                        color: '#64748b',
                                        font: { size: 11, weight: 'bold' },
                                        maxRotation: 0,
                                        minRotation: 0,
                                    },
                                },
                                y: {
                                    beginAtZero: true,
                                    grid: { color: '#e0f2fe' },
                                    ticks: {
                                        precision: 0,
                                        color: '#64748b',
                                        font: { size: 11, weight: 'bold' },
                                    },
                                },
                            },
                        },
                    });
                },
                filteredRows() {
                    const term = this.search.toLowerCase().trim();
                    const division = this.filters.division;
                    const employmentStatus = this.filters.employmentStatus;

                    return this.active.rows.filter((row) => {
                        const matchesSearch = !term || row.cells.join(' ').toLowerCase().includes(term);
                        const matchesDivision = division === 'All Divisions' || row.division === '' || row.division === division;
                        const matchesEmployment = employmentStatus === 'All Employment Status' || row.employmentStatus === '' || row.employmentStatus === employmentStatus;

                        return matchesSearch && matchesDivision && matchesEmployment;
                    });
                },
                displayCells(row) {
                    return this.active.columns.map((column, index) => {
                        const value = row.cells?.[index];

                        return value === undefined || value === null || String(value).trim() === '' ? 'N/A' : value;
                    });
                },
                exportExcel() {
                    const rows = [this.active.columns, ...this.filteredRows().map((row) => this.displayCells(row))];
                    const csv = rows
                        .map((row) => row.map((cell) => `"${String(cell).replaceAll('"', '""')}"`).join(','))
                        .join('\n');
                    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                    const link = document.createElement('a');
                    const filename = `${this.active.title.toLowerCase().replace(/[^a-z0-9]+/g, '-')}.csv`;

                    link.href = URL.createObjectURL(blob);
                    link.download = filename;
                    link.click();
                    URL.revokeObjectURL(link.href);
                },
            };
        }
    </script>
</x-app-layout>
