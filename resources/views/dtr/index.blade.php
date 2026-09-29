<x-app-layout>
    <x-slot name="title">{{ __('Daily Time Records (DTR)') }}</x-slot>

    @php
        $user = auth()->user();
        $employee = $user->employee;
        $isAdministrativeDtr = ! ($isPersonal ?? false) && in_array(strtolower($user->role ?? ''), ['admin', 'hrstaff', 'chief', 'regionaldirector'], true);
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
        ];
        $startYear = now()->year - 3;
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
        $officialEnd = \Carbon\Carbon::parse('17:00:00');
        $printDays = collect(range(1, 31))->map(function ($dayNumber) use ($selectedDate, $recordsByDate, $officialEnd) {
            $day = $dayNumber <= $selectedDate->daysInMonth ? $selectedDate->copy()->day($dayNumber) : null;
            $record = $day ? $recordsByDate->get($day->format('Y-m-d'), collect())->first() : null;
            $undertimeMinutes = 0;

            if ($day && $record?->time_out && ! $day->isWeekend()) {
                $timeOut = \Carbon\Carbon::parse($record->time_out);

                if ($timeOut->format('H:i:s') < $officialEnd->format('H:i:s')) {
                    $undertimeMinutes = $officialEnd->diffInMinutes($timeOut);
                }
            }

            return [
                'day_number' => $dayNumber,
                'record' => $record,
                'undertime_hours' => $undertimeMinutes > 0 ? intdiv($undertimeMinutes, 60) : '',
                'undertime_minutes' => $undertimeMinutes > 0 ? $undertimeMinutes % 60 : '',
                'total_undertime_minutes' => $undertimeMinutes,
            ];
        });
        $totalUndertimeMinutes = $printDays->sum('total_undertime_minutes');
        $totalUndertimeHours = $totalUndertimeMinutes > 0 ? intdiv($totalUndertimeMinutes, 60) : '';
        $totalUndertimeRemainder = $totalUndertimeMinutes > 0 ? $totalUndertimeMinutes % 60 : '';
        $recordHours = function ($record) {
            if (! $record->time_in || ! $record->time_out) {
                return '';
            }

            $hours = \Carbon\Carbon::parse($record->time_in)->diffInMinutes(\Carbon\Carbon::parse($record->time_out)) / 60;

            return number_format($hours, 2);
        };
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
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                        <h1 class="text-xl font-bold text-slate-900">
                            {{ __('My Daily Time Record') }}
                        </h1>
                        <p class="mt-1 text-sm text-slate-500">
                            {{ __('Review and print your monthly DTR.') }}
                        </p>
                    </div>
                </div>

                <form method="GET" action="{{ route('my-dtr.index') }}" class="mt-6 flex flex-col gap-4 lg:flex-row lg:items-center">
                    <div class="grid flex-1 gap-4 sm:grid-cols-2">
                        <label class="relative block">
                            <span class="absolute -top-2 left-3 bg-white px-1 text-xs text-slate-500">Month</span>
                            <select name="month" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-[#2b428f]">
                                @foreach($months as $num => $name)
                                    <option value="{{ $num }}" {{ (int) $selectedMonth === $num ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>
                        </label>

                        <label class="relative block">
                            <span class="absolute -top-2 left-3 bg-white px-1 text-xs text-slate-500">Year</span>
                            <select name="year" class="w-full rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-[#2b428f]">
                                @for($year = $startYear; $year <= $endYear; $year++)
                                    <option value="{{ $year }}" {{ (int) $selectedYear === $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endfor
                            </select>
                        </label>
                    </div>

                    <div class="flex gap-2 lg:shrink-0">
                        <button type="submit" style="background-color: #2b428f;" class="min-w-28 rounded-lg px-6 py-3 text-sm font-bold uppercase tracking-wide text-white shadow">
                            {{ __('Select') }}
                        </button>
                        <button type="button" onclick="window.print()" style="background-color: #2b428f;" class="min-w-28 rounded-lg px-6 py-3 text-sm font-bold uppercase tracking-wide text-white shadow">
                            {{ __('Print') }}
                        </button>
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
                                            $hours = $record ? $recordHours($record) : '';
                                            $isLate = $record && strtolower($statusText($record)) === 'late';
                                            $remarks = $record ? $statusText($record) : ($isWeekend ? 'Weekend' : '');
                                        @endphp
                                        <tr class="hover:bg-slate-50">
                                            <td class="border border-slate-300 px-2 py-1">{{ $day->day }}</td>
                                            <td class="border border-slate-300 px-2 py-1">{{ $day->format('D') }}</td>
                                            <td class="border border-slate-300 px-2 py-1">{{ $record ? $formatTime($record->time_in) : '' }}</td>
                                            <td class="border border-slate-300 px-2 py-1">{{ $record ? $formatTime($record->am_out) : '' }}</td>
                                            <td class="border border-slate-300 px-2 py-1">{{ $record ? $formatTime($record->pm_in) : '' }}</td>
                                            <td class="border border-slate-300 px-2 py-1">{{ $record ? $formatTime($record->time_out) : '' }}</td>
                                            <td class="border border-slate-300 px-2 py-1"></td>
                                            <td class="border border-slate-300 px-2 py-1"></td>
                                            <td class="border border-slate-300 px-2 py-1">{{ $hours }}</td>
                                            <td class="border border-slate-300 px-2 py-1">{{ $isWeekend ? '' : '8.00' }}</td>
                                            <td class="border border-slate-300 px-2 py-1"></td>
                                            <td class="border border-slate-300 px-2 py-1">{{ $isLate ? 'Late' : '' }}</td>
                                            <td class="border border-slate-300 px-2 py-1"></td>
                                            <td class="border border-slate-300 px-2 py-1 text-left">{{ $remarks }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="dtr-late-summary mt-8 rounded-lg border border-slate-300 bg-slate-50 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <h4 class="text-sm font-bold uppercase tracking-wide text-slate-900">
                                        Late Dates Summary
                                    </h4>
                                    <p class="mt-1 text-xs text-slate-600">
                                        This section helps the employee and HR quickly review dates with late attendance for the selected month.
                                    </p>
                                </div>

                                <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-amber-700 ring-1 ring-amber-200">
                                    {{ $lateRecords->count() }} late day(s)
                                </span>
                            </div>

                            <div class="mt-3 overflow-x-auto">
                                <table class="w-full border-collapse text-xs">
                                    <thead>
                                        <tr class="bg-white text-left text-slate-600">
                                            <th class="border border-slate-300 px-2 py-2">Date</th>
                                            <th class="border border-slate-300 px-2 py-2">Day</th>
                                            <th class="border border-slate-300 px-2 py-2">Time In</th>
                                            <th class="border border-slate-300 px-2 py-2">Late</th>
                                            <th class="border border-slate-300 px-2 py-2">Remarks</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @forelse($lateRecords as $lateRecord)
                                            @php $lateDate = \Carbon\Carbon::parse($lateRecord->date); @endphp
                                            <tr class="bg-white">
                                                <td class="border border-slate-300 px-2 py-1">{{ $lateDate->format('M d, Y') }}</td>
                                                <td class="border border-slate-300 px-2 py-1">{{ $lateDate->format('D') }}</td>
                                                <td class="border border-slate-300 px-2 py-1">{{ $formatTime($lateRecord->time_in) ?: 'N/A' }}</td>
                                                <td class="border border-slate-300 px-2 py-1">Late</td>
                                                <td class="border border-slate-300 px-2 py-1">{{ $lateRecord->status ?: 'Late arrival' }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="border border-slate-300 px-2 py-3 text-center text-slate-500">
                                                    No late records for this selected month.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
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
                                                    @php $record = $dayRow['record']; @endphp
                                                    <tr>
                                                        <td>{{ $dayRow['day_number'] }}</td>
                                                        <td>{{ $record ? $formatTime($record->time_in) : '' }}</td>
                                                        <td>{{ $record ? $formatTime($record->am_out) : '' }}</td>
                                                        <td>{{ $record ? $formatTime($record->pm_in) : '' }}</td>
                                                        <td>{{ $record ? $formatTime($record->time_out) : '' }}</td>
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
