<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Travel Authority Report (TAR)</title>
    <style>
        @page { size: A4 portrait; margin: 0; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; color: #000; background: #fff; }
        body { font-family: "Times New Roman", Times, serif; font-size: 9pt; line-height: 1.2; }
        .pdf-page { width: 174mm; margin: 0 auto; padding-top: 14mm; }

        .header-table { width: 100%; margin: 0 0 4mm; border-collapse: collapse; }
        .header-logo-cell { width: 24mm; padding: 0 4mm 0 0; vertical-align: top; }
        .header-logo { display: block; width: 21mm; height: 21mm; object-fit: contain; }
        .header-copy { padding: 1.5mm 0 0; vertical-align: top; line-height: 1.05; }
        .agency-name { font-size: 11pt; font-weight: 400; }
        .regional-office { font-size: 10pt; font-weight: 400; }
        .office-address { margin-top: .8mm; font-size: 8pt; }

        .report-title { margin: 0 0 2.5mm; text-align: center; font-size: 11pt; font-weight: 700; text-decoration: underline; }
        .info-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .info-table th, .info-table td { border: .7pt solid #000; padding: 1.1mm 1.5mm; vertical-align: top; }
        .info-table th { width: 31mm; text-align: left; font-weight: 400; }
        .info-table .multirow { height: 13mm; }

        .report-section { margin-top: 4.5mm; page-break-inside: avoid; }
        .section-title { margin: 0 0 1.2mm; font-size: 8.5pt; font-weight: 700; }
        .section-body { min-height: 12mm; margin-left: 1.5mm; white-space: pre-wrap; overflow-wrap: break-word; }
        .section-body.compact { min-height: 9mm; }

        .attachments-box { width: 100%; border: .7pt solid #000; border-collapse: collapse; }
        .attachments-box td { width: 50%; padding: 1.6mm 2mm; vertical-align: middle; line-height: 3.4mm; }
        .check-box { display: inline-block; width: 3mm; height: 3mm; margin-right: 1.4mm; border: .7pt solid #000; text-align: center; font-size: 7pt; line-height: 2.6mm; vertical-align: middle; }

        .signature-stack { margin-top: 5mm; }
        .signature-block { width: 78mm; }
        .signature-block + .signature-block { margin-top: 9mm; }
        .signature-label { margin-bottom: 8mm; font-weight: 700; }
        .signature-image { display: block; width: 38mm; height: 10mm; margin: -7mm auto -1mm; object-fit: contain; }
        .signature-line { border-top: .7pt solid #000; padding-top: 1mm; text-align: center; font-weight: 700; text-transform: uppercase; }
        .signature-role { margin-top: .5mm; font-weight: 400; text-transform: none; }
    </style>
</head>
<body>
<div class="pdf-page">
    @php
        $employee = $travelOrder->employee;
        $employeeName = trim(collect([$employee?->firstname, $employee?->middlename, $employee?->lastname, $employee?->suffix])->filter()->implode(' ')) ?: 'N/A';
        $divisionHeadName = trim(collect([$divisionHead?->firstname, $divisionHead?->middlename, $divisionHead?->lastname, $divisionHead?->suffix])->filter()->implode(' ')) ?: 'DIVISION HEAD';
        $selectedAttachments = collect($travelOrder->tar_attachments ?: []);
        $attachmentOptions = [
            'photo_documentation' => 'photo documentation',
            'certificate' => 'copy of certificate of participation/attendance',
            'lecture_materials' => 'lecture material/s',
            'program_flow' => 'copy of program flow',
            'activity_materials' => 'activity material/s',
            'others' => 'others (specify)' . ($travelOrder->tar_other_attachment ? ': ' . $travelOrder->tar_other_attachment : ''),
        ];
        $attachmentRows = collect($attachmentOptions)->chunk(2);
    @endphp

    <table class="header-table"><tr>
        <td class="header-logo-cell"><img src="{{ $dmwLogoPath }}" alt="DMW Logo" class="header-logo"></td>
        <td class="header-copy"><div class="agency-name">DEPARTMENT OF MIGRANT WORKERS</div><div class="regional-office">REGIONAL OFFICE XI</div><div class="office-address">3rd Floor, NCCC Mall, Buhangin, Davao City</div></td>
    </tr></table>

    <h1 class="report-title">TRAVEL AUTHORITY REPORT (TAR)</h1>
    <table class="info-table">
        <tr><th>Name</th><td>{{ $employeeName }}</td></tr>
        <tr><th>Activity</th><td>{{ \Illuminate\Support\Str::title($travelOrder->tar_activity ?: $travelOrder->purpose ?: 'N/A') }}</td></tr>
        <tr><th>Date</th><td>{{ $travelOrder->tar_activity_date?->format('F d, Y') ?: $travelOrder->travel_date_end?->format('F d, Y') ?: 'N/A' }}</td></tr>
        <tr><th>Venue</th><td>{{ \Illuminate\Support\Str::title($travelOrder->tar_venue ?: $travelOrder->places_of_travel ?: 'N/A') }}</td></tr>
        <tr><th>Participants<br>Present</th><td class="multirow">{{ $travelOrder->tar_participants_present ?: 'N/A' }}</td></tr>
        <tr><th>Participants<br>of DMW</th><td class="multirow">{{ $travelOrder->tar_dmw_participants ?: $employeeName }}</td></tr>
    </table>

    @foreach([
        ['I. Objective/s of the Activity', $travelOrder->tar_objectives],
        ['II. Facts/Highlights of the Activity', $travelOrder->tar_highlights],
        ['III. Result/s of the Activity', $travelOrder->tar_results],
        ['IV. Recommendations/Resolutions', $travelOrder->tar_recommendations],
        ['V. Commitments/Requested Action from DMW', $travelOrder->tar_commitments],
    ] as [$heading, $content])
        <section class="report-section"><h2 class="section-title">{{ $heading }}</h2><div class="section-body {{ $loop->last ? 'compact' : '' }}">{{ $content ?: 'N/A' }}</div></section>
    @endforeach

    <section class="report-section">
        <h2 class="section-title">VI. Attachments (check applicable)</h2>
        <table class="attachments-box">
            @foreach($attachmentRows as $row)
                <tr>
                    @foreach($row as $value => $label)<td><span class="check-box">{{ $selectedAttachments->contains($value) ? 'X' : '' }}</span>{{ $label }}</td>@endforeach
                    @if($row->count() < 2)<td></td>@endif
                </tr>
            @endforeach
        </table>
    </section>

    <div class="signature-stack">
        <div class="signature-block">
            <div class="signature-label">Prepared by:</div>
            @if($employee?->effective_signature_url)<img src="{{ $employee->effective_signature_url }}" alt="Prepared by signature" class="signature-image">@endif
            <div class="signature-line">{{ $employeeName }}</div>
        </div>
        <div class="signature-block">
            <div class="signature-label">Approved by: (Division Head)</div>
            @if($travelOrder->tar_chief_status === 'approved' && $divisionHead?->effective_signature_url)<img src="{{ $divisionHead->effective_signature_url }}" alt="Division head signature" class="signature-image">@endif
            <div class="signature-line">{{ $divisionHeadName }}<div class="signature-role">Division Head</div></div>
        </div>
    </div>
</div>
</body>
</html>
