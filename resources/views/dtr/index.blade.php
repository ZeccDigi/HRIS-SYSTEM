<x-app-layout>
    <x-slot name="title">{{ __('Daily Time Records (DTR)') }}</x-slot>

    @php
        $user = auth()->user();
        $employee = $dtrEmployee ?? $user->employee;
        $isEmployeeReview = ! $isPersonal && $dtrEmployee;
        $monthSelectionRoute = $isEmployeeReview
            ? route('dtr.employee', $employee)
            : route('my-dtr.index');
        $printDtrRoute = $isEmployeeReview
            ? route('dtr.employee.print', ['employee' => $employee, 'month' => $selectedMonth, 'year' => $selectedYear])
            : route('my-dtr.print', ['month' => $selectedMonth, 'year' => $selectedYear]);
        $isDataVisible = ! ($isPersonal ?? false) || ($dtrMonthReleased ?? false);
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];
        $startYear = $firstDtrYear;
        $endYear = now()->year + 1;
        $selectedDate = \Carbon\Carbon::create((int) $selectedYear, (int) $selectedMonth, 1);
        $calendarDays = collect(range(1, $selectedDate->daysInMonth))->map(fn ($day) => $selectedDate->copy()->day($day));
        $recordsByDate = collect($dtrRecords ?? [])->groupBy(fn ($record) => \Carbon\Carbon::parse($record->date)->format('Y-m-d'));
        $displayEmployeeNo = $employee?->employee_no ?? ($employee?->id ? 'EMP-' . str_pad((string) $employee->id, 4, '0', STR_PAD_LEFT) : 'N/A');
        $statusText = fn ($record) => (string) ($record->status ?: 'Present');
        $formatTime = fn ($time) => $time ? \Carbon\Carbon::parse($time)->format('h:i A') : '';
        $printEmployeeName = trim(($employee?->lastname ?? '') . ', ' . ($employee?->firstname ?? '') . ' ' . ($employee?->middle_initial ?? ''));
        $printEmployeeName = trim($printEmployeeName, ', ') ?: $user->name;
        $regionalDirector = \App\Models\Employee::query()
            ->where('account_role', 'regionaldirector')
            ->orderBy('lastname')
            ->first();
        $regionalDirectorName = $regionalDirector
            ? trim(($regionalDirector->firstname ?? '') . ' ' . ($regionalDirector->middlename ? mb_substr($regionalDirector->middlename, 0, 1) . '. ' : '') . ($regionalDirector->lastname ?? ''))
            : 'MARIA CAROLINA B. AGDAMAG';
        $printMonthPeriod = $selectedDate->format('F 1') . ' - ' . $selectedDate->copy()->endOfMonth()->format('F j, Y');
        $calculator = app(\App\Services\AttendanceCalculator::class);
        $actualWorkedMinutes = fn ($record) => $calculator->actualMinutes($record, $employee);
        $lateMinutesForRecord = fn ($record) => $calculator->lateMinutes($record, $employee);
        $undertimeMinutesForRecord = fn ($record) => $calculator->undertimeMinutes($record, $employee);
        $attendanceEventsByDate = collect($attendanceEventsByDate ?? []);
        $printDays = collect(range(1, 31))->map(function ($dayNumber) use ($selectedDate, $recordsByDate, $undertimeMinutesForRecord, $attendanceEventsByDate, $calculator, $employee) {
            $day = $dayNumber <= $selectedDate->daysInMonth ? $selectedDate->copy()->day($dayNumber) : null;
            $record = $day ? $recordsByDate->get($day->format('Y-m-d'), collect())->first() : null;
            $approvedEvent = $day ? $attendanceEventsByDate->get($day->format('Y-m-d')) : null;
            if ($day && $calculator->isBlankDay($employee, $day)) { $record = null; $approvedEvent = null; }
            $undertimeMinutes = $day && (!$approvedEvent || ($approvedEvent['locator_only'] ?? false)) ? $undertimeMinutesForRecord($record) : 0;

            return [
                'day_number' => $dayNumber,
                'record' => $record,
                'approved_event' => $approvedEvent,
                'undertime_hours' => $undertimeMinutes > 0 ? intdiv($undertimeMinutes, 60) : '',
                'undertime_minutes' => $undertimeMinutes > 0 ? $undertimeMinutes % 60 : '',
                'total_undertime_minutes' => $undertimeMinutes,
            ];
        });
        $totalUndertimeMinutes = $printDays->sum('total_undertime_minutes');
        $totalUndertimeHours = $totalUndertimeMinutes > 0 ? intdiv($totalUndertimeMinutes, 60) : '';
        $totalUndertimeRemainder = $totalUndertimeMinutes > 0 ? $totalUndertimeMinutes % 60 : '';
        $recordHours = function ($record) use ($actualWorkedMinutes) {
            $minutes = $actualWorkedMinutes($record);

            if ($minutes <= 0) {
                return '';
            }

            return number_format($minutes / 60, 2);
        };
        $monthlyActualMinutes = collect($dtrRecords ?? [])->sum(fn ($record) => $actualWorkedMinutes($record));
        $monthlyOvertimeMinutes = collect($dtrRecords ?? [])->sum(fn ($record) => $calculator->overtimeMinutes($record, $employee));
        $monthlyOfficialMinutes = $calendarDays->sum(function ($day) use ($recordsByDate, $attendanceEventsByDate, $calculator, $employee) {
            if ($calculator->isBlankDay($employee, $day)) {
                return 0;
            }

            $record = $recordsByDate->get($day->format('Y-m-d'), collect())->first();
            $workedMinutes = $calculator->regularMinutes($record, $employee);
            $scheduledMinutes = $calculator->scheduledMinutes($employee, $day);

            return $attendanceEventsByDate->has($day->format('Y-m-d'))
                ? max($scheduledMinutes, $workedMinutes)
                : $workedMinutes;
        });
        $monthlyLateMinutes = $calendarDays->sum(function ($day) use ($recordsByDate, $attendanceEventsByDate, $lateMinutesForRecord) {
            $event = $attendanceEventsByDate->get($day->format('Y-m-d'));
            if ($day->isWeekend() || ($event && !($event['locator_only'] ?? false))) {
                return 0;
            }

            return $lateMinutesForRecord($recordsByDate->get($day->format('Y-m-d'), collect())->first());
        });
        $monthlyUndertimeMinutes = $calendarDays->sum(function ($day) use ($recordsByDate, $attendanceEventsByDate, $undertimeMinutesForRecord) {
            $event = $attendanceEventsByDate->get($day->format('Y-m-d'));
            if ($day->isWeekend() || ($event && !($event['locator_only'] ?? false))) {
                return 0;
            }

            return $undertimeMinutesForRecord($recordsByDate->get($day->format('Y-m-d'), collect())->first());
        });
        $monthlyDaysPresent = collect($dtrRecords ?? [])->filter(fn ($record) => $record->time_in || $record->am_out || $record->pm_in || $record->time_out)->count();
        $monthlyTotalWorkedMinutes = $monthlyOfficialMinutes + $monthlyOvertimeMinutes;
        $monthlyDeductionMinutes = $monthlyLateMinutes + $monthlyUndertimeMinutes;
        $monthlyEquivalentDays = $monthlyTotalWorkedMinutes / 480;
        $formatSummaryHours = fn ($minutes) => $isDataVisible ? number_format(max(0, $minutes) / 60, 2) : '';
        $scheduleSource = $calculator->sourceFor($employee, $calendarDays->last());
        $coreTimeSchedule = collect($scheduleSource?->core_time_schedule ?? []);
        $coreTimeDayLabels = [
            'monday' => 'Mon',
            'tuesday' => 'Tue',
            'wednesday' => 'Wed',
            'thursday' => 'Thu',
            'friday' => 'Fri',
        ];
        $formatCoreTime = fn ($time) => $time ? \Carbon\Carbon::createFromFormat('H:i', $time)->format('h:i A') : null;
        $coreTimeLines = collect($coreTimeDayLabels)->map(function ($label, $dayKey) use ($coreTimeSchedule, $formatCoreTime) {
            $schedule = $coreTimeSchedule->get($dayKey, []);
            $amStart = $formatCoreTime(data_get($schedule, 'am_start'));
            $amEnd = $formatCoreTime(data_get($schedule, 'am_end'));
            $pmStart = $formatCoreTime(data_get($schedule, 'pm_start'));
            $pmEnd = $formatCoreTime(data_get($schedule, 'pm_end'));
            $periods = collect([
                $amStart && $amEnd ? $amStart.' - '.$amEnd : null,
                $pmStart && $pmEnd ? $pmStart.' - '.$pmEnd : null,
            ])->filter()->implode(' | ');

            return $periods ? $label.' '.$periods : null;
        })->filter()->values();
        $coreTimePeriod = collect([
            $scheduleSource?->core_time_start_date?->format('M. d, Y'),
            $scheduleSource?->core_time_end_date?->format('M. d, Y'),
        ])->filter()->implode(' to ');
        $previousSchedule = null;
        $scheduleSegment = 0;
        $coreTimeSections = $calendarDays->groupBy(function ($day) use ($calculator, $employee, &$previousSchedule, &$scheduleSegment) {
            $source = $calculator->sourceFor($employee, $day);
            $schedule = collect($source?->core_time_schedule ?? [])->sortKeys()->map(fn ($slots) => collect($slots)->sortKeys()->all())->all();
            $signature = json_encode($schedule);
            if ($signature !== $previousSchedule) { $scheduleSegment++; $previousSchedule = $signature; }
            return $scheduleSegment;
        })->map(function ($days) use ($calculator, $employee, $coreTimeDayLabels, $formatCoreTime) {
            $source = $calculator->sourceFor($employee, $days->first());
            $lines = collect($coreTimeDayLabels)->map(function ($label, $key) use ($source, $formatCoreTime) {
                $day = $source?->core_time_schedule[$key] ?? [];
                $periods = [];
                foreach (['am', 'pm'] as $session) {
                    if (!empty($day[$session.'_start']) && !empty($day[$session.'_end'])) $periods[] = $formatCoreTime($day[$session.'_start']).' - '.$formatCoreTime($day[$session.'_end']);
                }
                return $periods ? $label.' '.implode(' | ', $periods) : null;
            })->filter()->implode('; ');
            return ['period' => $days->first()->format('M. d, Y').' to '.$days->last()->format('M. d, Y'), 'lines' => $lines];
        });
    @endphp

    <style>
        @media print {
            @page {
                size: A4 portrait;
                margin: 5mm;
            }

            html,
            body {
                width: 210mm !important;
                min-height: 297mm !important;
                margin: 0 !important;
                background: #fff !important;
                overflow: visible !important;
            }

            body * {
                visibility: hidden !important;
                box-shadow: none !important;
                text-shadow: none !important;
            }

            .no-print,
            .dtr-screen-only,
            nav,
            aside,
            header {
                display: none !important;
            }

            .dtr-print-only {
                display: block !important;
                visibility: visible !important;
                position: absolute !important;
                top: 7mm !important;
                left: 0 !important;
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
                background: #fff !important;
                border: 0 !important;
                border-radius: 0 !important;
            }

            .dtr-print-only * {
                visibility: visible !important;
            }

            .py-8,
            .max-w-7xl {
                padding: 0 !important;
                margin: 0 !important;
                max-width: none !important;
                width: 100% !important;
            }

            .dtr-print-shell {
                padding: 0 !important;
                margin: 0 !important;
                background: #fff !important;
                box-shadow: none !important;
                border: 0 !important;
                border-radius: 0 !important;
                outline: 0 !important;
                ring-width: 0 !important;
            }

            .dtr-print-only,
            .dtr-print-only > div,
            .dtr-preview-page,
            .dtr-preview-grid,
            .dtr-form-copy {
                box-shadow: none !important;
                border-radius: 0 !important;
                outline: 0 !important;
            }

            .dtr-sheet {
                width: 100% !important;
                border: 0 !important;
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .dtr-preview-page {
                width: 196mm !important;
                min-height: 0 !important;
                padding: 0 !important;
                margin: 0 auto !important;
                border: 0 !important;
                box-shadow: none !important;
            }

            .dtr-preview-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
                gap: 3.5mm !important;
            }

            .dtr-form-copy {
                width: 100% !important;
                min-width: 0 !important;
                overflow: hidden !important;
            }

            .dtr-late-summary,
            .dtr-signatures {
                display: none !important;
            }
        }

        .dtr-print-only {
            display: none;
        }

        .dtr-screen-table {
            table-layout: fixed;
        }

        .dtr-screen-table th,
        .dtr-screen-table td {
            overflow: hidden;
            padding-left: 0.25rem !important;
            padding-right: 0.25rem !important;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dtr-screen-table td.dtr-reference-cell {
            overflow: visible;
            text-overflow: clip;
            white-space: normal;
        }

        .dtr-screen-table td.dtr-duration-cell {
            overflow: visible;
            text-overflow: clip;
            white-space: normal;
            overflow-wrap: anywhere;
            padding-left: 2px !important;
            padding-right: 2px !important;
            line-height: 1.35;
        }

        .dtr-screen-table td.dtr-remarks-cell {
            overflow: visible;
            text-overflow: clip;
            white-space: normal;
            overflow-wrap: normal;
            word-break: normal;
            line-height: 1.4;
        }

        .dtr-reference-value {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            max-width: 100%;
            font-size: 8px;
            font-weight: 700;
            line-height: 1.05;
            white-space: nowrap;
        }

        .dtr-preview-page {
            width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            background: #fff;
            padding: 2mm 1mm 0;
            box-sizing: border-box;
        }

        .dtr-preview-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4mm;
            align-items: start;
        }

        .dtr-form-copy {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7.6pt;
            line-height: 1.05;
            color: #000;
            border: 0.25mm solid #000;
            padding: 2.6mm 2.3mm 3mm;
            box-sizing: border-box;
        }

        .dtr-form-title-small {
            font-size: 7.4pt;
            font-weight: 700;
        }

        .dtr-form-title {
            margin-top: 1mm;
            text-align: center;
            font-size: 10.5pt;
            font-weight: 700;
            letter-spacing: 0.4px;
            text-decoration: none;
        }

        .dtr-form-row {
            display: table;
            width: 100%;
            margin-top: 1mm;
            table-layout: fixed;
        }

        .dtr-form-cell {
            display: table-cell;
            vertical-align: bottom;
            white-space: nowrap;
        }

        .dtr-form-line {
            display: inline-block;
            min-height: 3.1mm;
            border-bottom: 0.2mm solid #000;
            text-align: center;
            font-weight: 700;
            vertical-align: bottom;
            overflow: hidden;
            text-overflow: clip;
            white-space: nowrap;
            box-sizing: border-box;
            padding: 0 0.3mm;
        }

        .dtr-log-table {
            width: 100%;
            margin-top: 1.3mm;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 6.8pt;
        }

        .dtr-log-table th,
        .dtr-log-table td {
            border: 0.2mm solid #000;
            height: 4.25mm;
            padding: 0.2mm 0.25mm;
            text-align: center;
            vertical-align: middle;
            font-weight: 400;
            overflow: hidden;
            white-space: nowrap;
        }

        .dtr-log-table th {
            font-size: 5.7pt;
            font-weight: 700;
        }

        .dtr-log-table .dtr-day-cell {
            width: 8%;
        }

        .dtr-log-table .dtr-time-cell {
            width: 18.5%;
        }

        .dtr-log-table .dtr-under-cell {
            width: 9%;
        }

        .dtr-certification {
            margin-top: 2mm;
            font-size: 7pt;
            line-height: 1.12;
            text-align: justify;
        }

        .dtr-sign-line {
            margin: 7mm auto 0;
            width: 67%;
            border-bottom: 0.2mm solid #000;
            min-height: 3.8mm;
            text-align: center;
            font-weight: 700;
        }

        .dtr-verify-line {
            width: 100%;
        }

        .dtr-sign-caption {
            text-align: center;
            font-size: 6.8pt;
        }
    </style>

    <div class="py-8">
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

            <div class="dtr-screen-only px-1 sm:px-0">
                <form method="GET" action="{{ $monthSelectionRoute }}" class="flex flex-col gap-4 lg:flex-row lg:items-center">
                    <div class="grid flex-1 gap-4 sm:grid-cols-2">
                        <label class="relative block">
                            <span class="absolute -top-2 left-3 bg-white px-1 text-xs text-slate-500">Month</span>
                            <select name="month" class="h-9 w-full rounded-lg border border-slate-300 bg-white pl-3 pr-8 py-1 text-xs outline-none focus:border-[#2b428f]">
                                @foreach($months as $num => $name)
                                    <option value="{{ $num }}" {{ (int) $selectedMonth === $num ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="relative block">
                            <span class="absolute -top-2 left-3 bg-white px-1 text-xs text-slate-500">Year</span>
                            <select name="year" class="h-9 w-full rounded-lg border border-slate-300 bg-white pl-3 pr-8 py-1 text-xs outline-none focus:border-[#2b428f]">
                                @for($year = $startYear; $year <= $endYear; $year++)
                                    <option value="{{ $year }}" {{ (int) $selectedYear === $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endfor
                            </select>
                        </label>
                    </div>

                    <div class="flex gap-2 lg:shrink-0">
                        <button type="submit" style="background-color: #2b428f;" class="inline-flex h-9 min-w-24 items-center justify-center rounded-xl px-4 text-xs font-bold uppercase tracking-wide text-white shadow">
                            {{ __('Select') }}
                        </button>
                        <a href="{{ $printDtrRoute }}" target="_blank" rel="noopener" style="background-color: #2b428f;" class="inline-flex h-9 min-w-24 items-center justify-center rounded-xl px-4 text-xs font-bold uppercase tracking-wide text-white shadow">
                            {{ __('Print') }}
                        </a>
                    </div>
                </form>
            </div>

            <div class="dtr-screen-only rounded-2xl bg-white px-6 py-7 shadow-sm ring-1 ring-slate-200">
                <div>
                    <div class="text-center">
                            <h2 class="text-base font-bold text-slate-900">
                                Department of Migrant Workers - Region XI
                            </h2>
                            <p class="text-xs text-slate-600">
                                Davao City, Philippines
                            </p>
                            <p class="text-xs text-slate-600">
                                Website: www.dmw.gov.ph | Email: region11@dmw.gov.ph
                            </p>
                            <h3 class="mt-4 text-base font-bold text-slate-900">
                                Daily Time Record for the period of {{ $dtrSummary['period'] }}
                            </h3>
                        </div>

                        <div class="mt-5 grid gap-4 text-xs text-slate-700 md:grid-cols-2">
                            <div>
                                <p class="font-semibold">Legend</p>
                                <p class="mt-1">AHW - Actual Hours Worked &nbsp;&nbsp; OHW - Official Hours Worked</p>
                                <p>OT - Overtime &nbsp;&nbsp; LT - Lates &nbsp;&nbsp; UT - Undertime</p>
                            </div>

                            <div class="md:text-right">
                                <p>Employee No. <span class="font-bold underline">{{ $displayEmployeeNo }}</span></p>
                                <p>Name: <span class="font-bold uppercase">{{ trim(($employee?->firstname ?? '') . ' ' . ($employee?->lastname ?? '')) ?: $user->name }}</span></p>
                            </div>
                        </div>

                        <div class="mt-5">
                            <table class="dtr-screen-table w-full border-collapse text-center text-[10px] leading-tight">
                                <thead>
                                    <tr class="bg-slate-50">
                                        <th rowspan="2" class="border border-slate-300 px-2 py-2">Date</th>
                                        <th rowspan="2" class="border border-slate-300 px-2 py-2">Day</th>
                                        <th colspan="2" class="border border-slate-300 px-2 py-2">Morning</th>
                                        <th colspan="2" class="border border-slate-300 px-2 py-2">Afternoon</th>
                                        <th colspan="2" class="border border-slate-300 px-2 py-2">Overtime</th>
                                        <th rowspan="2" class="border border-slate-300 px-2 py-2">AHW</th>
                                        <th rowspan="2" class="border border-slate-300 px-2 py-2">OHW</th>
                                        <th rowspan="2" class="border border-slate-300 px-2 py-2">OT</th>
                                        <th rowspan="2" class="border border-slate-300 px-2 py-2">LT</th>
                                        <th rowspan="2" class="border border-slate-300 px-2 py-2">UT</th>
                                        <th rowspan="2" class="border border-slate-300 px-2 py-2">Remarks</th>
                                    </tr>
                                    <tr class="bg-slate-50">
                                        <th class="border border-slate-300 px-2 py-2">In</th>
                                        <th class="border border-slate-300 px-2 py-2">Out</th>
                                        <th class="border border-slate-300 px-2 py-2">In</th>
                                        <th class="border border-slate-300 px-2 py-2">Out</th>
                                        <th class="border border-slate-300 px-2 py-2">In</th>
                                        <th class="border border-slate-300 px-2 py-2">Out</th>
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach($calendarDays as $day)
                                        @php
                                            $record = $recordsByDate->get($day->format('Y-m-d'), collect())->first();
                                            $isWeekend = $day->isWeekend();
                                            $blankDay = $calculator->isBlankDay($employee, $day);
                                            $approvedEvent = $attendanceEventsByDate->get($day->format('Y-m-d'));
                                            if ($blankDay) { $record = null; $approvedEvent = null; }
                                            $slotLabels = $approvedEvent['slot_labels'] ?? [];
                                            $hours = $record ? $recordHours($record) : '';
                                            $excusedDay = $approvedEvent && !($approvedEvent['locator_only'] ?? false);
                                            $lateMinutes = $record && !$excusedDay ? $lateMinutesForRecord($record) : 0;
                                            $undertimeMinutes = $record && !$excusedDay ? $undertimeMinutesForRecord($record) : 0;
                                            $remarks = $approvedEvent['remarks'] ?? ($isWeekend ? 'Weekend' : ($record ? $statusText($record) : ''));
                                            if (!$isWeekend && $calculator->isIncomplete($record, $employee, $day, $slotLabels)) {
                                                $remarks = $approvedEvent['remarks'] ?? '';
                                            }
                                            if ($blankDay) $remarks = $isWeekend ? 'Weekend' : '';
                                            if (!$isWeekend && !$calculator->hasDayEnded($day)) $remarks = '';
                                        @endphp
                                        <tr class="hover:bg-slate-50">
                                            <td class="border border-slate-300 px-2 py-1">{{ $day->day }}</td>
                                            <td class="border border-slate-300 px-2 py-1">{{ $day->format('D') }}</td>
                                            <td class="border border-slate-300 px-2 py-1 {{ ! $record?->time_in && isset($slotLabels['time_in']) ? 'dtr-reference-cell text-blue-700' : '' }}">@if($record?->time_in){{ $formatTime($record->time_in) }}@elseif(isset($slotLabels['time_in']))<x-dtr-reference :value="$slotLabels['time_in']" />@endif</td>
                                            <td class="border border-slate-300 px-2 py-1 {{ ! $record?->am_out && isset($slotLabels['am_out']) ? 'dtr-reference-cell text-blue-700' : '' }}">@if($record?->am_out){{ $formatTime($record->am_out) }}@elseif(isset($slotLabels['am_out']))<x-dtr-reference :value="$slotLabels['am_out']" />@endif</td>
                                            <td class="border border-slate-300 px-2 py-1 {{ ! $record?->pm_in && isset($slotLabels['pm_in']) ? 'dtr-reference-cell text-blue-700' : '' }}">@if($record?->pm_in){{ $formatTime($record->pm_in) }}@elseif(isset($slotLabels['pm_in']))<x-dtr-reference :value="$slotLabels['pm_in']" />@endif</td>
                                            <td class="border border-slate-300 px-2 py-1 {{ ! $record?->time_out && isset($slotLabels['time_out']) ? 'dtr-reference-cell text-blue-700' : '' }}">@if($record?->time_out){{ $formatTime($record->time_out) }}@elseif(isset($slotLabels['time_out']))<x-dtr-reference :value="$slotLabels['time_out']" />@endif</td>
                                            <td class="border border-slate-300 px-2 py-1"></td>
                                            <td class="border border-slate-300 px-2 py-1"></td>
                                            <td class="border border-slate-300 px-2 py-1">{{ $hours }}</td>
                                            <td class="border border-slate-300 px-2 py-1">{{ $isWeekend || $calculator->assignedMinutes($employee, $day) <= 0 ? '' : number_format($calculator->assignedMinutes($employee, $day) / 60, 2) }}</td>
                                            <td class="border border-slate-300 px-2 py-1">{{ $record && $calculator->overtimeMinutes($record, $employee) > 0 ? number_format($calculator->overtimeMinutes($record, $employee) / 60, 2) : '' }}</td>
                                            <td class="dtr-duration-cell border border-slate-300 py-1">{{ $lateMinutes > 0 ? number_format($lateMinutes, 2) . ' min' : '' }}</td>
                                            <td class="dtr-duration-cell border border-slate-300 py-1">{{ $undertimeMinutes > 0 ? number_format($undertimeMinutes, 2) . ' min' : '' }}</td>
                                            <td class="dtr-remarks-cell border border-slate-300 px-2 py-1 text-left">{{ ($isWeekend && $blankDay) || $isDataVisible ? $remarks : '' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="dtr-late-summary mt-8 overflow-x-auto">
                            <table class="w-full min-w-[760px] border-collapse text-xs text-slate-800">
                                <tbody>
                                    <tr>
                                        <th colspan="4" class="border border-slate-300 bg-slate-100 px-3 py-2 text-center text-sm font-bold">Attendance Summary</th>
                                    </tr>
                                    <tr>
                                        <td class="w-[30%] border border-slate-300 px-3 py-2 text-center">Actual Hours Worked (AHW)</td>
                                        <td class="w-[20%] border border-slate-300 px-3 py-2 text-center font-bold">{{ $formatSummaryHours($monthlyActualMinutes) }}</td>
                                        <td class="w-[30%] border border-slate-300 px-3 py-2 text-center">Late (LT)</td>
                                        <td class="w-[20%] border border-slate-300 px-3 py-2 text-center font-bold">{{ $formatSummaryHours($monthlyLateMinutes) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-slate-300 px-3 py-2 text-center">Official Hours Worked (OHW)</td>
                                        <td class="border border-slate-300 px-3 py-2 text-center font-bold">{{ $formatSummaryHours($monthlyOfficialMinutes) }}</td>
                                        <td class="border border-slate-300 px-3 py-2 text-center">Undertime (UT)</td>
                                        <td class="border border-slate-300 px-3 py-2 text-center font-bold">{{ $formatSummaryHours($monthlyUndertimeMinutes) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="border border-slate-300 px-3 py-2 text-center">Overtime (OT)</td>
                                        <td class="border border-slate-300 px-3 py-2 text-center font-bold">{{ $formatSummaryHours($monthlyOvertimeMinutes) }}</td>
                                        <td class="border border-slate-300 px-3 py-2 text-center">Days Present (DP)</td>
                                        <td class="border border-slate-300 px-3 py-2 text-center font-bold">{{ $isDataVisible ? $monthlyDaysPresent : '' }}</td>
                                    </tr>
                                    <tr>
                                        <th colspan="4" class="border border-slate-300 bg-slate-100 px-3 py-2 text-center text-sm font-bold">Final Computation</th>
                                    </tr>
                                    <tr>
                                        <td class="border border-slate-300 px-3 py-2 text-center">Total Hours Worked (OHW + OT)</td>
                                        <td class="border border-slate-300 px-3 py-2 text-center font-bold">{{ $formatSummaryHours($monthlyTotalWorkedMinutes) }}</td>
                                        <td class="border border-slate-300 px-3 py-2 text-center">Total Deductions (LT + UT)</td>
                                        <td class="border border-slate-300 px-3 py-2 text-center font-bold">{{ $formatSummaryHours($monthlyDeductionMinutes) }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" class="border border-slate-300 px-3 py-2 text-center">Equivalent Days Present</td>
                                        <td class="border border-slate-300 px-3 py-2 text-center font-bold">{{ $isDataVisible ? number_format($monthlyEquivalentDays, 2) : '' }}</td>
                                    </tr>
                                    <tr>
                                        <th class="border border-slate-300 px-3 py-2 text-left">Remarks</th>
                                        <td colspan="3" class="border border-slate-300 px-3 py-2">&nbsp;</td>
                                    </tr>
                                </tbody>
                            </table>

                            <div class="mt-5 text-xs leading-6 text-slate-800">
                                @if($coreTimeSections->count() > 1)
                                    <p class="font-bold">Approved Core Time:</p>
                                    @foreach($coreTimeSections as $section)
                                        <p class="font-semibold">{{ $section['period'] }}</p>
                                        <p>{{ $section['lines'] ?: 'No weekday core-time schedule assigned.' }}</p>
                                    @endforeach
                                @else
                                <p>
                                    <span class="font-bold">Approved Core Time:</span>
                                    {{ $coreTimePeriod ?: 'No effective period assigned' }}
                                </p>
                                @if($coreTimeLines->isNotEmpty())
                                    <p>{{ $coreTimeLines->implode('; ') }}</p>
                                @else
                                    <p class="text-slate-500">No weekday core-time schedule assigned.</p>
                                @endif
                                @endif
                            </div>
                        </div>

                        <div class="dtr-signatures mt-12 grid gap-12 text-sm md:grid-cols-2">
                            <div class="text-center">
                                <div class="mx-auto flex h-16 w-64 items-end justify-center">
                                    @if($employee?->effective_signature_url)
                                        <img src="{{ $employee->effective_signature_url }}" alt="Employee e-signature" class="max-h-14 object-contain">
                                    @else
                                        <span class="text-xs italic text-slate-400">Employee e-signature</span>
                                    @endif
                                </div>

                                <div class="mx-auto w-64 border-t border-slate-400 pt-2">
                                    <p class="font-semibold">Employee Signature</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ trim(($employee?->firstname ?? '') . ' ' . ($employee?->lastname ?? '')) ?: $user->name }}</p>
                                </div>
                            </div>

                            <div class="text-center">
                                <div class="mx-auto flex h-16 w-64 items-end justify-center">
                                    <span class="text-xs italic text-slate-400">HR e-signature</span>
                                </div>

                                <div class="mx-auto w-64 border-t border-slate-400 pt-2">
                                    <p class="font-semibold">HR Verification</p>
                                    <p class="mt-1 text-xs text-slate-500">Authorized HR Personnel</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <div class="dtr-print-only dtr-print-shell">
                <div>
                    <div class="dtr-sheet">
                        <div class="dtr-preview-page">
                            <div class="dtr-preview-grid">
                                @foreach([1, 2] as $copy)
                                    <section class="dtr-form-copy">
                                        <div class="dtr-form-title-small">CIVIL SERVICE No. 48</div>
                                        <div class="dtr-form-title">DAILY&nbsp;&nbsp;TIME&nbsp;&nbsp;RECORD</div>

                                        <div class="dtr-form-row" style="margin-top:2.2mm;">
                                            <span class="dtr-form-cell" style="width:32%;">FOR THE MONTH PERIOD:</span>
                                            <span class="dtr-form-cell">
                                                <span class="dtr-form-line" style="width:100%; font-size:6.8pt;">{{ $printMonthPeriod }}</span>
                                            </span>
                                        </div>

                                        <div class="dtr-form-row">
                                            <span class="dtr-form-cell" style="width:11%;">Name:</span>
                                            <span class="dtr-form-cell" style="width:55%;">
                                                <span class="dtr-form-line" style="width:100%; text-transform:uppercase; font-size:6.8pt;">{{ $printEmployeeName }}</span>
                                            </span>
                                            <span class="dtr-form-cell" style="width:13%; padding-left:0.8mm;">Emp No:</span>
                                            <span class="dtr-form-cell" style="width:21%;">
                                                <span class="dtr-form-line" style="width:100%; font-size:6.4pt;">{{ $displayEmployeeNo }}</span>
                                            </span>
                                        </div>

                                        <div class="dtr-form-row">
                                            <span class="dtr-form-cell" style="width:17%;">Position:</span>
                                            <span class="dtr-form-cell" style="width:50%;">
                                                <span class="dtr-form-line" style="width:100%; font-size:6.8pt;">{{ $employee?->position ?? 'N/A' }}</span>
                                            </span>
                                            <span class="dtr-form-cell" style="width:18%; padding-left:1mm;">Official Time:</span>
                                            <span class="dtr-form-cell" style="width:15%;">
                                                <span class="dtr-form-line" style="width:100%; font-size:6.8pt;">8-5</span>
                                            </span>
                                        </div>

                                        <div class="dtr-form-row">
                                            <span class="dtr-form-cell" style="width:12%;">Office:</span>
                                            <span class="dtr-form-cell">
                                                <span class="dtr-form-line" style="width:100%; font-size:6.8pt;">{{ $employee?->division ?? 'DMW RO XI' }}</span>
                                            </span>
                                        </div>

                                        <table class="dtr-log-table">
                                            <thead>
                                                <tr>
                                                    <th rowspan="2" class="dtr-day-cell">Day</th>
                                                    <th colspan="2">AM</th>
                                                    <th colspan="2">PM</th>
                                                    <th colspan="2">UNDERTIME</th>
                                                </tr>
                                                <tr>
                                                    <th class="dtr-time-cell">Arrival</th>
                                                    <th class="dtr-time-cell">Departure</th>
                                                    <th class="dtr-time-cell">Arrival</th>
                                                    <th class="dtr-time-cell">Departure</th>
                                                    <th class="dtr-under-cell">Hrs</th>
                                                    <th class="dtr-under-cell">Min</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($printDays as $dayRow)
                                                    @php
                                                        $record = $dayRow['record'];
                                                        $approvedEvent = $dayRow['approved_event'];
                                                        $slotLabels = $approvedEvent['slot_labels'] ?? [];
                                                    @endphp
                                                    <tr>
                                                        <td>{{ $dayRow['day_number'] }}</td>
                                                        <td @if(! $record?->time_in && isset($slotLabels['time_in'])) style="font-size:4.8pt; line-height:1.05; overflow-wrap:anywhere; word-break:break-word;" @endif>{{ $record?->time_in ? $formatTime($record->time_in) : ($slotLabels['time_in'] ?? '') }}</td>
                                                        <td @if(! $record?->am_out && isset($slotLabels['am_out'])) style="font-size:4.8pt; line-height:1.05; overflow-wrap:anywhere; word-break:break-word;" @endif>{{ $record?->am_out ? $formatTime($record->am_out) : ($slotLabels['am_out'] ?? '') }}</td>
                                                        <td @if(! $record?->pm_in && isset($slotLabels['pm_in'])) style="font-size:4.8pt; line-height:1.05; overflow-wrap:anywhere; word-break:break-word;" @endif>{{ $record?->pm_in ? $formatTime($record->pm_in) : ($slotLabels['pm_in'] ?? '') }}</td>
                                                        <td @if(! $record?->time_out && isset($slotLabels['time_out'])) style="font-size:4.8pt; line-height:1.05; overflow-wrap:anywhere; word-break:break-word;" @endif>{{ $record?->time_out ? $formatTime($record->time_out) : ($slotLabels['time_out'] ?? '') }}</td>
                                                        <td>{{ $dayRow['undertime_hours'] }}</td>
                                                        <td>{{ $dayRow['undertime_minutes'] }}</td>
                                                    </tr>
                                                @endforeach
                                                <tr>
                                                    <td colspan="5" style="text-align:left; padding-left:1.5mm; font-weight:700;">TOTAL</td>
                                                    <td>{{ $totalUndertimeHours }}</td>
                                                    <td>{{ $totalUndertimeRemainder }}</td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        <p class="dtr-certification">
                                            I CERTIFY on my honor that the above is true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from office.
                                        </p>

                                        <div class="dtr-sign-line dtr-verify-line">
                                            @if($employee?->effective_signature_url)
                                                <img src="{{ $employee->effective_signature_url }}" alt="Employee e-signature" style="max-height:8mm; max-width:45mm; object-fit:contain;">
                                            @endif
                                        </div>
                                        <div class="dtr-sign-caption">Verified as to the prescribed office hours.</div>

                                        <div class="dtr-sign-line" style="margin-top:6mm;">{{ $regionalDirectorName }}</div>
                                        <div class="dtr-sign-caption">In Charge</div>
                                    </section>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
