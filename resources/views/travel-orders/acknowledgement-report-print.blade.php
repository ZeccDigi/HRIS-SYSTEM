<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acknowledgement Report</title>
    <style>
        @page { size: A4; margin: 18mm; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #111827; background: #fff; }
        .sheet { width: 100%; min-height: 260mm; }
        .meta { text-align: right; font-size: 11px; line-height: 1.5; }
        h1 { margin: 30px 0 4px; text-align: center; font-size: 20px; letter-spacing: .08em; text-transform: uppercase; }
        .subtitle { margin: 0 0 28px; text-align: center; font-size: 12px; color: #4b5563; }
        .grid { width: 100%; border-collapse: collapse; font-size: 12px; }
        .grid th, .grid td { border: 1px solid #111827; padding: 8px 10px; vertical-align: top; }
        .grid th { width: 30%; background: #f3f4f6; text-align: left; text-transform: uppercase; font-size: 10px; letter-spacing: .08em; }
        .section-title { margin: 22px 0 8px; font-size: 11px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        .box { min-height: 70px; border: 1px solid #111827; padding: 10px; font-size: 12px; line-height: 1.45; white-space: pre-line; }
        .signature { margin-top: 48px; display: grid; grid-template-columns: 1fr 1fr; gap: 48px; font-size: 12px; text-align: center; }
        .line { border-top: 1px solid #111827; padding-top: 6px; }
        .footer { position: fixed; bottom: 8mm; left: 18mm; right: 18mm; font-size: 10px; color: #6b7280; text-align: right; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
@php
    $employeeName = trim(($travelOrder->employee?->firstname ?? '') . ' ' . ($travelOrder->employee?->lastname ?? '')) ?: 'N/A';
    $taNumber = $travelOrder->ta_number ?: 'TA-' . optional($travelOrder->created_at)->format('Y-m-d') . '-' . str_pad((string) $travelOrder->id, 3, '0', STR_PAD_LEFT);
    $tarDeadline = $travelOrder->tar_deadline ?: optional($travelOrder->travel_date_end)->copy()?->addDays(5);
@endphp
<div class="sheet">
    <div class="meta">
        <strong>Department of Migrant Workers - Region XI</strong><br>
        Generated: {{ now()->format('M d, Y h:i A') }}
    </div>

    <h1>Acknowledgement Report</h1>
    <p class="subtitle">Official travel post-completion report summary</p>

    <table class="grid">
        <tr><th>TA Number</th><td>{{ $taNumber }}</td></tr>
        <tr><th>Employee</th><td>{{ $employeeName }}</td></tr>
        <tr><th>Position</th><td>{{ $travelOrder->employee?->position ?: 'N/A' }}</td></tr>
        <tr><th>Destination</th><td>{{ $travelOrder->places_of_travel ?: 'N/A' }}</td></tr>
        <tr>
            <th>Travel Period</th>
            <td>
                {{ $travelOrder->travel_date_start?->format('M d, Y') ?? 'N/A' }}
                @if($travelOrder->travel_date_end && $travelOrder->travel_date_start && $travelOrder->travel_date_end->ne($travelOrder->travel_date_start))
                    - {{ $travelOrder->travel_date_end->format('M d, Y') }}
                @endif
            </td>
        </tr>
        <tr><th>AR Deadline</th><td>{{ $tarDeadline?->format('M d, Y') ?? 'N/A' }}</td></tr>
        <tr><th>Submitted</th><td>{{ $travelOrder->tar_submitted_at?->format('M d, Y h:i A') ?? 'Not submitted' }}</td></tr>
        <tr><th>Status</th><td>{{ ucfirst((string) ($travelOrder->tar_status ?: 'pending')) }}</td></tr>
    </table>

    <p class="section-title">Purpose of Travel</p>
    <div class="box">{{ $travelOrder->purpose ?: 'N/A' }}</div>

    <p class="section-title">Employee Remarks</p>
    <div class="box">{{ $travelOrder->tar_remarks ?: 'No remarks provided.' }}</div>

    <p class="section-title">Attached Document</p>
    <div class="box">
        @if($travelOrder->tar_attachment_path)
            A PDF acknowledgement report was uploaded and stored in the HRIS records.
        @else
            No uploaded acknowledgement report is attached.
        @endif
    </div>

    <div class="signature">
        <div class="line">Employee Signature / Date</div>
        <div class="line">Received By / Date</div>
    </div>
</div>

<div class="footer">Acknowledgement Report - {{ $taNumber }}</div>
<script>
    window.addEventListener('load', () => window.print());
</script>
</body>
</html>
