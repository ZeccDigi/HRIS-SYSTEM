<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CTO Form - {{ $ctoRequest->employee?->lastname }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            width: 210mm;
            min-height: 297mm;
            margin: 0;
            padding: 0;
            background: #fff;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
        }

        .sheet {
            width: 190mm;
            height: 270mm;
            margin: 8mm auto;
            background: #fff;
            overflow: hidden;
        }

        .cto-grid {
            width: 100%;
            height: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.8pt;
            line-height: 1.05;
        }

        .cto-grid td {
            padding: 0.65mm 0.7mm;
            vertical-align: middle;
            overflow: hidden;
        }

        .font-calibri {
            font-family: Calibri, Arial, Helvetica, sans-serif;
        }

        .b { font-weight: 700; }
        .i { font-style: italic; }
        .center { text-align: center; }
        .left { text-align: left; }
        .top { vertical-align: top !important; }
        .upper { text-transform: uppercase; }
        .nowrap { white-space: nowrap; }
        .editable { min-height: 3.5mm; }

        .fs9 { font-size: 9pt; }
        .fs10 { font-size: 8.8pt; }
        .fs11 { font-size: 9pt; }
        .fs18 { font-size: 18pt; }

        .thin-t { border-top: 0.22mm solid #000; }
        .thin-r { border-right: 0.22mm solid #000; }
        .thin-b { border-bottom: 0.22mm solid #000; }
        .thin-l { border-left: 0.22mm solid #000; }
        .med-t { border-top: 0.45mm solid #000; }
        .med-r { border-right: 0.45mm solid #000; }
        .med-b { border-bottom: 0.45mm solid #000; }
        .med-l { border-left: 0.45mm solid #000; }
        .double-t { border-top: 0.55mm double #000; }
        .double-b { border-bottom: 0.55mm double #000; }

        .gray { background: #c0c0c0; }
        .title {
            font-size: 18pt;
            font-weight: 700;
            text-align: center;
            white-space: nowrap;
            letter-spacing: 0;
        }

        .agency {
            font-size: 10pt;
            font-weight: 700;
            text-align: center;
            white-space: pre-line;
            line-height: 1.18;
        }

        .applicant-signature {
            max-width: 48mm;
            max-height: 11mm;
            object-fit: contain;
            display: block;
            margin: 0 auto 0.5mm;
        }

        .signature-field {
            padding-bottom: 0.8mm;
            vertical-align: bottom !important;
        }

        .signature-caption {
            border-top: 0.22mm solid #000;
            padding-top: 1mm;
        }

        .logo-placeholder {
            width: 17mm;
            height: 7mm;
            margin: 0 auto;
            border: 0.22mm dashed #dbeafe;
        }

        .line-value {
            display: inline-block;
            min-width: 27mm;
            border-bottom: 0.22mm solid #000;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
        }

        .wide-line {
            display: block;
            width: 61mm;
            margin-top: 2.6mm;
            border-bottom: 0.22mm solid #000;
            padding-bottom: 1.5mm;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
        }

        .sig-line {
            display: block;
            width: 80mm;
            margin-left: auto;
            border-bottom: 0.22mm solid #000;
        }

        .box {
            display: inline-flex;
            width: 4.3mm;
            height: 7mm;
            align-items: center;
            justify-content: center;
            border: 0.22mm solid #000;
            font-weight: 700;
        }

        .box.second {
            border-top: 0;
        }

        .underline {
            display: inline-block;
            border-bottom: 0.22mm solid #000;
            min-width: 70mm;
            text-align: center;
        }

        .print-only-note {
            display: none;
        }

        @media print {
            html,
            body {
                width: 210mm;
                height: 297mm;
                overflow: hidden;
            }

            .sheet {
                width: 190mm;
                height: 270mm;
                margin: 8mm auto;
            }
        }
    </style>
</head>
<body>
    @php
        $employee = $ctoRequest->employee;
        $middleInitial = $employee?->middlename ? mb_substr($employee->middlename, 0, 1) . '.' : '';
        $lastName = trim((string) ($employee?->lastname ?? ''));
        $firstName = trim((string) ($employee?->firstname ?? ''));
        $fullName = trim($lastName . ', ' . $firstName . ' ' . $middleInitial);
        $division = $employee?->division ?: 'WRSD';
        $office = 'DMW RO XI - ' . $division;
        $position = $employee?->position ?: 'N/A';
        $dateFiled = $ctoRequest->created_at?->format('F j, Y') ?? now()->format('F j, Y');
        $start = $ctoRequest->date_start;
        $end = $ctoRequest->date_end;
        $inclusiveDates = $start && $end && $start->isSameDay($end)
            ? strtoupper($start->format('M. j, Y'))
            : strtoupper(($start?->format('M. j, Y') ?? '') . ' - ' . ($end?->format('M. j, Y') ?? ''));
        $hoursNumber = (float) $ctoRequest->hours;
        $hoursDisplay = fmod($hoursNumber, 1.0) === 0.0 ? (string) (int) $hoursNumber : number_format($hoursNumber, 1);
        $numberWords = [
            0 => 'ZERO', 1 => 'ONE', 2 => 'TWO', 3 => 'THREE', 4 => 'FOUR',
            5 => 'FIVE', 6 => 'SIX', 7 => 'SEVEN', 8 => 'EIGHT', 9 => 'NINE',
            10 => 'TEN', 11 => 'ELEVEN', 12 => 'TWELVE', 13 => 'THIRTEEN',
            14 => 'FOURTEEN', 15 => 'FIFTEEN', 16 => 'SIXTEEN',
            17 => 'SEVENTEEN', 18 => 'EIGHTEEN', 19 => 'NINETEEN',
            20 => 'TWENTY', 24 => 'TWENTY-FOUR', 40 => 'FORTY',
        ];
        $hoursWords = $numberWords[(int) $hoursNumber] ?? strtoupper($hoursDisplay);
        $hoursText = $hoursWords . ' (' . $hoursDisplay . ') ' . \Illuminate\Support\Str::upper(\Illuminate\Support\Str::plural('hour', $hoursNumber));
        $formatHours = fn ($value) => fmod((float) $value, 1.0) === 0.0 ? (string) (int) $value : number_format((float) $value, 1);
        $balanceBefore = $ctoRequest->cto_balance_before !== null ? (float) $ctoRequest->cto_balance_before : (float) ($employee?->cto_balance ?? 0);
        $balanceAfter = $ctoRequest->cto_balance_after !== null
            ? (float) $ctoRequest->cto_balance_after
            : ($ctoRequest->type === 'use' ? max(0, $balanceBefore - $hoursNumber) : $balanceBefore + $hoursNumber);
        $totalHoursEarned = $ctoRequest->type === 'earn' ? $balanceAfter : $balanceBefore;
        $totalHoursEarnedDisplay = $formatHours($totalHoursEarned);
        $lessThisApplicationDisplay = $ctoRequest->type === 'use' ? $hoursDisplay : '';
        $balanceDisplay = $formatHours($balanceAfter);
        $cocAsOf = $ctoRequest->created_at?->format('F j, Y') ?? now()->format('F j, Y');
        $applicantSignatureUrl = $ctoRequest->applicant_signature_path ? \App\Services\SignatureStorage::image($ctoRequest->applicant_signature_path) : ($employee?->effective_signature_url ?? null);
        $chiefName = $ctoRequest->chief ? trim($ctoRequest->chief->firstname . ' ' . ($ctoRequest->chief->middlename ? mb_substr($ctoRequest->chief->middlename, 0, 1) . '. ' : '') . $ctoRequest->chief->lastname) : 'LOUIE JAY C. LOSARIA';
        $chiefPosition = $ctoRequest->chief?->position ?: 'Chief Labor and Employment Officer';
        $hrName = $ctoRequest->hrstaff ? trim($ctoRequest->hrstaff->firstname . ' ' . ($ctoRequest->hrstaff->middlename ? mb_substr($ctoRequest->hrstaff->middlename, 0, 1) . '. ' : '') . $ctoRequest->hrstaff->lastname) : 'MANILYN JOY P. VELITA';
        $hrPosition = $ctoRequest->hrstaff?->position ?: 'Administrative Officer V';
        $directorName = $ctoRequest->regionalDirector ? trim($ctoRequest->regionalDirector->firstname . ' ' . ($ctoRequest->regionalDirector->middlename ? mb_substr($ctoRequest->regionalDirector->middlename, 0, 1) . '. ' : '') . $ctoRequest->regionalDirector->lastname) : 'MARIA CAROLINA B. AGDAMAG';
        $isApproved = $ctoRequest->status === 'approved';
        $isRejected = $ctoRequest->status === 'rejected';
        $disapprovalReason = trim(collect([$ctoRequest->chief_remarks, $ctoRequest->hrstaff_remarks, $ctoRequest->rd_remarks])->filter()->implode(' '));
    @endphp

    <main class="sheet">
        <table class="cto-grid" aria-label="CTO FORM worksheet replica">
            <colgroup>
                <col style="width: 1.755%">
                <col style="width: 1.349%">
                <col style="width: 17.977%">
                <col style="width: 16.487%">
                <col style="width: 12.299%">
                <col style="width: 4.187%">
                <col style="width: 2.432%">
                <col style="width: 43.514%">
            </colgroup>
            <tbody>
                <tr style="height:7.143mm">
                    <td colspan="7" class="font-calibri fs9 b i top med-l med-t">CTO Form</td>
                    <td class="med-r med-t"></td>
                </tr>
                <tr style="height:17.197mm">
                    <td colspan="8" class="agency med-l med-r">
Republic of the Philippines
Department of Migrant Workers
REGION XI</td>
                </tr>
                <tr style="height:16.139mm">
                    <td colspan="8" class="title med-l med-r thin-b">APPLICATION FOR COMPENSATORY TIME-OFF (CTO)</td>
                </tr>
                <tr style="height:5.820mm">
                    <td colspan="4" class="med-l thin-t top upper">OFFICE/DEPARTMENT</td>
                    <td colspan="4" class="thin-t med-r top">NAME : &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; (Last) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; (First) &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; (Middle)</td>
                </tr>
                <tr style="height:8.996mm">
                    <td colspan="4" class="med-l thin-b center b upper editable" contenteditable="true">{{ $office }}</td>
                    <td colspan="4" class="med-r thin-b center b upper editable" contenteditable="true">{{ $fullName ?: 'N/A' }}</td>
                </tr>
                <tr style="height:8.996mm">
                    <td colspan="4" class="med-l thin-t top">DATE OF FILING&nbsp;&nbsp; <span class="line-value editable" contenteditable="true">{{ strtoupper($dateFiled) }}</span></td>
                    <td colspan="4" class="thin-t med-r top">POSITION&nbsp;&nbsp; <span class="line-value editable" style="min-width:54mm" contenteditable="true">{{ $position }}</span></td>
                </tr>
                <tr style="height:8.996mm">
                    <td colspan="8" class="center b upper med-l med-r thin-t thin-b">DETAILS OF APPLICATION</td>
                </tr>
                <tr style="height:8.996mm">
                    <td class="med-l"></td>
                    <td></td>
                    <td colspan="3" class="left">Number of Hours Applied for:</td>
                    <td class="thin-l thin-t"></td>
                    <td></td>
                    <td class="med-r"></td>
                </tr>
                <tr style="height:8.996mm">
                    <td class="med-l"></td>
                    <td></td>
                    <td colspan="2" class="center b upper thin-b editable" contenteditable="true">{{ $hoursText }}</td>
                    <td></td>
                    <td class="thin-l"></td>
                    <td></td>
                    <td class="med-r"></td>
                </tr>
                <tr style="height:8.996mm">
                    <td class="med-l"></td>
                    <td></td>
                    <td colspan="3">Inclusive Date/s:</td>
                    <td class="thin-l"></td>
                    <td></td>
                    <td class="med-r"></td>
                </tr>
                <tr style="height:8.996mm">
                    <td class="med-l"></td>
                    <td></td>
                    <td colspan="2" class="center b upper thin-b editable" contenteditable="true">{{ $inclusiveDates }}</td>
                    <td></td>
                    <td class="thin-l"></td>
                    <td></td>
                    <td class="med-r"></td>
                </tr>
                <tr style="height:8.996mm">
                    <td class="med-l"></td>
                    <td></td>
                    <td colspan="3"></td>
                    <td class="thin-l"></td>
                    <td></td>
                    <td rowspan="2" class="center thin-t thin-b med-r signature-field">
                        @if($applicantSignatureUrl)
                            <img src="{{ $applicantSignatureUrl }}" alt="Applicant signature" class="applicant-signature">
                        @endif
                        <div class="signature-caption">Signature of Applicant</div>
                    </td>
                </tr>
                <tr style="height:8.996mm">
                    <td class="med-l"></td>
                    <td></td>
                    <td colspan="3"></td>
                    <td class="thin-l thin-b"></td>
                    <td></td>
                </tr>
                <tr style="height:8.996mm">
                    <td colspan="8" class="center b upper gray med-l med-r thin-t thin-b">DETAILS OF ACTION ON APPLICATION</td>
                </tr>
                <tr style="height:10.848mm">
                    <td class="med-l"></td>
                    <td></td>
                    <td colspan="3" class="center b upper thin-t">CERTIFICATION OF COMPENSATORY OVERTIME CREDITS (COC)</td>
                    <td colspan="3" class="left upper thin-l thin-t med-r">RECOMMENDATION</td>
                </tr>
                <tr style="height:8.996mm">
                    <td class="med-l"></td>
                    <td></td>
                    <td colspan="3" class="center">As of <span class="editable" contenteditable="true">{{ strtoupper($cocAsOf) }}</span></td>
                    <td class="thin-l"></td>
                    <td class="thin-l thin-r thin-t thin-b center">{{ $isApproved ? 'X' : '' }}</td>
                    <td class="med-r">For approval</td>
                </tr>
                <tr style="height:8.996mm">
                    <td class="med-l"></td>
                    <td></td>
                    <td colspan="3"></td>
                    <td class="thin-l"></td>
                    <td class="thin-l thin-r thin-b center">{{ $isRejected ? 'X' : '' }}</td>
                    <td class="med-r">For disapproval due to: </td>
                </tr>
                <tr style="height:8.202mm">
                    <td class="med-l"></td>
                    <td></td>
                    <td colspan="3" class="center upper thin-l thin-t thin-b">COMPENSATORY OVERTIME CREDIT</td>
                    <td class="thin-l"></td>
                    <td></td>
                    <td class="thin-b med-r editable" contenteditable="true">{{ $isRejected ? $disapprovalReason : '' }}</td>
                </tr>
                <tr style="height:8.202mm">
                    <td class="med-l"></td>
                    <td></td>
                    <td class="center i thin-l thin-r thin-b">Total Hours Earned</td>
                    <td colspan="2" class="thin-l thin-r thin-b center b editable" contenteditable="true">{{ $totalHoursEarnedDisplay }}</td>
                    <td class="thin-l"></td>
                    <td></td>
                    <td class="thin-b med-r"></td>
                </tr>
                <tr style="height:8.202mm">
                    <td class="med-l"></td>
                    <td></td>
                    <td class="center i thin-l thin-r thin-b">Less this application</td>
                    <td colspan="2" class="thin-l thin-r thin-b center b editable" contenteditable="true">{{ $lessThisApplicationDisplay }}</td>
                    <td class="thin-l"></td>
                    <td></td>
                    <td class="med-r"></td>
                </tr>
                <tr style="height:8.202mm">
                    <td class="med-l"></td>
                    <td></td>
                    <td class="center i thin-l thin-r thin-b">Balance</td>
                    <td colspan="2" class="thin-l thin-r thin-b center b editable" contenteditable="true">{{ $balanceDisplay }}</td>
                    <td class="thin-l"></td>
                    <td></td>
                    <td class="med-r"></td>
                </tr>
                <tr style="height:6.5mm">
                    <td class="med-l"></td>
                    <td></td>
                    <td colspan="3" class="center b upper thin-t editable" contenteditable="true">{{ $hrName }}</td>
                    <td class="thin-l"></td>
                    <td></td>
                    <td class="center b upper med-r editable" contenteditable="true">{{ $chiefName }}</td>
                </tr>
                <tr style="height:4.5mm">
                    <td class="med-l"></td>
                    <td></td>
                    <td colspan="3" class="center top thin-r double-b editable" contenteditable="true">{{ $hrPosition }}</td>
                    <td class="double-b"></td>
                    <td colspan="2" class="center top med-r editable" contenteditable="true">{{ $chiefPosition }}</td>
                </tr>
                <tr style="height:5.5mm">
                    <td colspan="6" class="med-l double-t upper">APPROVED FOR:</td>
                    <td colspan="2" class="double-t med-r upper">DISAPPROVED DUE TO:</td>
                </tr>
                <tr style="height:4.8mm">
                    <td class="med-l"></td>
                    <td></td>
                    <td colspan="4"><span class="editable" contenteditable="true">{{ $isApproved ? $hoursDisplay : '_______' }}</span> day/s with pay</td>
                    <td></td>
                    <td class="med-r editable" contenteditable="true">{{ $isRejected ? $disapprovalReason : '___________________________________________' }}</td>
                </tr>
                <tr style="height:4.8mm">
                    <td class="med-l"></td>
                    <td></td>
                    <td colspan="4"></td>
                    <td></td>
                    <td class="med-r editable" contenteditable="true">___________________________________________</td>
                </tr>
                <tr style="height:24mm">
                    <td colspan="8" class="center b med-l med-r med-b">
                        <div style="height:4.5mm"></div>
                        <span class="editable" contenteditable="true">{{ $directorName }}</span><br>
                        _________________________________<br>
                        (Authorized Official)
                    </td>
                </tr>
            </tbody>
        </table>
    </main>

    @unless(request()->boolean('preview'))
        <script>
            const ctoReturnUrl = @js(route('my-cto.show', $ctoRequest));

            window.addEventListener('afterprint', () => {
                window.location.href = ctoReturnUrl;
            });

            window.addEventListener('load', () => {
                window.print();
            });
        </script>
    @endunless
</body>
</html>
