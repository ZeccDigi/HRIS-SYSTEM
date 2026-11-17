@php
    $employee = $travelOrder->employee;
    $employeeName = trim(($employee?->firstname ?? '') . ' ' . ($employee?->middlename ? strtoupper(substr($employee->middlename, 0, 1)).'. ' : '') . ($employee?->lastname ?? ''));
    $employeePosition = $employee?->position ?: '';
    $companions = $travelOrder->companions ?? collect();
    // The employee list on the TA form contains names only. Positions remain
    // in the Requesting Party signature section below.
    $nameLines = collect([trim($employeeName)])
        ->filter()
        ->values();

    foreach ($companions as $companion) {
        $companionName = trim(($companion->firstname ?? '') . ' ' . ($companion->middlename ? strtoupper(substr($companion->middlename, 0, 1)).'. ' : '') . ($companion->lastname ?? ''));
        $nameLines->push(trim($companionName));
    }

    $travelDate = $travelOrder->travel_date_start->isSameDay($travelOrder->travel_date_end)
        ? $travelOrder->travel_date_start->format('d F Y')
        : $travelOrder->travel_date_start->format('d F Y') . ' - ' . $travelOrder->travel_date_end->format('d F Y');
    $requestingPartyName = strtoupper($employeeName ?: '');
    $requestingSignaturePath = $requestingSignaturePath ?? $employee?->effective_signature_url;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Request Form for Travel Authority</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 8mm 18mm 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10.5pt;
            line-height: 1.12;
        }

        .ta-page {
            width: 174mm;
            margin: 0 auto;
        }

        .ta-header {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
            margin-bottom: 7mm;
        }

        .ta-header td {
            border: 0;
            padding: 0;
            vertical-align: middle;
        }

        .ta-logo-cell {
            width: 30mm;
            text-align: center;
        }

        .ta-logo {
            /* Match the locator slip's official seal + tagline treatment. */
            width: 25mm;
            max-height: 25mm;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }

        .ta-logo-right {
            /* The Bagong Pilipinas mark is visually denser, so its box is
               slightly smaller just as it is on the locator slip. */
            width: 20.5mm;
            max-height: 20.5mm;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }

        .ta-agency {
            text-align: center;
            padding: 0 4mm !important;
        }

        .ta-republic {
            font-family: "Times New Roman", Times, serif;
            font-size: 12pt;
            font-weight: 700;
            margin-bottom: 2mm;
        }

        .ta-department {
            font-family: "Old English Text MT", "Times New Roman", serif;
            font-size: 22pt;
            font-weight: 700;
            line-height: .95;
            white-space: nowrap;
        }

        .ta-address {
            margin-top: 1.6mm;
            font-family: "Times New Roman", Times, serif;
            font-size: 8.5pt;
            font-weight: 700;
            white-space: nowrap;
        }

        .ta-contact {
            border-top: .25mm solid #000;
            margin: 1.6mm auto 0;
            padding-top: .8mm;
            width: 103mm;
            font-size: 6.5pt;
            white-space: nowrap;
        }

        .ta-title {
            margin: 0 0 7mm 4mm;
            text-align: left;
            font-size: 13pt;
            font-weight: 700;
            text-transform: uppercase;
        }

        .ta-form {
            width: 170mm;
            margin: 0 auto;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .ta-col-name {
            width: 90mm !important;
            min-width: 90mm !important;
            max-width: 90mm !important;
        }

        .ta-col-date {
            width: 28mm !important;
            min-width: 28mm !important;
            max-width: 28mm !important;
        }

        .ta-col-activity {
            width: 27mm !important;
            min-width: 27mm !important;
            max-width: 27mm !important;
        }

        .ta-col-address {
            width: 25mm !important;
            min-width: 25mm !important;
            max-width: 25mm !important;
        }

        .ta-form td,
        .ta-form th {
            border: .35mm solid #000;
            padding: 1.1mm 1.25mm;
            vertical-align: top;
            overflow-wrap: break-word;
            word-wrap: break-word;
        }

        .ta-form .ta-label {
            font-size: 10pt;
            font-weight: 700;
        }

        .ta-form .ta-value {
            font-size: 10pt;
            font-weight: 400;
        }

        .ta-region-row td {
            height: 9mm;
            vertical-align: middle;
            text-align: center;
            font-size: 10pt;
            font-weight: 700;
        }

        .ta-column-head th {
            height: 13mm;
            text-align: center;
            vertical-align: middle;
            font-size: 10pt;
            font-weight: 700;
        }

        .ta-main-row td {
            height: 28mm;
            vertical-align: middle;
        }

        .ta-main-row .ta-value {
            line-height: 1.12;
        }

        .ta-date-value {
            line-height: 1.1;
        }

        .ta-office-row td {
            height: 11mm;
            vertical-align: middle;
        }

        .ta-notes-label td {
            height: 7mm;
            vertical-align: middle;
        }

        .ta-vehicle-row td {
            height: 10.5mm;
            vertical-align: middle;
        }

        .ta-requesting td {
            height: 31mm;
            text-align: center;
            vertical-align: top;
            padding-top: 2mm;
        }

        .ta-requesting-title {
            font-size: 10.8pt;
            font-weight: 700;
        }

        .ta-signature {
            margin-top: 15mm;
            font-size: 10.8pt;
            font-weight: 700;
            text-transform: uppercase;
        }

        .ta-signature-image {
            display: block;
            width: 50mm;
            height: 15mm;
            margin: 0.5mm auto 0;
            object-fit: contain;
        }

        .ta-signature--with-image {
            margin-top: 0;
        }

        .ta-position {
            font-size: 10.8pt;
            font-weight: 400;
        }

        .ta-footnote {
            margin: 8mm 0 11mm 6mm;
            font-size: 9.5pt;
            font-style: italic;
            line-height: 1.25;
        }

        .ta-bottom-line {
            border-top: .65mm solid #000;
            margin: 0 5mm 5mm;
        }

        .ta-note {
            margin-left: 6mm;
            font-size: 11pt;
            font-style: italic;
        }

        .bold {
            font-weight: 700;
        }
    </style>
</head>
<body>
    <div class="ta-page">
        <table class="ta-header">
            <tr>
                <td class="ta-logo-cell">
                    @if($dmwLogoPath)
                        <img class="ta-logo" src="{{ $dmwLogoPath }}" alt="">
                    @endif
                </td>
                <td class="ta-agency">
                    <div class="ta-republic">Republic of the Philippines</div>
                    <div class="ta-department">Department of Migrant Workers</div>
                    <div class="ta-address">3<sup>rd</sup> Floor NCCC Mall, Km. 7 Tigatto Road, Barangay Buhangin, Davao City</div>
                    <div class="ta-contact">Website: dmw.gov.ph | Email: davao@dmw.gov.ph | Hotline: 0931 029 8009</div>
                </td>
                <td class="ta-logo-cell">
                    @if($bagongPilipinasLogoPath)
                        <img class="ta-logo-right" src="{{ $bagongPilipinasLogoPath }}" alt="">
                    @endif
                </td>
            </tr>
        </table>

        <h1 class="ta-title">Request for Travel Authority</h1>

        <table class="ta-form">
            <colgroup>
                <col class="ta-col-name" style="width:90mm">
                <col class="ta-col-date" style="width:28mm">
                <col class="ta-col-activity" style="width:27mm">
                <col class="ta-col-address" style="width:25mm">
            </colgroup>
            <tr class="ta-region-row">
                <td colspan="4">{{ $regionalOffice }}</td>
            </tr>
            <tr class="ta-column-head">
                <th class="ta-col-name" style="width:90mm">Name of Regional Office<br>Employee/s:</th>
                <th class="ta-col-date" style="width:28mm">Date of Travel</th>
                <th class="ta-col-activity" style="width:27mm">Activity</th>
                <th class="ta-col-address" style="width:25mm">Address/<br>Venue</th>
            </tr>
            <tr class="ta-main-row">
                <td class="ta-value ta-col-name" style="width:90mm">
                    @foreach($nameLines as $line)
                        {{ $line }}@if(! $loop->last)<br>@endif
                    @endforeach
                </td>
                <td class="ta-value ta-date-value ta-col-date" style="width:28mm">{{ $travelDate }}</td>
                <td class="ta-value ta-col-activity" style="width:27mm">{{ $travelOrder->purpose }}</td>
                <td class="ta-value ta-col-address" style="width:25mm">{{ $travelOrder->places_of_travel }}</td>
            </tr>
            <tr class="ta-office-row">
                <td class="ta-label">Requesting Government<br>Official/ Office:</td>
                <td colspan="3" class="ta-value">{{ $requestingOffice }}</td>
            </tr>
            <tr class="ta-notes-label">
                <td colspan="4" class="ta-label">Notes/Remarks:</td>
            </tr>
            <tr class="ta-notes-label">
                <td colspan="4" class="ta-value">{{ $notesRemarks }}</td>
            </tr>
            <tr class="ta-notes-label">
                <td colspan="4"><span class="bold">For Regional Office personnel who will use government vehicle:</span> {{ $travelOrder->travel_type === 'local' ? 'Use of Vehicle from Davao City to '.$travelOrder->places_of_travel.'; then back to Davao City.' : '' }}</td>
            </tr>
            <tr class="ta-vehicle-row">
                <td class="ta-label">Name of Driver:</td>
                <td colspan="3" class="ta-value">{{ $driverName }}</td>
            </tr>
            <tr class="ta-vehicle-row">
                <td class="ta-label">Vehicle/Plate No.:</td>
                <td colspan="3" class="ta-value">{{ $vehiclePlateNo }}</td>
            </tr>
            <tr class="ta-requesting">
                <td colspan="4">
                    <div class="ta-requesting-title">Requesting Party:</div>
                    @if($requestingSignaturePath)
                        <img src="{{ $requestingSignaturePath }}" alt="Employee e-signature" class="ta-signature-image">
                    @endif
                    <div class="ta-signature {{ $requestingSignaturePath ? 'ta-signature--with-image' : '' }}">{{ $requestingPartyName }}</div>
                    <div class="ta-position">{{ $employeePosition }}</div>
                </td>
            </tr>
        </table>

        <div class="ta-footnote">
            *Please attach supporting document (request for PEOS from PESO/LGU/universities/schools etc., request for inspection,<br>
            request for court hearing, Notice of Meeting, etc.)
        </div>

        <div class="ta-bottom-line"></div>
        <div class="ta-note">Note: For FAD personnel use</div>
    </div>
</body>
</html>
