<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate of COC Earned</title>
    <style>
        @page { size: A4 portrait; margin: 12mm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #fff; color: #000; font-family: Arial, Helvetica, sans-serif; font-size: 10pt; }
        .paper { width: 186mm; margin: 0 auto; padding: 8mm 0; }
        .header { width: 100%; height: 22mm; border-collapse: collapse; }
        .header td { vertical-align: middle; text-align: center; }
        .logo { width: 17mm; height: 17mm; object-fit: contain; }
        .heading { font-size: 10pt; font-weight: bold; line-height: 1.4; }
        .certificate { position: relative; height: 103mm; border: .3mm solid #000; }
        .entitlement { margin: 6mm 14mm 0; line-height: 1.6; }
        .name { display: inline-block; width: 86mm; border-bottom: .2mm solid #000; text-align: center; font-weight: bold; }
        .hours { display: inline-block; width: 38mm; border-bottom: .2mm solid #000; text-align: center; font-weight: bold; }
        .hours-caption { margin-left: 7mm; font-size: 9pt; }
        .head { position: absolute; top: 65mm; right: 30mm; width: 44mm; text-align: center; }
        .signature-name { min-height: 6mm; font-size: 9pt; font-weight: bold; }
        .signature-line { border-top: .2mm solid #000; padding-top: 1.5mm; }
        .dates { position: absolute; bottom: 5mm; left: 1.5mm; line-height: 1.7; }
        .date-line { display: inline-block; width: 42mm; border-bottom: .2mm solid #000; height: 4mm; }
        .ledger { margin-top: 10mm; border: .3mm solid #000; }
        .band { height: 5mm; border-bottom: .3mm solid #000; }
        .ledger-table { width: 100%; table-layout: fixed; border-collapse: collapse; font-size: 9pt; }
        .ledger-table th { text-align: center; vertical-align: middle; font-weight: bold; padding: 1.5mm 2mm; border-bottom: .3mm solid #000; }
        .ledger-table td { text-align: center; height: 8mm; padding: 1.8mm 2mm; vertical-align: middle; line-height: 1.3; border-bottom: .15mm solid #e5e7eb; overflow-wrap: break-word; }
        .ledger-table .numeric { text-align: center; font-variant-numeric: tabular-nums; }
        .ledger-body .ledger-table td:first-child { text-align: center; font-variant-numeric: tabular-nums; }
        .ledger-body { position: relative; height: 100mm; }
        .approval { position: absolute; bottom: 4mm; left: 1.5mm; width: 43mm; }
        .claimed { position: absolute; bottom: 4mm; right: 19mm; width: 43mm; }
        .signature-block { margin-top: 10mm; text-align: center; }
        .date-signature { margin-top: 9mm; text-align: center; }
    </style>
</head>
<body>
@php
    $employee = $ctoRequest->employee;
    $employeeName = trim(collect([$employee?->firstname, $employee?->middlename, $employee?->lastname, $employee?->suffix])->filter()->implode(' '));
    $creditsApproved = $ctoRequest->status === 'approved';
    $approved = $creditsApproved && (!$ctoRequest->issued_on || $ctoRequest->issued_on->toDateString() <= now('Asia/Manila')->toDateString());
    $director = $approved ? $ctoRequest->regionalDirector : null;
    $directorName = $director ? trim(collect([$director->firstname, $director->middlename, $director->lastname])->filter()->implode(' ')) : '';
    $monthly = app(\App\Services\CocCertificateService::class)->data($ctoRequest);
    $hours = rtrim(rtrim(number_format($monthly['hours'], 2, '.', ''), '0'), '.');
    $usages = $monthly['usages'];
@endphp
<main id="cto-preview-sheet" class="paper">
    <table class="header" role="presentation"><tr>
        <td style="width:39mm"><img class="logo" src="{{ $dmwLogoPath }}" alt="Department of Migrant Workers"></td>
        <td class="heading">DEPARTMENT OF MIGRANT WORKERS<br>CERTIFICATE OF COC EARNED</td>
        <td style="width:39mm"><img class="logo" src="{{ $bagongPilipinasLogoPath }}" alt="Bagong Pilipinas"></td>
    </tr></table>
    <section class="certificate">
        <div class="entitlement">
            <div style="text-align:center">This certificate entitles Mr./Ms. <span class="name">{{ $employeeName }}</span></div>
            <div>to <span class="hours">{{ $approved ? $hours : '' }}</span> of Compensatory Overtime Credits (COC).</div>
            <div class="hours-caption">(number of hours)</div>
        </div>
        <div class="head"><div class="signature-name">{{ $directorName }}</div><div class="signature-line">Head of Office</div></div>
        <div class="dates">Date Issued: <span class="date-line">{{ $approved ? $ctoRequest->issued_on?->format('M d, Y') : '' }}</span><br>Valid Until: &nbsp;<span class="date-line">{{ $approved ? $ctoRequest->valid_until?->format('M d, Y') : '' }}</span></div>
    </section>
    <section class="ledger">
        <div class="band"></div>
        <div class="ledger-body">
        <table class="ledger-table"><colgroup><col style="width:34%"><col style="width:17%"><col style="width:16%"><col style="width:20%"><col style="width:13%"></colgroup><thead><tr>
            <th style="width:34%">No. of Hours Earned<br>COCs Beginning Balance</th>
            <th style="width:17%">Date of CTO</th>
            <th class="numeric" style="width:16%">Used COCs</th>
            <th class="numeric" style="width:20%">Remaining COCs</th>
            <th style="width:13%">Remarks</th>
        </tr></thead><tbody>
                <tr><td>{{ $creditsApproved ? number_format($monthly['hours'], 2) : '--' }}</td><td>--</td><td class="numeric">{{ $creditsApproved ? '0.00' : '--' }}</td><td class="numeric">{{ $creditsApproved ? number_format($monthly['hours'], 2) : '--' }}</td><td>--</td></tr>
                @foreach($usages as $usage)
                    <tr><td>--</td><td>{{ $usage->date_start ? \Carbon\Carbon::parse($usage->date_start)->format('M d, Y') : '--' }}</td><td class="numeric">{{ number_format($usage->hours, 2) }}</td><td class="numeric">{{ number_format($usage->remaining_after, 2) }}</td><td>--</td></tr>
                @endforeach
            </tbody></table>
            <div class="approval">Approved by:<div class="signature-block"><div class="signature-name">{{ $directorName }}</div><div class="signature-line">Head of Office</div></div><div class="date-signature"><div class="signature-line">Date</div></div></div>
            <div class="claimed">Claimed:<div class="signature-block"><div class="signature-name">&nbsp;</div><div class="signature-line">HRMO</div></div><div class="date-signature"><div class="signature-line">Date</div></div></div>
        </div>
    </section>
</main>
</body>
</html>
