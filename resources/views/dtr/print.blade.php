<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('Daily Time Record') }} - {{ $selectedMonth }}/{{ $selectedYear }}</title>

    @php
        $user = auth()->user();
        $employee = $dtrEmployee ?? $user->employee;
        $selectedDate = \Carbon\Carbon::create((int) $selectedYear, (int) $selectedMonth, 1);
        $recordsByDate = collect($dtrRecords ?? [])->groupBy(fn ($record) => \Carbon\Carbon::parse($record->date)->format('Y-m-d'));
        $attendanceEventsByDate = collect($attendanceEventsByDate ?? []);
        $formatTime = fn ($time) => $time ? \Carbon\Carbon::parse($time)->format('h:i A') : '';
        $displayEmployeeNo = $employee?->employee_no ?? ($employee?->id ? 'EMP-' . str_pad((string) $employee->id, 4, '0', STR_PAD_LEFT) : 'N/A');
        $printEmployeeName = trim(($employee?->lastname ?? '') . ', ' . ($employee?->firstname ?? '') . ' ' . ($employee?->middle_initial ?? ''));
        $printEmployeeName = trim($printEmployeeName, ', ') ?: $user->name;
        $printMonthPeriod = $selectedDate->format('F 1') . ' - ' . $selectedDate->copy()->endOfMonth()->format('F j, Y');
        $regionalDirector = \App\Models\Employee::query()
            ->where('account_role', 'regionaldirector')
            ->orderBy('lastname')
            ->first();
        $regionalDirectorName = $regionalDirector
            ? trim(($regionalDirector->firstname ?? '') . ' ' . ($regionalDirector->middlename ? mb_substr($regionalDirector->middlename, 0, 1) . '. ' : '') . ($regionalDirector->lastname ?? ''))
            : 'MARIA CAROLINA B. AGDAMAG';
        $undertimeMinutesForRecord = function ($record) {
            if (! $record) return 0;
            $minutes = 0;
            if ($record->am_out && \Carbon\Carbon::parse($record->am_out)->format('H:i:s') < '12:00:00') {
                $minutes += \Carbon\Carbon::parse($record->am_out)->diffInMinutes(\Carbon\Carbon::parse('12:00:00'));
            }
            if ($record->time_out && \Carbon\Carbon::parse($record->time_out)->format('H:i:s') < '17:00:00') {
                $minutes += \Carbon\Carbon::parse($record->time_out)->diffInMinutes(\Carbon\Carbon::parse('17:00:00'));
            }
            return $minutes;
        };
        $printDays = collect(range(1, 31))->map(function ($dayNumber) use ($selectedDate, $recordsByDate, $attendanceEventsByDate, $undertimeMinutesForRecord) {
            $day = $dayNumber <= $selectedDate->daysInMonth ? $selectedDate->copy()->day($dayNumber) : null;
            $record = $day ? $recordsByDate->get($day->format('Y-m-d'), collect())->first() : null;
            $approvedEvent = $day ? $attendanceEventsByDate->get($day->format('Y-m-d')) : null;
            $undertimeMinutes = $day && ! $day->isWeekend() && ! $approvedEvent ? $undertimeMinutesForRecord($record) : 0;

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
    @endphp

    <style>
        @page { size: A4 portrait; margin: 6mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #000; background: #fff; font-family: Arial, Helvetica, sans-serif; }
        .print-page { width: 100%; margin: 0; padding: 0; background: #fff; }
        .copy-grid { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .copy-grid > tbody > tr > .copy-cell { width: 49%; padding: 0; border: 0; vertical-align: top; }
        .copy-grid > tbody > tr > .copy-gap { width: 2%; padding: 0; border: 0; }
        .dtr-form-copy { width: auto; border: .25mm solid #000; padding: 2.6mm 2.3mm 3mm; font-size: 7.6pt; line-height: 1.05; }
        .title-small { font-size: 7.4pt; font-weight: 700; }
        .title { margin-top: 1mm; text-align: center; font-size: 10.5pt; font-weight: 700; letter-spacing: .4px; }
        .form-row { display: table; width: 100%; margin-top: 1mm; table-layout: fixed; }
        .form-cell { display: table-cell; vertical-align: bottom; white-space: nowrap; }
        .form-line { display: inline-block; min-height: 3.1mm; border-bottom: .2mm solid #000; text-align: center; font-weight: 700; vertical-align: bottom; overflow: hidden; white-space: nowrap; padding: 0 .3mm; }
        .log-table { width: 100%; margin-top: 1.3mm; border-collapse: collapse; table-layout: fixed; font-size: 6.8pt; }
        .log-table th, .log-table td { border: .2mm solid #000; height: 4.25mm; padding: .2mm .25mm; text-align: center; vertical-align: middle; font-weight: 400; overflow: hidden; }
        .log-table th { font-size: 5.7pt; font-weight: 700; white-space: nowrap; }
        .log-table .day-cell { width: 8%; }
        .log-table .time-cell { width: 18.5%; }
        .log-table .under-cell { width: 9%; }
        .log-table td.reference-cell { overflow: visible; white-space: normal; }
        .dtr-reference-value { display: inline-block; max-width: 100%; font-size: 5.2pt !important; font-weight: 700; line-height: .92 !important; white-space: nowrap; text-align: center; }
        .dtr-reference-value span { display: block; }
        .certification { margin-top: 2mm; font-size: 7pt; line-height: 1.12; text-align: justify; }
        .sign-line { margin: 7mm auto 0; width: 67%; min-height: 3.8mm; border-bottom: .2mm solid #000; text-align: center; font-weight: 700; }
        .verify-line { width: 100%; }
        .sign-caption { text-align: center; font-size: 6.8pt; }
    </style>
</head>
<body>
    <main class="print-page">
        <table class="copy-grid">
            <tbody>
                <tr>
                    @foreach([1, 2] as $copy)
                        <td class="copy-cell">
                            <section class="dtr-form-copy">
                    <div class="title-small">CIVIL SERVICE No. 48</div>
                    <div class="title">DAILY&nbsp;&nbsp;TIME&nbsp;&nbsp;RECORD</div>

                    <div class="form-row" style="margin-top:2.2mm;">
                        <span class="form-cell" style="width:32%;">FOR THE MONTH PERIOD:</span>
                        <span class="form-cell"><span class="form-line" style="width:100%; font-size:6.8pt;">{{ $printMonthPeriod }}</span></span>
                    </div>
                    <div class="form-row">
                        <span class="form-cell" style="width:11%;">Name:</span>
                        <span class="form-cell" style="width:55%;"><span class="form-line" style="width:100%; text-transform:uppercase; font-size:6.8pt;">{{ $printEmployeeName }}</span></span>
                        <span class="form-cell" style="width:13%; padding-left:.8mm;">Emp No:</span>
                        <span class="form-cell" style="width:21%;"><span class="form-line" style="width:100%; font-size:6.4pt;">{{ $displayEmployeeNo }}</span></span>
                    </div>
                    <div class="form-row">
                        <span class="form-cell" style="width:17%;">Position:</span>
                        <span class="form-cell" style="width:50%;"><span class="form-line" style="width:100%; font-size:6.8pt;">{{ $employee?->position ?? 'N/A' }}</span></span>
                        <span class="form-cell" style="width:18%; padding-left:1mm;">Official Time:</span>
                        <span class="form-cell" style="width:15%;"><span class="form-line" style="width:100%; font-size:6.8pt;">8-5</span></span>
                    </div>
                    <div class="form-row">
                        <span class="form-cell" style="width:12%;">Office:</span>
                        <span class="form-cell"><span class="form-line" style="width:100%; font-size:6.8pt;">{{ $employee?->division ?? 'DMW RO XI' }}</span></span>
                    </div>

                    <table class="log-table">
                        <thead>
                            <tr><th rowspan="2" class="day-cell">Day</th><th colspan="2">AM</th><th colspan="2">PM</th><th colspan="2">UNDERTIME</th></tr>
                            <tr><th class="time-cell">Arrival</th><th class="time-cell">Departure</th><th class="time-cell">Arrival</th><th class="time-cell">Departure</th><th class="under-cell">Hrs</th><th class="under-cell">Min</th></tr>
                        </thead>
                        <tbody>
                            @foreach($printDays as $dayRow)
                                @php
                                    $record = $dayRow['record'];
                                    $slotLabels = $dayRow['approved_event']['slot_labels'] ?? [];
                                @endphp
                                <tr>
                                    <td>{{ $dayRow['day_number'] }}</td>
                                    <td class="{{ ! $record?->time_in && isset($slotLabels['time_in']) ? 'reference-cell' : '' }}">@if($record?->time_in){{ $formatTime($record->time_in) }}@elseif(isset($slotLabels['time_in']))<x-dtr-reference :value="$slotLabels['time_in']" />@endif</td>
                                    <td class="{{ ! $record?->am_out && isset($slotLabels['am_out']) ? 'reference-cell' : '' }}">@if($record?->am_out){{ $formatTime($record->am_out) }}@elseif(isset($slotLabels['am_out']))<x-dtr-reference :value="$slotLabels['am_out']" />@endif</td>
                                    <td class="{{ ! $record?->pm_in && isset($slotLabels['pm_in']) ? 'reference-cell' : '' }}">@if($record?->pm_in){{ $formatTime($record->pm_in) }}@elseif(isset($slotLabels['pm_in']))<x-dtr-reference :value="$slotLabels['pm_in']" />@endif</td>
                                    <td class="{{ ! $record?->time_out && isset($slotLabels['time_out']) ? 'reference-cell' : '' }}">@if($record?->time_out){{ $formatTime($record->time_out) }}@elseif(isset($slotLabels['time_out']))<x-dtr-reference :value="$slotLabels['time_out']" />@endif</td>
                                    <td>{{ $dayRow['undertime_hours'] }}</td>
                                    <td>{{ $dayRow['undertime_minutes'] }}</td>
                                </tr>
                            @endforeach
                            <tr><td colspan="5" style="text-align:left; padding-left:1.5mm; font-weight:700;">TOTAL</td><td>{{ $totalUndertimeHours }}</td><td>{{ $totalUndertimeRemainder }}</td></tr>
                        </tbody>
                    </table>

                    <p class="certification">I CERTIFY on my honor that the above is true and correct report of the hours of work performed, record of which was made daily at the time of arrival and departure from office.</p>
                    <div class="sign-line verify-line">
                        @if($employee?->e_signature_absolute_path)
                            <img src="{{ $employee->e_signature_absolute_path }}" alt="Employee e-signature" style="max-height:8mm; max-width:45mm; object-fit:contain;">
                        @endif
                    </div>
                    <div class="sign-caption">Verified as to the prescribed office hours.</div>
                    <div class="sign-line" style="margin-top:6mm;">{{ $regionalDirectorName }}</div>
                    <div class="sign-caption">In Charge</div>
                            </section>
                        </td>
                        @if(! $loop->last)
                            <td class="copy-gap"></td>
                        @endif
                    @endforeach
                </tr>
            </tbody>
        </table>
    </main>
</body>
</html>
