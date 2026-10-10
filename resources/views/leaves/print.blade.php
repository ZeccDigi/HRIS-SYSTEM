<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Application for Leave - {{ $leaveRequest->employee?->lastname ?? 'Employee' }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 4mm;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 210mm;
            margin: 0;
            padding: 0;
        }

        body {
            background: #e9eef5;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9.4pt;
            line-height: 1.12;
        }

        .print-toolbar {
            position: sticky;
            top: 0;
            z-index: 5;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12mm;
            width: 196mm;
            margin: 0 auto;
            padding: 7mm 0 5mm;
        }

        .print-toolbar a,
        .print-toolbar button {
            display: inline-flex;
            width: 48mm;
            height: 12mm;
            align-items: center;
            justify-content: center;
            border-radius: 3mm;
            border: 0.25mm solid #bfdbfe;
            background: #fff;
            color: #1e40af;
            cursor: pointer;
            font: 700 10pt Arial, Helvetica, sans-serif;
            letter-spacing: 0.18em;
            text-decoration: none;
            text-transform: uppercase;
        }

        .print-toolbar button {
            border-color: #1d4ed8;
            background: #1d4ed8;
            color: #fff;
        }

        .leave-form {
            width: 200mm;
            min-height: 286mm;
            margin: 0 auto 8mm;
            background: #fff;
            overflow: visible;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        td,
        th {
            padding: 0.9mm 1.05mm;
            vertical-align: top;
            font-weight: 400;
        }

        .thin-t { border-top: 0.2mm solid #000; }
        .thin-r { border-right: 0.2mm solid #000; }
        .thin-b { border-bottom: 0.2mm solid #000; }
        .thin-l { border-left: 0.2mm solid #000; }
        .double-t { border-top: 0.55mm double #000; }
        .double-b { border-bottom: 0.55mm double #000; }

        .center { text-align: center; }
        .right { text-align: right; }
        .middle { vertical-align: middle; }
        .bottom { vertical-align: bottom; }
        .bold { font-weight: 700; }
        .italic { font-style: italic; }
        .upper { text-transform: uppercase; }
        .nowrap { white-space: nowrap; }
        .small { font-size: 8pt; }
        .tiny { font-size: 5.2pt; line-height: 1.08; }
        .value {
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .title-row {
            height: 13mm;
            font-size: 17pt;
            font-weight: 700;
            text-align: center;
            vertical-align: bottom;
            padding-bottom: 2.6mm;
        }

        .header-table {
            height: 30mm;
        }

        .header-left {
            position: relative;
            display: flex;
            align-items: flex-start;
            justify-content: center;
            height: 30mm;
            padding-top: 13.8mm;
        }

        .form-number {
            position: absolute;
            left: 3mm;
            top: 4mm;
            font-size: 8pt;
            font-weight: 700;
            font-style: italic;
            line-height: 1.2;
        }

        .header-logo {
            width: auto;
            height: 13mm;
            max-width: 13mm;
            object-fit: contain;
            aspect-ratio: 1 / 1;
        }

        .letterhead {
            height: 30mm;
            text-align: center;
            vertical-align: top;
            padding-top: 13.8mm;
            font-size: 9.2pt;
            font-weight: 700;
            line-height: 1.15;
        }

        .stamp-holder {
            height: 30mm;
            vertical-align: top;
            padding-top: 13.8mm;
        }

        .stamp-box {
            display: flex;
            width: 29mm;
            height: 9mm;
            margin: 0 auto;
            align-items: center;
            justify-content: center;
            border: 0.2mm dashed #aaa;
            font-size: 5.4pt;
            text-align: center;
        }

        .employee-info td {
            height: 6.4mm;
            border-bottom: 0.2mm solid #000;
            font-size: 9pt;
        }

        .employee-info .filing-row td {
            height: 8.4mm;
            padding-top: 1.2mm;
            padding-bottom: 1.2mm;
            vertical-align: middle;
        }

        .employee-label-row td {
            border-bottom: 0;
        }

        .employee-info {
            border-top: 0.35mm solid #000;
            border-left: 0.35mm solid #000;
            border-right: 0.35mm solid #000;
        }

        .field-row td {
            height: 6.6mm;
        }

        .field-label {
            display: inline-block;
            margin-right: 1.6mm;
        }

        .date-position-salary {
            display: grid;
            grid-template-columns: minmax(0, 34fr) minmax(0, 38fr) minmax(0, 28fr);
            column-gap: 2mm;
            align-items: end;
            white-space: nowrap;
        }

        .date-position-salary .line {
            width: 100%;
            min-width: 0;
        }

        .field-group {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr);
            gap: 1.2mm;
            align-items: end;
            min-width: 0;
        }

        .line {
            display: inline-block;
            min-height: 3.7mm;
            border-bottom: 0.2mm solid #000;
            text-align: center;
            vertical-align: bottom;
        }

        .name-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 0.45fr;
            gap: 2mm;
            align-items: end;
            height: 100%;
        }

        .section-title {
            height: 6mm;
            border-left: 0.35mm solid #000;
            border-right: 0.35mm solid #000;
            border-top: 0.55mm double #000;
            border-bottom: 0.55mm double #000;
            font-size: 10pt;
            font-weight: 700;
            text-align: center;
            vertical-align: middle;
        }

        .section-title-light-top {
            border-top: 0.2mm solid #000;
        }

        .two-col > tbody > tr > td {
            width: 50%;
            border-right: 0.2mm solid #000;
        }

        .two-col > tbody > tr > td:last-child {
            border-right: 0;
        }

        .subhead {
            height: 6mm;
            border-bottom: 0.2mm solid #000;
            font-size: 9pt;
            vertical-align: middle;
        }

        .application-table > tbody > tr > td {
            height: 83mm;
            padding: 1.7mm 2.35mm 1.2mm;
            font-size: 10pt;
        }

        .application-table,
        .workday-table,
        .action-table,
        .approval-table {
            border-left: 0.35mm solid #000;
            border-right: 0.35mm solid #000;
        }

        .application-table .subhead {
            height: 5.2mm;
            border-bottom: 0;
            font-size: 10pt;
        }

        .application-table > tbody > tr > td:first-child {
            width: 57%;
        }

        .application-table > tbody > tr > td:last-child {
            width: 43%;
        }

        .workday-table > tbody > tr > td:first-child,
        .action-table > tbody > tr > td:first-child,
        .approval-table > tbody > tr > td:first-child {
            width: 57%;
        }

        .workday-table > tbody > tr > td:last-child,
        .action-table > tbody > tr > td:last-child,
        .approval-table > tbody > tr > td:last-child {
            width: 43%;
        }

        .leave-option {
            display: flex;
            gap: 2.35mm;
            align-items: flex-start;
            margin-bottom: 1.08mm;
            line-height: 1.12;
            font-size: 10pt;
        }

        .leave-option > span:last-child {
            display: block;
            max-width: 108mm;
        }

        .leave-option .tiny {
            font-size: 6.2pt;
            line-height: 1;
            white-space: nowrap;
        }

        .box {
            display: inline-flex;
            width: 2.75mm;
            height: 2.75mm;
            flex: 0 0 2.75mm;
            align-items: center;
            justify-content: center;
            margin-top: 0.05mm;
            border: 0.22mm solid #000;
            font-size: 7pt;
            font-weight: 800;
            line-height: 1;
        }

        .detail-block {
            min-height: 12.4mm;
            margin-bottom: 1.15mm;
            line-height: 1.16;
            font-size: 10pt;
        }

        .detail-block.compact {
            min-height: 8.8mm;
        }

        .detail-title {
            margin-bottom: 0.95mm;
            font-style: italic;
        }

        .detail-block .box {
            margin-right: 0;
        }

        .detail-row {
            display: flex;
            align-items: flex-end;
            gap: 2.15mm;
            min-height: 4mm;
            white-space: nowrap;
        }

        .detail-row .box {
            margin-bottom: 0.25mm;
        }

        .detail-text {
            flex: 0 0 auto;
        }

        .detail-line {
            display: inline-block;
            flex: 1 1 auto;
            min-width: 18mm;
            height: 3.2mm;
            border-bottom: 0.2mm solid #000;
            font-weight: 700;
            text-align: left;
            padding: 0 0.8mm;
            overflow: hidden;
            white-space: nowrap;
        }

        .full-detail-line {
            display: block;
            width: 100%;
            height: 4mm;
            border-bottom: 0.2mm solid #000;
        }

        .detail-block > div:not(.detail-title) {
            min-height: 3.85mm;
        }

        .workday-table {
            border-top: 0.2mm solid #000;
        }

        .workday-table td {
            height: 27mm;
            padding: 1.25mm 2.2mm 1mm;
            font-size: 10pt;
            vertical-align: top;
        }

        .workday-table .line {
            min-height: 4mm;
        }

        .workday-label {
            line-height: 1;
            margin-bottom: 0.8mm;
        }

        .workday-value {
            display: block;
            width: 70mm;
            height: 5mm;
            margin: 0 auto 1.55mm;
            border-bottom: 0.2mm solid #000;
            font-weight: 700;
            line-height: 4.3mm;
            text-align: center;
            white-space: nowrap;
        }

        .inclusive-label {
            margin: 0 0 0.8mm 8mm;
            line-height: 1;
        }

        .workday-choice {
            display: flex;
            align-items: center;
            gap: 2.4mm;
            margin: 2mm 0 0 6mm;
            line-height: 1;
        }

        .signature-block {
            height: 13.5mm;
            padding-top: 6.5mm;
            text-align: center;
            font-size: 7.8pt;
        }

        .signature-block .signature-line {
            display: block;
            width: 78mm;
            min-height: 3.6mm;
            margin: 0 auto 0.4mm;
            border-bottom: 0.2mm solid #000;
        }

        .applicant-signature {
            display: block;
            max-width: 46mm;
            max-height: 8mm;
            margin: 0 auto 0.5mm;
            object-fit: contain;
        }

        .action-table > tbody > tr > td {
            height: 45mm;
            padding: 1.25mm 1.45mm;
            font-size: 7.8pt;
        }

        .credits-table {
            width: 86%;
            margin: 1.2mm auto 3mm;
        }

        .credits-table td {
            height: 4.7mm;
            border: 0.2mm solid #000;
            padding: 0.55mm 0.8mm;
            text-align: center;
            vertical-align: middle;
        }

        .recommendation-lines {
            display: block;
            width: 72mm;
            height: 4.4mm;
            border-bottom: 0.2mm solid #000;
            font-weight: 700;
            overflow-wrap: anywhere;
        }

        .recommendation-option {
            display: flex;
            align-items: flex-end;
            gap: 2.3mm;
            margin-left: 5mm;
            line-height: 1;
        }

        .recommendation-option.approval {
            margin-top: 3mm;
        }

        .recommendation-option.disapproval {
            margin-top: 2.6mm;
        }

        .recommendation-text {
            flex: 0 0 auto;
        }

        .recommendation-option .recommendation-lines {
            flex: 1 1 auto;
            width: auto;
            min-width: 33mm;
            height: 3.6mm;
        }

        .recommendation-extra-line {
            display: block;
            width: calc(100% - 10mm);
            height: 4.2mm;
            margin-left: 10mm;
            border-bottom: 0.2mm solid #000;
        }

        .officer-name {
            display: block;
            width: 82mm;
            margin: 2.7mm auto 0;
            border-bottom: 0.2mm solid #000;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
        }

        .officer-label {
            text-align: center;
            font-size: 8pt;
        }

        .approval-table {
            border-top: 0.55mm double #000;
            border-bottom: 0.35mm solid #000;
        }

        .approval-table td {
            height: 20mm;
            padding: 1.6mm 1.55mm;
            vertical-align: top;
        }

        .approved-row {
            margin-left: 8mm;
            display: flex;
            align-items: flex-end;
            gap: 2mm;
            line-height: 1;
        }

        .approved-row:first-of-type {
            margin-top: 1.4mm;
        }

        .approved-line {
            display: inline-block;
            width: 22mm;
            height: 3.8mm;
            border-bottom: 0.2mm solid #000;
            font-weight: 700;
            line-height: 3.3mm;
            text-align: center;
            white-space: nowrap;
        }

        .approval-line {
            display: block;
            width: 70mm;
            height: 4.2mm;
            border-bottom: 0.2mm solid #000;
            font-weight: 700;
        }

        .approval-table td.head-section {
            height: 24mm;
            padding: 0 0 1.2mm;
            border-left: 0.35mm solid #000;
            border-right: 0.35mm solid #000;
            border-bottom: 0.35mm solid #000;
            text-align: center;
            vertical-align: bottom;
            line-height: 1;
        }

        .head-section > * {
            position: relative;
            top: 0.25mm;
        }

        .head-spacer td {
            height: 5mm;
            padding: 0;
            border-left: 0.35mm solid #000;
            border-right: 0.35mm solid #000;
        }

        .head-name {
            display: block;
            width: 56mm;
            margin: 0 auto;
            border-bottom: 0.2mm solid #000;
            font-weight: 700;
            text-transform: uppercase;
        }

        @media print {
            .print-toolbar {
                display: none !important;
            }

            body {
                background: #fff;
            }

            .leave-form {
                margin: 0 auto;
                page-break-inside: avoid;
                break-inside: avoid;
            }
        }
    </style>
</head>
<body>
@php
    use Carbon\Carbon;
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;

    $employee = $leaveRequest->employee;
    $leaveType = trim((string) ($leaveRequest->leaveType?->name ?? ''));
    $leaveTypeLower = strtolower($leaveType);
    $reason = trim((string) $leaveRequest->reason);
    $reasonLower = strtolower($reason);
    $start = Carbon::parse($leaveRequest->start_date);
    $end = Carbon::parse($leaveRequest->end_date);
    $dateFiled = $leaveRequest->date_filed ? Carbon::parse($leaveRequest->date_filed)->format('F j, Y') : '';
    $inclusiveDates = $start->isSameDay($end)
        ? $start->format('F j, Y')
        : $start->format('M. j, Y') . ' - ' . $end->format('M. j, Y');
    $duration = (float) $leaveRequest->duration;
    $durationText = fmod($duration, 1.0) === 0.0 ? (string) (int) $duration : number_format($duration, 1);
    $year = $start->year;
    $employee?->ensureLeaveCredits($year);
    $credits = $employee?->leaveCredits ?? collect();
    $vacationCredit = $credits->first(fn ($credit) => str_contains(strtolower($credit->leaveType?->name ?? ''), 'vacation'));
    $sickCredit = $credits->first(fn ($credit) => str_contains(strtolower($credit->leaveType?->name ?? ''), 'sick'));
    $isSick = str_contains($leaveTypeLower, 'sick');
    $vacationCurrent = (float) ($vacationCredit?->balance ?? 0);
    $sickCurrent = (float) ($sickCredit?->balance ?? 0);
    $vacationLessValue = $isSick ? 0.0 : $duration;
    $sickLessValue = $isSick ? $duration : 0.0;
    $vacationLess = $vacationLessValue > 0 ? $durationText : '';
    $sickLess = $sickLessValue > 0 ? $durationText : '';
    $vacationEarned = number_format($vacationCurrent, 1);
    $sickEarned = number_format($sickCurrent, 1);
    $vacationBalance = number_format($vacationCurrent - $vacationLessValue, 1);
    $sickBalance = number_format($sickCurrent - $sickLessValue, 1);
    $formatName = fn ($person) => trim(($person?->firstname ?? '') . ' ' . ($person?->middlename ? Str::substr($person->middlename, 0, 1) . '. ' : '') . ($person?->lastname ?? ''));
    $chiefName = $formatName($leaveRequest->chief) ?: 'LOUIE JAY C. LOSARIA';
    $hrName = $formatName($leaveRequest->hrstaff) ?: 'MANILYN JOY P. VELITA';
    $directorName = $formatName($leaveRequest->regionalDirector) ?: 'MARIA CAROLINA B. AGDAMAG';
    $signatureUrl = $employee?->effective_signature_url ?? ($employee?->pdsGovId?->signature_path ? Storage::disk('public')->url($employee->pdsGovId->signature_path) : null);
    $isApproved = $leaveRequest->status === 'approved';
    $isRejected = $leaveRequest->status === 'rejected';
    $rejectReason = trim(collect([$leaveRequest->rd_remarks, $leaveRequest->hrstaff_remarks, $leaveRequest->chief_remarks, $leaveRequest->remarks])->filter()->implode(' '));
    $check = fn (bool $condition) => $condition ? '✓' : '';
    $typeCheck = fn (string $needle) => str_contains($leaveTypeLower, $needle);
    $isVacationLike = $typeCheck('vacation') || $typeCheck('special privilege');
    $isAbroad = str_contains($reasonLower, 'abroad');
    $isHospital = str_contains($reasonLower, 'hospital');
    $isMaster = str_contains($reasonLower, 'master');
    $isBar = str_contains($reasonLower, 'bar') || str_contains($reasonLower, 'board');
    $isMonetization = str_contains($reasonLower, 'monetization') || str_contains($leaveTypeLower, 'monetization');
    $isTerminal = str_contains($reasonLower, 'terminal') || str_contains($leaveTypeLower, 'terminal');
@endphp

@php
    $isPreview = request()->boolean('preview');
@endphp

@unless($isPreview)
    <div class="print-toolbar">
        <a href="{{ request()->routeIs('leave-applications.print') ? route('leave-applications.show', $leaveRequest) : route('leaves.show', $leaveRequest) }}" data-no-transition>Back</a>
        <button type="button" onclick="window.print()">Print Leave</button>
    </div>
@endunless

<main class="leave-form">
    <table class="header-table">
        <colgroup>
            <col style="width: 28%">
            <col style="width: 44%">
            <col style="width: 28%">
        </colgroup>
        <tbody>
            <tr>
                <td class="header-left">
                    <div class="form-number">Civil Service Form No. 6<br>Revised 2020</div>
                    <img class="header-logo" src="{{ asset('images/dmw.png') }}" alt="">
                </td>
                <td class="letterhead middle">
                    <div>Republic of the Philippines<br>Department of Migrant Workers<br>REGION XI</div>
                </td>
                <td class="stamp-holder middle"><span class="stamp-box">Stamp of Date of Receipt</span></td>
            </tr>
        </tbody>
    </table>

    <table>
        <tbody>
            <tr>
                <td class="title-row">APPLICATION FOR LEAVE</td>
            </tr>
        </tbody>
    </table>

    <table class="employee-info">
        <colgroup>
            <col style="width: 35%">
            <col style="width: 65%">
        </colgroup>
        <tbody>
            <tr class="employee-label-row">
                <td class="thin-r">1. OFFICE/DEPARTMENT</td>
                <td>2. NAME :
                    <span style="display:inline-block; width: 19mm; margin-left: 15mm;">(Last)</span>
                    <span style="display:inline-block; width: 27mm; margin-left: 9mm;">(First)</span>
                    <span>(Middle)</span>
                </td>
            </tr>
            <tr class="field-row">
                <td class="thin-r center value upper">{{ $employee?->division ?: 'DMW RO XI' }}</td>
                <td>
                    <div class="name-grid value upper">
                        <span>{{ $employee?->lastname }}</span>
                        <span>{{ $employee?->firstname }}</span>
                        <span>{{ $employee?->middlename ? Str::substr($employee->middlename, 0, 1) . '.' : '' }}</span>
                    </div>
                </td>
            </tr>
            <tr class="filing-row">
                <td colspan="2">
                    <div class="date-position-salary">
                        <div class="field-group">
                            <span>3. DATE OF FILING</span>
                            <span class="line value">{{ $dateFiled }}</span>
                        </div>
                        <div class="field-group">
                            <span>4. POSITION</span>
                            <span class="line value">{{ $employee?->position ?: 'N/A' }}</span>
                        </div>
                        <div class="field-group">
                            <span>5. SALARY</span>
                            <span class="line value">{{ $employee?->salary ?? 'N/A' }}</span>
                        </div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

    <table>
        <tbody>
            <tr><td class="section-title section-title-light-top">6. DETAILS OF APPLICATION</td></tr>
        </tbody>
    </table>

    <table class="two-col application-table">
        <colgroup>
            <col style="width: 57%">
            <col style="width: 43%">
        </colgroup>
        <tbody>
            <tr>
                <td>
                    <div class="subhead">6.A TYPE OF LEAVE TO BE AVAILED OF</div>
                    <div class="leave-option"><span class="box">{{ $check($typeCheck('vacation')) }}</span><span>Vacation Leave <span class="tiny">(Sec. 51, Rule XVI, Omnibus Rules Implementing E.O. No. 292)</span></span></div>
                    <div class="leave-option"><span class="box">{{ $check($typeCheck('mandatory') || $typeCheck('force')) }}</span><span>Mandatory/Forced Leave <span class="tiny">(Sec. 25, Rule XVI, Omnibus Rules Implementing E.O. No. 292)</span></span></div>
                    <div class="leave-option"><span class="box">{{ $check($typeCheck('sick')) }}</span><span>Sick Leave <span class="tiny">(Sec. 43, Rule XVI, Omnibus Rules Implementing E.O. No. 292)</span></span></div>
                    <div class="leave-option"><span class="box">{{ $check($typeCheck('maternity')) }}</span><span>Maternity Leave <span class="tiny">(R.A. No. 11210 / IRR issued by CSC, DOLE and SSS)</span></span></div>
                    <div class="leave-option"><span class="box">{{ $check($typeCheck('paternity')) }}</span><span>Paternity Leave <span class="tiny">(R.A. No. 8187 / CSC MC No. 71, s. 1998, as amended)</span></span></div>
                    <div class="leave-option"><span class="box">{{ $check($typeCheck('special privilege')) }}</span><span>Special Privilege Leave <span class="tiny">(Sec. 21, Rule XVI, Omnibus Rules Implementing E.O. No. 292)</span></span></div>
                    <div class="leave-option"><span class="box">{{ $check($typeCheck('solo parent')) }}</span><span>Solo Parent Leave <span class="tiny">(RA No. 8972 / CSC MC No. 8, s. 2004)</span></span></div>
                    <div class="leave-option"><span class="box">{{ $check($typeCheck('study')) }}</span><span>Study Leave <span class="tiny">(Sec. 68, Rule XVI, Omnibus Rules Implementing E.O. No. 292)</span></span></div>
                    <div class="leave-option"><span class="box">{{ $check($typeCheck('vawc')) }}</span><span>10-Day VAWC Leave <span class="tiny">(RA No. 9262 / CSC MC No. 15, s. 2005)</span></span></div>
                    <div class="leave-option"><span class="box">{{ $check($typeCheck('rehabilitation')) }}</span><span>Rehabilitation Privilege <span class="tiny">(Sec. 55, Rule XVI, Omnibus Rules Implementing E.O. No. 292)</span></span></div>
                    <div class="leave-option"><span class="box">{{ $check($typeCheck('women')) }}</span><span>Special Leave Benefits for Women <span class="tiny">(RA No. 9710 / CSC MC No. 25, s. 2010)</span></span></div>
                    <div class="leave-option"><span class="box">{{ $check($typeCheck('emergency') || $typeCheck('calamity')) }}</span><span>Special Emergency (Calamity) Leave <span class="tiny">(CSC MC No. 2, s. 2012, as amended)</span></span></div>
                    <div class="leave-option"><span class="box">{{ $check($typeCheck('adoption')) }}</span><span>Adoption Leave <span class="tiny">(R.A. No. 8552)</span></span></div>
                    <div style="margin-top: 3mm;"><span class="italic">Others:</span></div>
                    <div class="line value" style="width: 72mm;">{{ ! collect(['vacation','mandatory','force','sick','maternity','paternity','special privilege','solo parent','study','vawc','rehabilitation','women','emergency','calamity','adoption'])->contains(fn ($needle) => $typeCheck($needle)) ? $leaveType : '' }}</div>
                </td>
                <td>
                    <div class="subhead">6.B DETAILS OF LEAVE</div>
                    <div class="detail-block">
                        <div class="detail-title">In case of Vacation/Special Privilege Leave:</div>
                        <div class="detail-row"><span class="box">{{ $check($isVacationLike && ! $isAbroad) }}</span><span class="detail-text">Within the Philippines</span><span class="detail-line">{{ $isVacationLike && ! $isAbroad ? $reason : '' }}</span></div>
                        <div class="detail-row"><span class="box">{{ $check($isVacationLike && $isAbroad) }}</span><span class="detail-text">Abroad (Specify)</span><span class="detail-line">{{ $isVacationLike && $isAbroad ? $reason : '' }}</span></div>
                    </div>
                    <div class="detail-block compact">
                        <div class="detail-title">In case of Sick Leave:</div>
                        <div class="detail-row"><span class="box">{{ $check($isSick && $isHospital) }}</span><span class="detail-text">In Hospital (Specify Illness)</span><span class="detail-line">{{ $isSick && $isHospital ? $reason : '' }}</span></div>
                        <div class="detail-row"><span class="box">{{ $check($isSick && ! $isHospital) }}</span><span class="detail-text">Out Patient (Specify Illness)</span><span class="detail-line">{{ $isSick && ! $isHospital ? $reason : '' }}</span></div>
                        <span class="full-detail-line"></span>
                    </div>
                    <div class="detail-block compact">
                        <div class="detail-title">In case of Special Leave Benefits for Women:</div>
                        <div class="detail-row"><span class="detail-text">(Specify Illness)</span><span class="detail-line">{{ $typeCheck('women') ? $reason : '' }}</span></div>
                        <span class="full-detail-line"></span>
                    </div>
                    <div class="detail-block compact">
                        <div class="detail-title">In case of Study Leave:</div>
                        <div class="detail-row"><span class="box">{{ $check($typeCheck('study') && $isMaster) }}</span><span class="detail-text">Completion of Master's Degree</span></div>
                        <div class="detail-row"><span class="box">{{ $check($typeCheck('study') && $isBar) }}</span><span class="detail-text">BAR/Board Examination Review</span></div>
                    </div>
                    <div class="detail-block compact">
                        <div class="detail-title">Other purpose:</div>
                        <div class="detail-row"><span class="box">{{ $check($isMonetization) }}</span><span class="detail-text">Monetization of Leave Credits</span></div>
                        <div class="detail-row"><span class="box">{{ $check($isTerminal) }}</span><span class="detail-text">Terminal Leave</span></div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

    <table class="two-col workday-table">
        <colgroup>
            <col style="width: 57%">
            <col style="width: 43%">
        </colgroup>
        <tbody>
            <tr>
                <td>
                    <div class="workday-label">6.C&nbsp;&nbsp;NUMBER OF WORKING DAYS APPLIED FOR</div>
                    <span class="workday-value">{{ $durationText }}</span>
                    <div class="inclusive-label">INCLUSIVE DATES</div>
                    <span class="workday-value">{{ $inclusiveDates }}</span>
                </td>
                <td>
                    <div>6.D&nbsp;&nbsp;COMMUTATION</div>
                    <div class="workday-choice"><span class="box"></span>Not Requested</div>
                    <div class="workday-choice"><span class="box">{{ $check(true) }}</span>Requested</div>
                    <div class="signature-block">
                        <span class="signature-line">
                        @if($signatureUrl)
                            <img src="{{ $signatureUrl }}" class="applicant-signature" alt="Signature">
                        @endif
                        </span>
                        (Signature of Applicant)
                    </div>
                </td>
            </tr>
        </tbody>
    </table>

    <table>
        <tbody>
            <tr><td class="section-title">7. DETAILS OF ACTION ON APPLICATION</td></tr>
        </tbody>
    </table>

    <table class="two-col action-table">
        <colgroup>
            <col style="width: 57%">
            <col style="width: 43%">
        </colgroup>
        <tbody>
            <tr>
                <td>
                    <div>7.A CERTIFICATION OF LEAVE CREDITS</div>
                    <div class="center" style="margin-top: 3mm;">As of <span class="line" style="width: 40mm;">{{ now()->format('F j, Y') }}</span></div>
                    <table class="credits-table">
                        <tbody>
                            <tr>
                                <td></td>
                                <td>Vacation Leave</td>
                                <td>Sick Leave</td>
                            </tr>
                            <tr>
                                <td class="italic">Total Earned</td>
                                <td class="value">{{ $vacationEarned }}</td>
                                <td class="value">{{ $sickEarned }}</td>
                            </tr>
                            <tr>
                                <td class="italic">Less this application</td>
                                <td class="value">{{ $vacationLess }}</td>
                                <td class="value">{{ $sickLess }}</td>
                            </tr>
                            <tr>
                                <td class="italic">Balance</td>
                                <td class="value">{{ $vacationBalance }}</td>
                                <td class="value">{{ $sickBalance }}</td>
                            </tr>
                        </tbody>
                    </table>
                    <span class="officer-name">{{ $hrName }}</span>
                    <div class="officer-label">(Authorized Officer)</div>
                </td>
                <td>
                    <div>7.B RECOMMENDATION</div>
                    <div class="recommendation-option approval"><span class="box">{{ $check($leaveRequest->chief_status === 'approved' || $leaveRequest->hrstaff_status === 'approved' || $leaveRequest->rd_status === 'approved' || $isApproved) }}</span><span class="recommendation-text">For approval</span></div>
                    <div class="recommendation-option disapproval"><span class="box">{{ $check($leaveRequest->chief_status === 'rejected' || $leaveRequest->hrstaff_status === 'rejected' || $leaveRequest->rd_status === 'rejected' || $isRejected) }}</span><span class="recommendation-text">For disapproval due to</span><span class="recommendation-lines">{{ $isRejected ? $rejectReason : '' }}</span></div>
                    <span class="recommendation-extra-line"></span>
                    <span class="recommendation-extra-line"></span>
                    <span class="officer-name" style="margin-top: 5mm;">{{ $chiefName }}</span>
                    <div class="officer-label">(Authorized Officer)</div>
                </td>
            </tr>
        </tbody>
    </table>

    <table class="approval-table">
        <colgroup>
            <col style="width: 57%">
            <col style="width: 43%">
        </colgroup>
        <tbody>
            <tr>
                <td>
                    <div>7.C APPROVED FOR:</div>
                    <div class="approved-row"><span class="approved-line">{{ $isApproved && $leaveRequest->is_paid ? $durationText : '' }}</span><span>days with pay</span></div>
                    <div class="approved-row"><span class="approved-line">{{ $isApproved && ! $leaveRequest->is_paid ? $durationText : '' }}</span><span>days without pay</span></div>
                    <div class="approved-row"><span class="approved-line"></span><span>others (Specify)</span></div>
                </td>
                <td>
                    <div>7.D DISAPPROVED DUE TO:</div>
                    <span class="approval-line" style="margin-top: 1mm;">{{ $isRejected ? $rejectReason : '' }}</span>
                    <span class="approval-line"></span>
                    <span class="approval-line"></span>
                </td>
            </tr>
            <tr class="head-spacer">
                <td colspan="2"></td>
            </tr>
            <tr>
                <td colspan="2" class="head-section">
                    <span class="head-name">{{ $directorName }}</span>
                    <div>Head of Office</div>
                </td>
            </tr>
        </tbody>
    </table>
</main>

@if(request()->boolean('auto_print'))
    <script>
        window.addEventListener('load', () => {
            window.focus();
            window.print();
        });
    </script>
@endif
</body>
</html>
