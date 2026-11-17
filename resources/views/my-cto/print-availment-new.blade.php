<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $ctoRequest->type === 'use' ? 'Compensatory Time-Off Availment Form' : 'Request to Earn Compensatory Overtime Credits' }}</title>
    <style>
        @page { size: A4 portrait; margin: 9mm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #fff; color: #000; font-family: Calibri, Carlito, Arial, sans-serif; }
        .paper { width: 192mm; margin: 0 auto; }
        .copy { width: 100%; border: .45mm solid #000; }
        .header { width: 100%; border-collapse: collapse; }
        .header td { padding: 1.6mm 3mm 1.2mm; vertical-align: middle; }
        .header-logo { display: block; width: 13.5mm; height: 13.5mm; margin: 0 auto; object-fit: contain; }
        .header-logo--bp { width: 13mm; height: 13mm; }
        .agency { text-align: center; line-height: 1.1; }
        .agency-name { font-size: 10pt; font-weight: 700; letter-spacing: .05mm; }
        .form-title { margin-top: 1mm; font-size: 10pt; font-weight: 700; }
        .grid { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 8pt; }
        .grid td { border: .22mm solid #000; padding: .55mm 1.5mm; vertical-align: top; }
        .grid td:last-child { border-left: .7mm double #000; }
        .label { font-size: 7.15pt; font-weight: 700; text-transform: uppercase; color: #303030; }
        .entry-label { height: 3.8mm; }
        .entry-value { height: 6.8mm; padding-top: .9mm !important; font-size: 8.3pt; font-weight: 700; text-align: center; text-transform: uppercase; }
        .entry-value--date { font-size: 8.25pt; text-transform: none; }
        .grid .work-description { border-left: .22mm solid #000; white-space: pre-line; overflow-wrap: break-word; }
        .earned-label { display: flex; align-items: baseline; gap: 1.3mm; white-space: nowrap; }
        .earned-as-of { display: inline-block; min-width: 27mm; border-bottom: .22mm solid #000; text-align: center; font-size: 7.6pt; font-weight: 700; text-transform: none; }
        .signature-cell { height: 12mm; text-align: center; vertical-align: bottom !important; }
        .signature-image { display: block; max-width: 35mm; max-height: 8mm; margin: 1mm auto .5mm; }
        .signature-line { width: 72%; margin: 5.6mm auto 1mm; border-top: .22mm solid #000; }
        .signature-image + .signature-line { margin-top: 0; }
        .signature-name { font-size: 7.45pt; font-weight: 700; text-transform: uppercase; }
        .signature-name--above { margin: 2mm auto 0; }
        .signature-line--after-name { margin-top: 1mm; }
        .signature-role, .signature-caption { margin-top: .35mm; font-size: 6.8pt; }
        .approval-cell { height: 15mm; text-align: center; vertical-align: bottom !important; }
        .approval-label { font-size: 7.1pt; font-weight: 700; text-transform: uppercase; text-align: left; }
        .file-label { padding: 1mm 1mm 0; text-align: center; font-size: 7.2pt; font-weight: 700; }
        .print-copy + .print-copy { margin-top: 2mm; }
        @media screen { .preview-document { background: #fff; padding: 0; } .preview-document .paper { background: #fff; box-shadow: none; } .preview-copy { min-height: 0; } }
        .print-document, .print-document .paper { background: #fff !important; box-shadow: none !important; }
        .print-document .paper { padding: 20mm 0 20mm; }
        @media print { html, body { width: 210mm; height: 297mm; overflow: hidden; background: #fff; } .paper { width: 192mm; margin: 0 auto; padding: 20mm 0; } .copy { break-inside: avoid; page-break-inside: avoid; } }
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
    $formTitle = $isCtoAvailment ? 'COMPENSATORY TIME-OFF (CTO) AVAILMENT FORM' : 'REQUEST TO EARN COMPENSATORY OVERTIME CREDITS (COC)';
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
                <table class="grid"><colgroup><col style="width:50%"><col style="width:50%"></colgroup>
                    <tr><td class="entry-label"><div class="label">Name of Employee</div></td><td class="entry-label"><div class="label">Office / Division</div></td></tr>
                    <tr><td class="entry-value">{{ $employeeName ?: 'N/A' }}</td><td class="entry-value">{{ $officeDivision }}</td></tr>
                    @if($isCtoAvailment)
                    <tr><td class="entry-label"><div class="label">Date Filed</div></td><td><div class="label earned-label">Total COCs Earned as of <span class="earned-as-of">{{ $earnedAsOf }}</span></div></td></tr>
                    <tr><td class="entry-value entry-value--date">{{ $dateFiled }}</td><td class="entry-value entry-value--date">{{ $earnedHours !== null ? $formatHours($earnedHours).' hour'.((float) $earnedHours === 1.0 ? '' : 's') : '' }}</td></tr>
                    <tr><td class="entry-label"><div class="label">Date of CTO &nbsp; | &nbsp; No. of Hours Applied</div></td><td class="entry-label"><div class="label">COC Remaining Balance (less this availment)</div></td></tr>
                    <tr><td class="entry-value entry-value--date">{{ $isCtoAvailment ? $ctoDate.' | '.$hoursApplied.' hour'.((float) $hoursApplied === 1.0 ? '' : 's') : '' }}</td><td class="entry-value entry-value--date">{{ $isCtoAvailment ? $formatHours($remainingBalance).' hour'.((float) $remainingBalance === 1.0 ? '' : 's') : '' }}</td></tr>
                    @else
                        <tr><td class="entry-label"><div class="label">Date Filed</div></td><td class="entry-label"><div class="label">Overtime Date</div></td></tr>
                        <tr><td class="entry-value entry-value--date">{{ $dateFiled }}</td><td class="entry-value entry-value--date">{{ $ctoRequest->date_start?->format('F j, Y') }}</td></tr>
                        <tr><td class="entry-label"><div class="label">Hours Rendered</div></td><td class="entry-label"><div class="label">Description of Work Rendered</div></td></tr>
                        <tr><td class="entry-value entry-value--date">{{ $formatHours($ctoRequest->hours) }}</td><td class="work-description">{{ $ctoRequest->purpose }}</td></tr>
                    @endif
                    <tr><td class="signature-cell">@if($applicantSignaturePath)<img class="signature-image" src="{{ $applicantSignaturePath }}" alt="Employee signature">@endif<div class="signature-line"></div><div class="signature-caption">Signature of Employee</div></td><td class="signature-cell">@if($hrName)<div class="signature-name signature-name--above">{{ strtoupper($hrName) }}</div>@endif<div class="signature-line {{ $hrName ? 'signature-line--after-name' : '' }}"></div><div class="signature-caption">HRMO Signature Over Printed Name</div></td></tr>
                    <tr><td class="approval-cell"><div class="approval-label">Recommending Approval:</div>@if($chiefName)<div class="signature-name signature-name--above">{{ strtoupper($chiefName) }}</div>@endif<div class="signature-line {{ $chiefName ? 'signature-line--after-name' : '' }}"></div><div class="signature-role">Division Chief</div></td><td class="approval-cell"><div class="approval-label">Approved:</div>@if($directorName)<div class="signature-name signature-name--above">{{ strtoupper($directorName) }}</div>@endif<div class="signature-line {{ $directorName ? 'signature-line--after-name' : '' }}"></div><div class="signature-role">Director/Head, B/S/O</div></td></tr>
                </table>
            </article>
            @if($fileLabel)<div class="file-label">{{ $fileLabel }}</div>@endif
        </section>
    @endforeach
</main>
</body>
</html>
