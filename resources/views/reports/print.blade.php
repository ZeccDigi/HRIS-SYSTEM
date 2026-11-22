<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subReportLabel }} - {{ $reportTypeLabel }}</title>

    @php
        $columnCount = count($report['columns'] ?? []);
        $isWide = $columnCount > 6;
        $isCompact = $columnCount > 5;
        $printCharts = collect($report['charts'] ?? [])
            ->filter(fn ($chart) => !empty($chart['items']))
            ->take(3)
            ->values();
    @endphp

    <style id="print-page-style">
        @page {
            size: {{ $isWide ? 'landscape' : 'portrait' }};
            margin: 1.5cm;
        }
    </style>

    <style>
        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            color: #000000;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            line-height: 1.3;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        body {
            background-color: #f1f5f9;
            padding: 24px 0 48px;
        }

        .screen-actions {
            position: fixed;
            top: 16px;
            right: 20px;
            z-index: 1000;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .screen-actions button {
            padding: 8px 16px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            font-weight: bold;
            border-radius: 6px;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.15s ease-in-out;
        }

        .screen-actions .btn-primary {
            background-color: #0038a8;
            color: #ffffff;
            border: 1px solid #002d86;
        }

        .screen-actions .btn-primary:hover {
            background-color: #002d86;
        }

        .screen-actions .btn-secondary {
            background-color: #ffffff;
            color: #334155;
            border: 1px solid #cbd5e1;
        }

        .screen-actions .btn-secondary:hover {
            background-color: #f8fafc;
        }

        .screen-actions .btn-toggle {
            background-color: #ffffff;
            color: #0038a8;
            border: 1px solid #93c5fd;
        }

        .screen-actions .btn-toggle:hover {
            background-color: #eff6ff;
        }

        .report-sheet {
            width: 100%;
            max-width: {{ $isWide ? '297mm' : '210mm' }};
            min-height: {{ $isWide ? '210mm' : '297mm' }};
            margin: 0 auto;
            background: #ffffff;
            padding: 1.5cm;
            box-sizing: border-box;
            box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
        }

        .report-sheet.landscape {
            max-width: 297mm;
            min-height: 210mm;
        }

        .official-header {
            width: 100%;
            display: block;
            height: auto;
            max-height: 34mm;
            object-fit: contain;
            object-position: center;
            margin: 0 auto 4mm;
        }

        /* Report Title & Sub-Report */
        .report-title-section {
            text-align: center;
            margin-bottom: 5mm;
        }

        .report-type {
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #111827;
        }

        .sub-report {
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 1.5mm;
            color: #000000;
        }

        .report-period {
            font-size: 10pt;
            color: #374151;
            margin-top: 2mm;
        }

        .meta-dot {
            margin: 0 2mm;
            color: #6b7280;
        }

        /* Formal Table (No rounded corners, straight solid borders) */
        .formal-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1px solid #000000;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            margin-top: 3mm;
        }

        .formal-table thead {
            display: table-header-group;
        }

        .formal-table tr {
            page-break-inside: avoid;
        }

        .formal-table th,
        .formal-table td {
            border: 1px solid #000000;
            padding: 5px 6px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10pt;
            line-height: 1.25;
            overflow-wrap: anywhere;
            border-radius: 0 !important;
        }

        .formal-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
            vertical-align: middle;
        }

        .formal-table td {
            vertical-align: middle;
            text-align: left;
            background-color: #ffffff;
        }

        .formal-table td.empty-cell {
            text-align: center;
            padding: 18mm 4mm;
            font-style: italic;
            color: #4b5563;
        }

        .compact-report .formal-table,
        .compact-report .formal-table th,
        .compact-report .formal-table td {
            font-size: 8.5pt;
        }

        .compact-report .formal-table th,
        .compact-report .formal-table td {
            padding: 4px 5px;
        }

        .visual-summary {
            margin-top: 8mm;
            border-top: 1px solid #000000;
            padding-top: 4mm;
            page-break-inside: avoid;
        }

        .visual-summary-title {
            margin: 0 0 3mm;
            font-size: 10pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .metric-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            border: 1px solid #000000;
            border-right: 0;
            border-bottom: 0;
            margin-bottom: 5mm;
        }

        .metric-item {
            min-height: 16mm;
            padding: 3mm;
            border-right: 1px solid #000000;
            border-bottom: 1px solid #000000;
        }

        .metric-label {
            display: block;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .metric-value {
            display: block;
            margin-top: 1mm;
            font-size: 12pt;
            font-weight: bold;
        }

        .chart-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 4mm;
        }

        .chart-card {
            border: 1px solid #000000;
            padding: 3mm;
            page-break-inside: avoid;
        }

        .chart-title {
            margin: 0 0 2mm;
            font-size: 8.5pt;
            font-weight: bold;
            text-transform: uppercase;
        }

        .chart-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 14mm;
            align-items: center;
            gap: 2mm;
            margin-top: 1.5mm;
            font-size: 7.5pt;
        }

        .chart-label {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .chart-bar-track {
            height: 3mm;
            margin-top: 0.8mm;
            background: #e5e7eb;
        }

        .chart-bar {
            height: 100%;
            min-width: 1px;
        }

        .chart-value {
            text-align: right;
            font-weight: bold;
        }

        /* Document Footer */
        .document-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8mm;
            padding-top: 2mm;
            border-top: 1px solid #000000;
            font-size: 9pt;
            color: #4b5563;
            page-break-inside: avoid;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            .screen-actions {
                display: none !important;
            }

            .report-sheet {
                width: 100% !important;
                max-width: 100% !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="screen-actions">
        <button type="button" class="btn-toggle" onclick="toggleOrientation()">
            {{ $isWide ? 'Switch to Portrait' : 'Switch to Landscape' }}
        </button>
        <button type="button" class="btn-secondary" onclick="window.close()">Close</button>
        <button type="button" class="btn-primary" onclick="window.print()">Print / Save PDF</button>
    </div>

    <main class="report-sheet {{ $isWide ? 'landscape' : '' }} {{ $isCompact ? 'compact-report' : '' }}">
        <img class="official-header" src="{{ asset('images/leave-form-header.png') }}" alt="Department of Migrant Workers Region XI official header">

        {{-- Report Type and Sub-Report --}}
        <section class="report-title-section">
            <div class="report-type">{{ $reportTypeLabel }}</div>
            <div class="sub-report">{{ $subReportLabel }}</div>
            <div class="report-period">
                <span>Period: {{ \Carbon\Carbon::parse($reportData['filters']['from'])->format('F d, Y') }} to {{ \Carbon\Carbon::parse($reportData['filters']['to'])->format('F d, Y') }}</span>
                @if(!empty($reportData['filters']['division']) && $reportData['filters']['division'] !== 'All Divisions')
                    <span class="meta-dot">&bull;</span>
                    <span>Division: {{ $reportData['filters']['division'] }}</span>
                @endif
                @if(!empty($reportData['filters']['employmentStatus']) && $reportData['filters']['employmentStatus'] !== 'All Employment Status')
                    <span class="meta-dot">&bull;</span>
                    <span>Status: {{ $reportData['filters']['employmentStatus'] }}</span>
                @endif
            </div>
        </section>

        {{-- Formal Table (No rounded corners, crisp black borders, Arial 11pt) --}}
        <table class="formal-table">
            <thead>
                <tr>
                    @foreach($report['columns'] as $column)
                        <th>{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($report['rows'] as $row)
                    <tr>
                        @foreach($row['cells'] as $cell)
                            <td>{{ $cell !== null && $cell !== '' ? $cell : 'N/A' }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ max(count($report['columns']), 1) }}" class="empty-cell">
                            No records found for this report.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if(!empty($report['metrics']) || $printCharts->isNotEmpty())
            <section class="visual-summary" aria-label="Visual report summary">
                <h2 class="visual-summary-title">DMW Report Summary</h2>

                @if(!empty($report['metrics']))
                    <div class="metric-grid">
                        @foreach(collect($report['metrics'])->take(8) as $metric)
                            <div class="metric-item">
                                <span class="metric-label">{{ $metric['label'] }}</span>
                                <span class="metric-value">{{ $metric['value'] }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif

                @if($printCharts->isNotEmpty())
                    <div class="chart-grid">
                        @foreach($printCharts as $chart)
                            @php($maxValue = max(1, collect($chart['items'])->max('value')))
                            <section class="chart-card">
                                <h3 class="chart-title">{{ $chart['title'] }}</h3>
                                @foreach(collect($chart['items'])->take(8) as $item)
                                    @php($width = min(100, max(0, ((float) $item['value'] / $maxValue) * 100)))
                                    <div class="chart-row" title="{{ $item['fullLabel'] ?? $item['label'] }}">
                                        <div>
                                            <span class="chart-label">{{ $item['shortLabel'] ?? $item['label'] }}</span>
                                            <div class="chart-bar-track"><div class="chart-bar" style="width: {{ $width }}%; background: {{ $item['color'] ?? '#0038a8' }};"></div></div>
                                        </div>
                                        <span class="chart-value">{{ rtrim(rtrim(number_format((float) $item['value'], 3, '.', ''), '0'), '.') }}</span>
                                    </div>
                                @endforeach
                            </section>
                        @endforeach
                    </div>
                @endif
            </section>
        @endif

        {{-- Formal Footer --}}
        <footer class="document-footer">
            <span>Generated by DMW Human Resource Information System</span>
            <span>Date Printed: {{ now()->format('F d, Y h:i A') }}</span>
        </footer>
    </main>

    <script>
        function toggleOrientation() {
            const sheet = document.querySelector('.report-sheet');
            const styleTag = document.getElementById('print-page-style');
            const btn = document.querySelector('.btn-toggle');
            const isLandscape = sheet.classList.toggle('landscape');

            if (isLandscape) {
                styleTag.innerHTML = '@page { size: landscape; margin: 1.5cm; }';
                btn.textContent = 'Switch to Portrait';
            } else {
                styleTag.innerHTML = '@page { size: portrait; margin: 1.5cm; }';
                btn.textContent = 'Switch to Landscape';
            }
        }
    </script>
</body>
</html>
