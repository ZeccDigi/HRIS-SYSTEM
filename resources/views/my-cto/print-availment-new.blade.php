<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $ctoRequest->type === 'use' ? 'Compensatory Time-Off Availment Form' : 'Request to Earn Compensatory Overtime Credits' }}</title>
    <style>
        @page { size: A4 portrait; margin: 9mm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #fff; color: #000; font-family: Arial, Helvetica, sans-serif; }
        .paper { width: 192mm; margin: 0 auto; }
        .copy { width: 100%; page-break-inside: avoid; }
        .header { width: 100%; height: 23mm; border-collapse: collapse; }
        .header td { padding: 2mm 4mm; vertical-align: middle; }
        .header-logo { display: block; width: 14mm; height: 14mm; margin: 0 0 0 2mm; object-fit: contain; }
        .header-logo--bp { margin: 0 auto; }
        .agency { text-align: center; line-height: 1.15; }
        .agency-name, .form-title { font-size: 9pt; font-weight: 700; }
        .form-title { margin-top: .8mm; }
        .grid { width: 100%; border-collapse: collapse; font-size: 10pt; }
        .grid td { border: .25mm solid #000; padding: .45mm 1.25mm; vertical-align: top; }
        .grid .divider { width: 6mm; padding: 0; }
        .grid .entry-column { width: 90mm; }
        .label { font-size: 11pt; font-weight: 400; line-height: 1.05; }
        .entry-label { height: 5.7mm; }
        .entry-value { height: 8.6mm; font-size: 10.5pt; font-weight: 700; text-align: center; vertical-align: middle !important; }
        .grid .work-description { white-space: pre-line; overflow-wrap: break-word; font-size: 10.5pt; }
        .earned-as-of { display: inline-block; min-width: 27mm; border-bottom: .22mm solid #000; text-align: center; font-size: 10pt; }
        .signature-cell { height: 17.2mm; text-align: center; vertical-align: bottom !important; }
        .signature-image { display: block; max-width: 35mm; max-height: 8mm; margin: 0 auto .5mm; }
        .signature-line { width: 69%; margin: .5mm auto 1mm; border-top: .25mm solid #000; }
        .signature-name { font-size: 10pt; font-weight: 700; text-transform: uppercase; }
        .signature-role, .signature-caption { font-size: 11pt; font-weight: 400; line-height: 1.05; }
        .approval-label { text-align: center; font-size: 11pt; font-weight: 400; }
        .approval-cell { height: 26mm; text-align: center; }
        .approval-space { height: 12mm; }
        .footer-band { height: 5.5mm; border: .25mm solid #000; border-top: 0; }
        .file-label { padding-top: .8mm; text-align: right; font-size: 9.5pt; font-weight: 700; }
        .print-copy + .print-copy { margin-top: 3mm; }
        .print-document .paper { padding: 3mm 0; }
        @media print { .paper { padding: 3mm 0; } }
    </style>
</head>
<body class="{{ ($isPrintDocument ?? false) ? 'print-document' : 'preview-document' }}">
@php
    $isPrintDocument = $isPrintDocument ?? false;
    $employee = $ctoRequest->employee;
    $middleInitial = filled($employee?->middlename) ? ' '.mb_substr($employee->middlename, 0, 1).'.' : '';
    $employeeName = trim(collect([$employee?->firstname, $middleInitial, $employee?->lastname])->filter()->implode(' '));
    $officeDivision = trim((string) ($employee?->division ?? '')) ?: 'N/A';
    $dateFiled = $ctoRequest->created_at?->format('F j, Y') ?? 'N/A';
    $isCtoAvailment = $ctoRequest->type === 'use';
    $formTitle = $isCtoAvailment ? 'COMPENSATORY TIME-OFF (CTO) AVAILMENT FORM' : 'REQUEST TO EARN COC';
    $earnedAsOf = $dateFiled;
    $ctoDate = $ctoRequest->date_start && $ctoRequest->date_end && ! $ctoRequest->date_start->isSameDay($ctoRequest->date_end) ? $ctoRequest->date_start->format('F j, Y').' to '.$ctoRequest->date_end->format('F j, Y') : ($ctoRequest->date_start?->format('F j, Y') ?? 'N/A');
    $formatHours = static fn ($value) => rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.');
    $hoursApplied = $isCtoAvailment ? $formatHours($ctoRequest->hours ?? 0) : null;
    $earnedHours = $ctoRequest->cto_balance_before ?? $employee?->cto_balance ?? 0;
    $remainingBalance = $isCtoAvailment ? ($ctoRequest->cto_balance_after ?? max(0, (float) $earnedHours - (float) $ctoRequest->hours)) : null;
    $formatApprover = static function ($approver): string { if (! $approver) { return ''; } return trim(collect([$approver->firstname, filled($approver->middlename) ? mb_substr($approver->middlename, 0, 1).'.' : null, $approver->lastname])->filter()->implode(' ')); };
    $hrName = $formatApprover($ctoRequest->hrstaff);
    $chiefName = $formatApprover($ctoRequest->chief);
    $directorName = $formatApprover($ctoRequest->regionalDirector);
    $copies = $isPrintDocument ? ['EMPLOYEE FILE', 'HR/FAD FILE'] : [null];
@endphp
<main id="cto-preview-sheet" class="paper">
    @foreach($copies as $fileLabel)
        <section class="{{ $isPrintDocument ? 'print-copy' : 'preview-copy' }}">
            <article class="copy" aria-label="{{ $formTitle }}">
                <table class="header" role="presentation"><tr><td style="width:27mm"><img class="header-logo" src="{{ $dmwLogoPath }}" alt="Department of Migrant Workers"></td><td class="agency"><div class="agency-name">DEPARTMENT OF MIGRANT WORKERS</div><div class="form-title">{{ $formTitle }}</div></td><td style="width:27mm"><img class="header-logo header-logo--bp" src="{{ $bagongPilipinasLogoPath }}" alt="Bagong Pilipinas"></td></tr></table>
                <table class="grid">
                    <tr><td class="entry-label entry-column"><div class="label">NAME OF EMPLOYEE</div></td><td class="divider" aria-hidden="true"></td><td class="entry-label entry-column"><div class="label">OFFICE/DIVISION</div></td></tr>
                    <tr><td class="entry-value">{{ $employeeName ?: 'N/A' }}</td><td class="divider" aria-hidden="true"></td><td class="entry-value">{{ $officeDivision }}</td></tr>
                    @if($isCtoAvailment)
                    <tr><td class="entry-label"><div class="label">DATE FILED</div></td><td class="divider" aria-hidden="true"></td><td><div class="label earned-label">TOTAL COCs EARNED as of <span class="earned-as-of">{{ $earnedAsOf }}</span></div></td></tr>
                    <tr><td class="entry-value entry-value--date">{{ $dateFiled }}</td><td class="divider" aria-hidden="true"></td><td class="entry-value entry-value--date">{{ $earnedHours !== null ? $formatHours($earnedHours).' hour'.((float) $earnedHours === 1.0 ? '' : 's') : '' }}</td></tr>
                    <tr><td class="entry-label"><div class="label">DATE OF CTO | NO. OF HOURS APPLIED</div></td><td class="divider" aria-hidden="true"></td><td class="entry-label"><div class="label">COC REMAINING BALANCE (less this availment)</div></td></tr>
                    <tr><td class="entry-value entry-value--date">{{ $isCtoAvailment ? $ctoDate.' | '.$hoursApplied.' hour'.((float) $hoursApplied === 1.0 ? '' : 's') : '' }}</td><td class="divider" aria-hidden="true"></td><td class="entry-value entry-value--date">{{ $isCtoAvailment ? $formatHours($remainingBalance).' hour'.((float) $remainingBalance === 1.0 ? '' : 's') : '' }}</td></tr>
                    @else
                        <tr><td class="entry-label"><div class="label">DATE FILED</div></td><td class="divider" aria-hidden="true"></td><td class="entry-label"><div class="label">Overtime Date</div></td></tr>
                        <tr><td class="entry-value entry-value--date">{{ $dateFiled }}</td><td class="divider" aria-hidden="true"></td><td class="entry-value entry-value--date">{{ $ctoRequest->date_start?->format('F j, Y') }}</td></tr>
                        <tr><td class="entry-label"><div class="label">Hours Rendered</div></td><td class="divider" aria-hidden="true"></td><td class="entry-label"><div class="label">Description of Work Rendered</div></td></tr>
                        <tr><td class="entry-value entry-value--date">{{ $formatHours($ctoRequest->hours) }}</td><td class="divider" aria-hidden="true"></td><td class="work-description">{{ $ctoRequest->purpose }}</td></tr>
                    @endif
                    <tr><td class="signature-cell">@if($applicantSignaturePath)<img class="signature-image" src="{{ $applicantSignaturePath }}" alt="Employee signature">@endif<div class="signature-line"></div><div class="signature-caption">Signature of Employee</div></td><td class="divider" aria-hidden="true"></td><td class="signature-cell">@if($hrName)<div class="signature-name ">{{ strtoupper($hrName) }}</div>@endif<div class="signature-line"></div><div class="signature-caption">HRMO Signature Over Printed Name</div></td></tr>
                    <tr><td class="approval-cell"><div class="approval-label">RECOMMENDING APPROVAL:</div><div class="approval-space"></div>@if($chiefName)<div class="signature-name ">{{ strtoupper($chiefName) }}</div>@endif<div class="signature-line"></div><div class="signature-role">Division Chief</div></td><td class="divider" aria-hidden="true"></td><td class="approval-cell"><div class="approval-label">APPROVED:</div><div class="approval-space"></div>@if($directorName)<div class="signature-name ">{{ strtoupper($directorName) }}</div>@endif<div class="signature-line"></div><div class="signature-role">Director/Head, B/S/O</div></td></tr>
                </table>
                <div class="footer-band" aria-hidden="true"></div>
            </article>
            @if($fileLabel)<div class="file-label">{{ $fileLabel }}</div>@endif
        </section>
    @endforeach
</main>
</body>
</html>
