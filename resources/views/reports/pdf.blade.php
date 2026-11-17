<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    @php
        $columnCount = count($report['columns'] ?? []);
        $isWide = $columnCount > 6;
        $isCompact = $columnCount > 5;
        $summaryCharts = collect($report['charts'] ?? [])
            ->filter(fn ($chart) => ! empty($chart['items']))
            ->take(3)
            ->values();
    @endphp
    <style>
        @page { margin: 12mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111; font-family: Arial, Helvetica, sans-serif; font-size: 10pt; line-height: 1.25; }
        .header-wrap { width: 100%; text-align: center; }
        .header-image { display: inline-block; width: 92%; height: auto; max-width: 100%; margin: 0 auto 3mm; }
        .title-block { width: 100%; margin: 0 0 3.5mm; border-bottom: .8pt solid #111; }
        .title-block td { padding: 0 0 2.5mm; vertical-align: bottom; }
        .title-block p { margin: 0; }
        .title-block .report-heading { width: 58%; text-align: left; }
        .title-block .report-period-cell { width: 42%; text-align: right; }
        .report-type { font-size: 13pt; font-weight: bold; letter-spacing: .4px; text-transform: uppercase; }
        .sub-report { margin-top: 1mm; font-size: 10pt; font-weight: bold; text-transform: uppercase; }
        .report-period { margin-top: 2mm; color: #374151; font-size: 9pt; }
        .formal-table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 10pt; }
        .formal-table thead { display: table-header-group; }
        .formal-table tr { page-break-inside: avoid; }
        .formal-table th, .formal-table td { border: .55pt solid #111; padding: 5px 6px; overflow-wrap: anywhere; vertical-align: middle; }
        .formal-table th { background: #f1f1f1; font-size: 9pt; font-weight: bold; text-align: center; text-transform: uppercase; }
        .formal-table td { text-align: left; }
        .formal-table .empty-cell { padding: 16mm 4mm; color: #4b5563; font-style: italic; text-align: center; }
        .compact .formal-table, .compact .formal-table th, .compact .formal-table td { font-size: 8.25pt; }
        .compact .formal-table th, .compact .formal-table td { padding: 4px; }
        .summary { margin-top: 5mm; padding-top: 2.5mm; border-top: .8pt solid #111; }
        .summary-heading { margin: 0 0 2mm; font-size: 10pt; font-weight: bold; text-transform: uppercase; }
        .metric-grid { width: 100%; border-collapse: collapse; margin-bottom: 3mm; }
        .metric-grid td { width: 25%; min-height: 12mm; padding: 2mm; border: .55pt solid #111; vertical-align: top; }
        .metric-label { display: block; color: #475569; font-size: 8pt; font-weight: bold; text-transform: uppercase; }
        .metric-value { display: block; margin-top: .5mm; font-size: 11pt; font-weight: bold; }
        .chart-layout { width: 100%; border-collapse: separate; border-spacing: 2mm 0; margin: 0 -2mm; }
        .chart-layout td { width: 33.33%; padding: 0; vertical-align: top; }
        .chart-card { min-height: 38mm; padding: 2mm; border: .55pt solid #111; page-break-inside: avoid; }
        .chart-title { margin: 0 0 1mm; font-size: 8pt; font-weight: bold; text-transform: uppercase; }
        .chart-row { margin-top: 1mm; }
        .chart-label { display: block; overflow: hidden; font-size: 7.5pt; text-overflow: ellipsis; white-space: nowrap; }
        .chart-track { height: 2.5mm; margin-top: .5mm; background: #e5e7eb; }
        .chart-bar { height: 100%; min-width: 1px; }
        .chart-value { float: right; margin-top: -2.2mm; padding-left: 1mm; background: #fff; font-size: 7pt; font-weight: bold; }
        .donut-layout { width: 100%; border-collapse: collapse; }
        .donut-layout td { width: auto; padding: 0; vertical-align: middle; }
        .donut-visual { width: 28mm !important; padding-right: 2mm !important; }
        .donut-chart { display: block; width: 26mm; height: 26mm; }
        .donut-legend-row { margin: 1mm 0; font-size: 7.5pt; line-height: 1.2; }
        .donut-key { display: inline-block; width: 2.5mm; height: 2.5mm; margin-right: 1mm; vertical-align: -0.35mm; }
        .donut-total { display: block; margin-top: .5mm; color: #475569; font-size: 7pt; font-weight: bold; text-transform: uppercase; }
        .document-footer { margin-top: 8mm; padding-top: 2mm; border-top: .55pt solid #111; color: #4b5563; font-size: 8pt; }
        .footer-right { float: right; }
    </style>
</head>
<body>
    <main class="{{ $isCompact ? 'compact' : '' }}">
        @if($headerImage)
            <div class="header-wrap"><img class="header-image" src="{{ $headerImage }}" alt="Department of Migrant Workers Region XI"></div>
        @endif

        <table class="title-block" role="presentation"><tr>
            <td class="report-heading">
                <p class="report-type">{{ $reportTypeLabel }}</p>
                <p class="sub-report">{{ $subReportLabel }}</p>
            </td>
            <td class="report-period-cell">
                <p class="report-period">
                    Period: {{ \Carbon\Carbon::parse($reportData['filters']['from'])->format('F d, Y') }} to {{ \Carbon\Carbon::parse($reportData['filters']['to'])->format('F d, Y') }}
                    @if(($reportData['filters']['division'] ?? 'All Divisions') !== 'All Divisions')<br>Division: {{ $reportData['filters']['division'] }} @endif
                    @if(($reportData['filters']['employmentStatus'] ?? 'All Employment Status') !== 'All Employment Status')<br>Status: {{ $reportData['filters']['employmentStatus'] }} @endif
                </p>
            </td>
        </tr></table>

        <table class="formal-table">
            <thead><tr>@foreach($report['columns'] as $column)<th>{{ $column }}</th>@endforeach</tr></thead>
            <tbody>
                @forelse($report['rows'] as $row)
                    <tr>@foreach($row['cells'] as $cell)<td>{{ $cell !== null && $cell !== '' ? $cell : 'N/A' }}</td>@endforeach</tr>
                @empty
                    <tr><td colspan="{{ max(count($report['columns']), 1) }}" class="empty-cell">No records found for this report.</td></tr>
                @endforelse
            </tbody>
        </table>

        @if(!empty($report['metrics']) || $summaryCharts->isNotEmpty())
            <section class="summary">
                <h2 class="summary-heading">Report Summary and Visual Analysis</h2>
                @if(!empty($report['metrics']))
                    <table class="metric-grid"><tr>
                        @foreach(collect($report['metrics'])->take(8)->chunk(4) as $metricRow)
                            @foreach($metricRow as $metric)
                                <td><span class="metric-label">{{ $metric['label'] }}</span><span class="metric-value">{{ $metric['value'] }}</span></td>
                            @endforeach
                            @for($blank = $metricRow->count(); $blank < 4; $blank++)<td></td>@endfor
                            @if(! $loop->last)</tr><tr>@endif
                        @endforeach
                    </tr></table>
                @endif

                @if($summaryCharts->isNotEmpty())
                    <table class="chart-layout"><tr>
                        @foreach($summaryCharts as $chart)
                            @php
                                $items = collect($chart['items'])->take(8)->values();
                                $maxValue = max(1, $items->max('value'));
                            @endphp
                            <td><section class="chart-card">
                                <h3 class="chart-title">{{ $chart['title'] }}</h3>
                                @if(($chart['type'] ?? 'bar') === 'doughnut')
                                    @php
                                        $total = max(1, (float) $items->sum('value'));
                                        $circumference = 251.327;
                                        $offset = 0.0;
                                        $svg = ['<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100">'];
                                        $svg[] = '<circle cx="50" cy="50" r="40" fill="none" stroke="#e5e7eb" stroke-width="18"/>';
                                        foreach ($items as $item) {
                                            $value = max(0, (float) $item['value']);
                                            $dash = ($value / $total) * $circumference;
                                            $color = $item['color'] ?? '#0038a8';
                                            if ($dash > 0) {
                                                $svg[] = '<circle cx="50" cy="50" r="40" fill="none" stroke="'.$color.'" stroke-width="18" stroke-dasharray="'.$dash.' '.$circumference.'" stroke-dashoffset="-'.$offset.'" transform="rotate(-90 50 50)"/>';
                                                $offset += $dash;
                                            }
                                        }
                                        $svg[] = '<text x="50" y="56" text-anchor="middle" font-family="Arial, Helvetica, sans-serif" font-size="18" font-weight="700" fill="#172033">'.number_format($total, 0).'</text>';
                                        $svg[] = '</svg>';
                                        $donutImage = 'data:image/svg+xml;base64,'.base64_encode(implode('', $svg));
                                    @endphp
                                    <table class="donut-layout" role="presentation"><tr>
                                        <td class="donut-visual">
                                            <img class="donut-chart" src="{{ $donutImage }}" alt="{{ $chart['title'] }}">
                                        </td>
                                        <td>
                                            @foreach($items as $item)
                                                <div class="donut-legend-row"><span class="donut-key" style="background-color: {{ $item['color'] ?? '#0038a8' }};"></span>{{ $item['shortLabel'] ?? $item['label'] }}: <strong>{{ rtrim(rtrim(number_format((float) $item['value'], 3, '.', ''), '0'), '.') }}</strong></div>
                                            @endforeach
                                            <span class="donut-total">Total: {{ rtrim(rtrim(number_format($total, 3, '.', ''), '0'), '.') ?: '0' }}</span>
                                        </td>
                                    </tr></table>
                                @else
                                    @foreach($items as $item)
                                        @php($width = min(100, max(0, ((float) $item['value'] / $maxValue) * 100)))
                                        <div class="chart-row">
                                            <span class="chart-label">{{ $item['shortLabel'] ?? $item['label'] }}</span>
                                            <div class="chart-track"><div class="chart-bar" style="width: {{ $width }}%; background-color: {{ $item['color'] ?? '#0038a8' }};"></div></div>
                                            <span class="chart-value">{{ rtrim(rtrim(number_format((float) $item['value'], 3, '.', ''), '0'), '.') }}</span>
                                        </div>
                                    @endforeach
                                @endif
                            </section></td>
                        @endforeach
                        @for($blank = $summaryCharts->count(); $blank < 3; $blank++)<td></td>@endfor
                    </tr></table>
                @endif
            </section>
        @endif

        <footer class="document-footer">
            <span>Generated by DMW Human Resource Information System</span>
            <span class="footer-right">Date Printed: {{ now()->format('F d, Y h:i A') }}</span>
        </footer>
    </main>
</body>
</html>
