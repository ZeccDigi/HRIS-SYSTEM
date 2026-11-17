<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Locator Slip Form</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 5mm;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
        }

        .sheet {
            width: 200mm;
            height: 287mm;
            margin: 0 auto;
            background: #fff;
        }

        .copies-layout {
            width: 100%;
            height: 100%;
            border-collapse: separate;
            /* Narrower side gutters give each printed copy the reference width. */
            /* Equal vertical insets keep the forms balanced within the paper. */
            border-spacing: 4mm 9mm;
            table-layout: fixed;
        }

        .copies-layout > tbody > tr > td {
            width: 50%;
            height: 50%;
            padding: 0;
            /* Each form has a fixed compact height. Center it inside its
               half-page cell so the outer top and bottom whitespace match. */
            vertical-align: middle;
        }

        .copy-table {
            width: 100%;
            height: 120mm;
            border-collapse: collapse;
            table-layout: fixed;
            border: .45mm solid #000;
            font-size: 6.2pt;
            line-height: 1.08;
        }

        .copy-table td {
            border: .22mm solid #000;
            padding: 0;
            vertical-align: top;
        }

        .copy-wrap {
            width: 100%;
        }

        .header-cell {
            height: 20mm;
            /* Small top inset keeps the header marks from sitting on the border. */
            padding: 2mm 1.4mm 1mm;
        }

        .ob-header-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .ob-header-table td {
            border: 0;
            padding: 0;
            vertical-align: middle;
        }

        .ob-header-table .logo-slot {
            width: 22mm;
            text-align: center;
        }

        .ob-header-table .logo-slot.right {
            width: 22mm;
        }

        .form-logo {
            /* The DMW asset includes the Tahanan ng OFW tagline, so it needs
               a larger image box than the Bagong Pilipinas mark to look equal. */
            width: 16mm;
            max-height: 15mm;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }

        .form-logo.right {
            /* The Bagong Pilipinas mark is visually denser than the DMW seal
               with its tagline, so it needs a smaller box. */
            width: 13mm;
            max-height: 13mm;
        }

        .agency-block {
            text-align: center;
            font-size: 5.8pt;
            line-height: 1.1;
        }

        .agency-name {
            font-weight: 700;
            text-transform: uppercase;
        }

        .form-title {
            margin-top: 1.8mm;
            font-size: 9pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .01em;
        }

        .label {
            font-weight: 700;
            text-transform: uppercase;
            font-size: 7pt;
            white-space: nowrap;
        }

        .label-cell {
            height: 12mm;
            text-align: center;
            vertical-align: top;
            padding-top: 1.8mm !important;
        }

        .field-cell {
            height: 8mm;
            padding: .7mm 1mm !important;
        }

        .field-label {
            display: block;
            font-size: 7pt;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1;
            text-align: left;
        }

        .field-value {
            display: block;
            margin-top: 1.4mm;
            text-align: center;
            font-size: 7.2pt;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.12;
            overflow-wrap: anywhere;
        }

        .value {
            font-size: 7.2pt;
            font-weight: 700;
            text-transform: uppercase;
            overflow-wrap: anywhere;
        }

        .purpose-label-cell {
            height: 4.8mm;
            padding: .7mm 1mm !important;
            border-bottom: 0 !important;
        }

        .purpose-value {
            height: 27mm;
            vertical-align: top;
            padding: 1.2mm 1.3mm !important;
            line-height: 1.15;
            text-align: left;
            border-top: 0 !important;
        }

        .time-cell {
            height: 14mm;
            text-align: center;
            vertical-align: top;
            padding-top: 2.6mm !important;
        }

        .signature-space {
            height: 13mm;
            vertical-align: bottom;
            text-align: left;
            padding: 1.1mm 1mm !important;
            font-size: 5.8pt;
        }

        .signature-name {
            display: none;
        }

        .locator-signature-img {
            display: block;
            max-width: 44mm;
            max-height: 9mm;
            margin: 0 auto;
            object-fit: contain;
        }

        .signature-caption {
            font-weight: 700;
            text-transform: uppercase;
        }

        .approval-cell {
            height: 25mm;
            text-align: left;
            vertical-align: bottom;
            padding: 1mm 1mm 1.5mm !important;
            font-size: 5.6pt;
        }

        .approval-name {
            margin-top: 15mm;
            min-height: 3mm;
            border-bottom: .22mm solid #000;
            display: block;
            text-align: center;
            padding-bottom: .25mm;
            font-size: 5.5pt;
            font-weight: 700;
            text-transform: uppercase;
        }

        .approval-role {
            padding-top: .45mm;
            text-align: center;
            font-size: 5.4pt;
            font-weight: 700;
            text-transform: uppercase;
        }

        .approval-position {
            text-align: center;
            font-size: 5.2pt;
            font-weight: 700;
        }

        .copy-label {
            height: 4mm;
            text-align: center;
            font-size: 7.5pt;
            font-weight: 400;
            text-transform: uppercase;
            line-height: 4mm;
        }

        .pass-slip-table {
            height: 117mm;
            border: .35mm solid #000;
            font-size: 8.2pt;
            line-height: 1.15;
        }

        .pass-slip-table td {
            border: 0;
        }

        .pass-header-cell {
            height: 21mm;
            padding: 1.5mm 4.5mm 0 !important;
        }

        .pass-header {
            display: grid;
            grid-template-columns: 20mm 1fr 20mm;
            align-items: start;
            column-gap: 2mm;
        }

        .pass-logo {
            /* Visually balance the seal + tagline with the Bagong Pilipinas mark. */
            width: 16mm;
            max-height: 15mm;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }

        .pass-logo.right {
            width: 13mm;
            max-height: 13mm;
        }

        .pass-title {
            padding-top: 1.2mm;
            text-align: center;
            font-size: 11pt;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .01em;
        }

        .pass-date-row {
            display: flex;
            justify-content: flex-end;
            align-items: flex-end;
            gap: 1.6mm;
            margin-top: 2mm;
            font-size: 8.2pt;
            font-weight: 400;
        }

        .pass-body-cell {
            padding: 0 4.5mm 1.1mm !important;
            vertical-align: top;
        }

        .pass-line-row {
            display: flex;
            align-items: flex-end;
            gap: 1.8mm;
            min-height: 4.2mm;
            font-size: 8.2pt;
            font-weight: 400;
        }

        .pass-line-row.compact {
            min-height: 3.9mm;
        }

        .pass-label {
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .pass-line {
            display: block;
            flex: 1 1 auto;
            min-height: 3.7mm;
            border-bottom: .22mm solid #000;
            text-align: center;
            font-size: 7pt;
            font-weight: 400;
            line-height: 3.5mm;
            overflow: hidden;
            white-space: nowrap;
            text-transform: uppercase;
        }

        .pass-line.strong {
            font-weight: 700;
        }

        .pass-instruction {
            margin: .45mm 0 .2mm;
            font-size: 8.2pt;
            font-weight: 700;
            font-style: italic;
            line-height: 1.25;
        }

        .pass-reason-line {
            margin: .8mm 0 1.2mm;
            min-height: 3.7mm;
            line-height: 3.5mm;
        }

        .pass-time-row {
            min-height: 5.5mm !important;
        }

        .pass-time-row + .pass-time-row {
            margin-top: .8mm;
        }

        .pass-signature {
            width: 42mm;
            margin: 5mm auto .6mm;
            border-bottom: .22mm solid #000;
            min-height: 3mm;
            display: flex;
            align-items: flex-end;
            justify-content: center;
        }

        .pass-signature img {
            max-width: 40mm;
            max-height: 9mm;
            object-fit: contain;
            display: block;
        }

        .pass-signature-caption {
            text-align: center;
            font-size: 8pt;
            font-weight: 400;
        }

        .pass-approval {
            width: 100%;
            height: 20mm;
            margin-top: .6mm;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .pass-approval td {
            width: 50%;
            border: .22mm solid #000;
            padding: .9mm 2mm .8mm !important;
            vertical-align: bottom;
        }

        .pass-approval-label {
            height: 5mm;
            font-size: 5.8pt;
            font-weight: 400;
            text-transform: uppercase;
            vertical-align: top;
        }

        .pass-approval-line {
            width: 32mm;
            min-height: 3mm;
            margin: 1.5mm auto .35mm;
            border-bottom: .22mm solid #000;
        }

        .pass-approval-name {
            min-height: 3mm;
            margin-top: 1mm;
            text-align: center;
            font-size: 7.2pt;
            font-weight: 700;
            text-transform: uppercase;
        }

        .pass-approval-role {
            text-align: center;
            font-size: 8pt;
            font-weight: 400;
        }

        .pass-security-note {
            text-align: center;
            font-size: 8pt;
            font-weight: 400;
            margin: .8mm 0 .2mm;
        }

        .pass-guard-line {
            width: 58mm;
            min-height: 3mm;
            margin: 4.5mm auto .35mm;
            border-bottom: .22mm solid #000;
        }

        .pass-copy-label {
            height: 4mm;
            line-height: 4mm;
            font-size: 7pt;
            color: transparent;
        }


        /*
         * Preview mode
         * The iframe URL contains ?preview=1, so only one enlarged copy is
         * displayed in the modal. These styles do not affect actual printing.
         */
        .preview-sheet {
            width: 100%;
            height: auto;
            min-height: 0;
            margin: 0;
            padding: 8px;
            background: #ffffff;
        }

        .preview-layout {
            /* Same approximate width as one copy in the 2 x 2 A4 print layout. */
            width: min(100%, 89.5mm);
            height: auto;
            margin: 0 auto;
            border-spacing: 0;
        }

        .preview-layout > tbody,
        .preview-layout > tbody > tr {
            display: block;
            width: 100%;
            height: auto;
        }

        .preview-layout > tbody > tr > td {
            display: block;
            width: 100%;
            height: auto;
            padding: 0;
        }

        .preview-layout .copy-wrap {
            width: 100%;
        }

        .preview-layout .copy-table {
            width: 100%;
            height: 125mm;
            min-height: 0;
            margin: 0;
            background: #fff;
            box-shadow: none;
        }

        .preview-layout .pass-slip-table {
            height: 121mm;
            min-height: 0;
        }

        .preview-layout .copy-label,
        .preview-layout .pass-copy-label {
            display: none;
        }


        .preview-layout .copy-wrap {
            margin: 0;
        }

        .preview-layout .copy-table,
        .preview-layout .pass-slip-table {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        body:has(.preview-sheet) {
            min-height: 0;
            background: #ffffff;
        }

        /* DomPDF does not implement CSS grid the same way as browsers. */
        .pdf-sheet .copies-layout {
            /* Match the reference's four-copy layout: fixed half-page cells
               with the same gap at every outer edge. */
            /* Fixed table heights make DomPDF push the lower copies onto a
               second page. Let it determine the natural one-page grid. */
            height: auto;
            /* Shift the group down to match the side inset, while the taller
               copies consume the former excess bottom whitespace. */
            margin: 4mm 0 0;
            border-spacing: 4mm;
        }

        .pdf-sheet .copies-layout > tbody > tr > td {
            height: auto;
        }

        .pdf-sheet .official-business-table {
            height: 126mm;
        }

        .pdf-sheet .pass-slip-table {
            height: 123mm;
        }

        /* Keep each Official Business copy within its half-page cell. DomPDF
           otherwise moves the body rows to the following page. */
        .pdf-sheet .official-business-table .header-cell {
            height: 20mm;
        }

        .pdf-sheet .official-business-table .ob-header-table,
        .pdf-sheet .pass-header-pdf {
            margin-top: 1.5mm;
        }

        .pdf-sheet .official-business-table .label-cell {
            height: 10mm;
            padding-top: 1.2mm !important;
        }

        .pdf-sheet .official-business-table .field-cell {
            height: 6mm;
            padding-top: .5mm !important;
        }

        .pdf-sheet .official-business-table .purpose-label-cell {
            height: 4mm;
        }

        .pdf-sheet .official-business-table .purpose-value {
            height: 18mm;
        }

        .pdf-sheet .official-business-table .time-cell {
            height: 12mm;
            padding-top: 1.8mm !important;
        }

        .pdf-sheet .official-business-table .signature-space {
            height: 11mm;
        }

        .pdf-sheet .official-business-table .approval-cell {
            height: 31mm;
        }

        .pdf-sheet .official-business-table .approval-name {
            margin-top: 19mm;
        }

        .pdf-sheet .pass-header-pdf {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .pdf-sheet .pass-header-pdf td {
            padding: 0;
            vertical-align: top;
        }

        .pdf-sheet .pass-header-pdf .logo-column {
            width: 22mm;
            text-align: center;
        }

        .pdf-sheet .pass-header-pdf .pass-logo {
            width: 16mm;
            height: auto;
            margin: 0 auto;
        }

        .pdf-sheet .pass-header-pdf .pass-logo.right {
            width: 13mm;
            height: auto;
        }

        .pdf-sheet .pass-header-pdf .pass-date-row {
            display: block;
            margin-top: 2mm;
            text-align: center;
        }

        .pdf-sheet .pass-header-pdf .pass-date-row .pass-line {
            margin: 0 auto;
        }

        @media print {
            html,
            body {
                width: 210mm;
                height: 297mm;
                margin: 0;
                padding: 0;
                background: #ffffff;
            }

            .sheet {
                width: 200mm;
                height: 287mm;
                min-height: 0;
                margin: 0;
                padding: 0;
                background: #ffffff;
            }

            .copies-layout {
                display: table;
                width: 100%;
                height: 100%;
                margin: 0;
                border-collapse: separate;
                border-spacing: 7.5mm 9mm;
                table-layout: fixed;
            }

            .copies-layout > tbody {
                display: table-row-group;
            }

            .copies-layout > tbody > tr {
                display: table-row;
            }

            .copies-layout > tbody > tr > td {
                display: table-cell;
                width: 50%;
                height: 50%;
                padding: 0;
                vertical-align: middle;
            }

            .copy-table {
                width: 100%;
                height: 125mm;
                margin: 0;
                box-shadow: none;
            }

            .pass-slip-table {
                height: 121mm;
            }

            .copy-label,
            .pass-copy-label {
                display: block;
            }

            .preview-sheet {
                width: 100%;
                height: auto;
                min-height: 0;
                margin: 0;
                padding: 8mm;
                background: #ffffff;
            }

            .preview-layout {
                display: table;
                width: min(100%, 89.5mm);
                height: auto;
                margin: 0 auto;
                border-spacing: 0;
                table-layout: fixed;
            }

            .preview-layout > tbody,
            .preview-layout > tbody > tr,
            .preview-layout > tbody > tr > td {
                display: block;
                width: 100%;
                height: auto;
                padding: 0;
            }

            .preview-layout .copy-table {
                width: 100%;
                height: 125mm;
                margin: 0;
                box-shadow: none;
            }

            .preview-layout .pass-slip-table {
                height: 121mm;
            }

            .preview-layout .copy-label,
            .preview-layout .pass-copy-label {
                display: none;
            }
        }
    </style>
</head>
<body>
    @php
        $employee = $locatorSlip->employee;
        $middleInitial = $employee?->middlename ? mb_substr($employee->middlename, 0, 1) . '. ' : '';
        $employeeName = trim(($employee->firstname ?? '') . ' ' . $middleInitial . ($employee->lastname ?? '') . ' ' . ($employee->suffix ?? ''));
        $division = $employee?->division ?: 'N/A';
        $office = 'DMW RO XI';
        $chiefName = $locatorSlip->approved_by_chief_name ?: 'LOUIE JAY C. LOSARIA';
        $regionalDirectorName = $locatorSlip->approved_by_regional_director_name ?: 'MARIA CAROLINA B. AGDAMAG';
        $dateCovered = \Carbon\Carbon::parse($locatorSlip->date_covered)->format('F d, Y');
        $timeFrom = $locatorSlip->time_from ? \Carbon\Carbon::parse($locatorSlip->time_from)->format('h:i A') : '';
        $timeTo = $locatorSlip->time_to ? \Carbon\Carbon::parse($locatorSlip->time_to)->format('h:i A') : '';
        $formType = ($locatorSlip->type ?? '') === 'Personal' ? 'Pass Slip' : ($locatorSlip->type ?: 'Official Business');
        $formTitle = $formType === 'Pass Slip' ? 'Pass Slip Form' : 'Official Business Form';
        $isPdf = $pdfMode ?? false;
        $isPreview = $previewMode ?? request()->boolean('preview');
        $signatureUrl = $isPdf
            ? ($signaturePath ?? $employee?->e_signature_absolute_path)
            : $employee?->effective_signature_url;
        $dmwLogoSource = $isPdf
            ? ($dmwLogoPath ?? public_path('images/official DMW.png'))
            : asset('images/official DMW.png');
        $bagongPilipinasLogoSource = $isPdf
            ? ($bagongPilipinasLogoPath ?? public_path('images/bagong-pilipinas-logo.png'))
            : asset('images/bagong-pilipinas-logo.png');

        // Show only one copy inside the preview modal.
        // Keep four copies when opening the actual printable page.
        $copyLabels = $isPreview
            ? ['PREVIEW']
            : ['EMPLOYEE FILE', 'HR/FAD FILE', 'EMPLOYEE FILE', 'HR/FAD FILE'];
    @endphp

    <main class="sheet{{ $isPreview ? ' preview-sheet' : '' }}{{ $isPdf ? ' pdf-sheet' : '' }}">
        <table
            class="copies-layout{{ $isPreview ? ' preview-layout' : '' }}"
            aria-label="{{ $formTitle }} copies"
        >
            <tbody>
                @foreach(array_chunk($copyLabels, 2) as $copyRow)
                    <tr>
                        @foreach($copyRow as $copyLabel)
                            <td>
                                <div class="copy-wrap">
                                <table class="copy-table{{ $formType === 'Pass Slip' ? ' pass-slip-table' : ' official-business-table' }}" aria-label="{{ $copyLabel }} {{ $formTitle }}">
                                    <colgroup>
                                        <col style="width: 25%">
                                        <col style="width: 25%">
                                        <col style="width: 25%">
                                        <col style="width: 25%">
                                    </colgroup>
                                    @if($formType === 'Pass Slip')
                                    <tr>
                                        <td colspan="4" class="pass-header-cell">
                                            @if($isPdf)
                                                <table class="pass-header-pdf" aria-hidden="true">
                                                    <tr>
                                                        <td class="logo-column">
                                                            <img class="pass-logo" src="{{ $dmwLogoSource }}" alt="Department of Migrant Workers - Tahanan ng OFW">
                                                        </td>
                                                        <td>
                                                            <div class="pass-title">PASS SLIP</div>
                                                            <div class="pass-date-row">
                                                                <span>Date :</span>
                                                                <span class="pass-line" style="max-width: 29mm;">{{ $dateCovered }}</span>
                                                            </div>
                                                        </td>
                                                        <td class="logo-column">
                                                            <img class="pass-logo right" src="{{ $bagongPilipinasLogoSource }}" alt="">
                                                        </td>
                                                    </tr>
                                                </table>
                                            @else
                                                <div class="pass-header">
                                                    <img class="pass-logo" src="{{ $dmwLogoSource }}" alt="Department of Migrant Workers - Tahanan ng OFW">
                                                    <div>
                                                        <div class="pass-title">PASS SLIP</div>
                                                        <div class="pass-date-row">
                                                            <span>Date :</span>
                                                            <span class="pass-line" style="max-width: 29mm;">{{ $dateCovered }}</span>
                                                        </div>
                                                    </div>
                                                    <img class="pass-logo right" src="{{ $bagongPilipinasLogoSource }}" alt="">
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" class="pass-body-cell">
                                            <div class="pass-line-row compact">
                                                <span class="pass-label">Name :</span>
                                                <span class="pass-line strong">{{ $employeeName ?: 'N/A' }}</span>
                                            </div>
                                            <div class="pass-line-row compact">
                                                <span class="pass-label">Division :</span>
                                                <span class="pass-line strong">{{ $division }}</span>
                                            </div>

                                            <div class="pass-instruction">
                                                Permission is requested to leave the office during<br>
                                                office hours for personal purposes.
                                            </div>

                                            <div class="pass-line-row compact">
                                                <span class="pass-label">State Reason(s) :</span>
                                                <span class="pass-line">{{ $locatorSlip->purpose ?: '' }}</span>
                                            </div>
                                            <span class="pass-line pass-reason-line"></span>

                                            <div class="pass-line-row compact pass-time-row">
                                                <span class="pass-label">Time of Departure :</span>
                                                <span class="pass-line">{{ $timeFrom }}</span>
                                            </div>
                                            <div class="pass-line-row compact pass-time-row">
                                                <span class="pass-label">Est. Time of Return :</span>
                                                <span class="pass-line">{{ $timeTo }}</span>
                                            </div>

                                            <div class="pass-signature">
                                                @if($signatureUrl)
                                                    <img src="{{ $signatureUrl }}" alt="Employee signature">
                                                @endif
                                            </div>
                                            <div class="pass-signature-caption">Signature of Employee</div>

                                            <table class="pass-approval">
                                                <tr>
                                                    <td>
                                                        <div class="pass-approval-label">RECOMMENDING APPROVAL:</div>
                                                        <div class="pass-approval-name">{{ $chiefName }}</div>
                                                        <div class="pass-approval-line"></div>
                                                        <div class="pass-approval-role">Division Chief</div>
                                                    </td>
                                                    <td>
                                                        <div class="pass-approval-label" style="text-align:center;">APPROVED BY:</div>
                                                        <div class="pass-approval-name">{{ $regionalDirectorName }}</div>
                                                        <div class="pass-approval-line"></div>
                                                        <div class="pass-approval-role">Director/Head, B/S/O</div>
                                                    </td>
                                                </tr>
                                            </table>

                                            <div class="pass-security-note">(To be filled up by the Security Guard)</div>

                                            <div class="pass-line-row">
                                                <span class="pass-label">Time of Return :</span>
                                                <span class="pass-line"></span>
                                            </div>

                                            <div class="pass-guard-line"></div>
                                            <div class="pass-signature-caption">Signature of Security Guard</div>
                                        </td>
                                    </tr>
                                    @else
                                    <tr>
                                        <td colspan="4" class="header-cell">
                                            <table class="ob-header-table" aria-hidden="true">
                                                <tr>
                                                    <td class="logo-slot">
                                                        <img class="form-logo" src="{{ $dmwLogoSource }}" alt="Department of Migrant Workers - Tahanan ng OFW">
                                                    </td>
                                                    <td class="agency-block">
                                                        <div class="agency-name">Department of Migrant Workers</div>
                                                        <div>Regional Office XI</div>
                                                        <div class="form-title">{{ $formTitle }}</div>
                                                    </td>
                                                    <td class="logo-slot right">
                                                        <img class="form-logo right" src="{{ $bagongPilipinasLogoSource }}" alt="">
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="label label-cell" colspan="2">
                                            Name of Employee
                                            <span class="field-value">{{ $employeeName ?: 'N/A' }}</span>
                                        </td>
                                        <td class="label label-cell" colspan="2">
                                            Date Covered
                                            <span class="field-value">{{ $dateCovered }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="field-cell" colspan="2">
                                            <span class="field-label">Division:</span>
                                            <span class="field-value">{{ $division }}</span>
                                        </td>
                                        <td class="field-cell" colspan="2">
                                            <span class="field-label">Office:</span>
                                            <span class="field-value">{{ $office }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="purpose-label-cell" colspan="4">
                                            <span class="field-label">Purpose:</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="value purpose-value" colspan="4">{{ $locatorSlip->purpose ?: '' }}</td>
                                    </tr>
                                    <tr>
                                        <td class="time-cell" colspan="2">
                                            <span class="label">From (Time)</span>
                                            <span class="field-value">{{ $timeFrom }}</span>
                                        </td>
                                        <td class="time-cell" colspan="2">
                                            <span class="label">To (Time)</span>
                                            <span class="field-value">{{ $timeTo }}</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="signature-space" colspan="4">
                                            <span class="signature-caption">Signature:</span>
                                            @if($signatureUrl)
                                                <img src="{{ $signatureUrl }}" alt="Employee signature" class="locator-signature-img">
                                            @endif
                                            <div class="signature-name">{{ $employeeName ?: '' }}</div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="approval-cell" colspan="2">
                                            <span class="field-label">Recommending Approval:</span>
                                            <div class="approval-name">{{ $chiefName }}</div>
                                            <div class="approval-position">Division Chief</div>
                                        </td>
                                        <td class="approval-cell" colspan="2">
                                            <span class="field-label">Approved By:</span>
                                            <div class="approval-name">{{ $regionalDirectorName }}</div>
                                            <div class="approval-position">Regional Director</div>
                                        </td>
                                    </tr>
                                    @endif
                                </table>
                                <div class="{{ $formType === 'Pass Slip' ? 'pass-copy-label' : 'copy-label' }}">{{ $copyLabel }}</div>
                                </div>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </main>
    @unless($isPreview)
        <script>
            const locatorSlipReturnUrl = @js(route('locator-slips.index', $locatorSlip));

            window.addEventListener('afterprint', () => {
                window.location.href = locatorSlipReturnUrl;
            });

            window.addEventListener('load', () => {
                window.print();
            });
        </script>
    @endunless
</body>
</html>
