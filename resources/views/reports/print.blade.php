<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $subReportLabel }} - DMW HRIS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #e5e7eb; color: #111827; font-family: Arial, Helvetica, sans-serif; }
        .report-sheet { width: 277mm; min-height: 190mm; margin: 8mm auto; padding: 9mm 11mm; background: #fff; box-shadow: 0 2px 14px rgba(15, 23, 42, .14); }
        .official-header { display: grid; grid-template-columns: 29mm 1fr 29mm; align-items: center; border-bottom: 1.2px solid #111827; padding-bottom: 3mm; }
        .official-logo { width: 25mm; height: 25mm; object-fit: contain; justify-self: center; }
        .header-center { text-align: center; font-family: Georgia, 'Times New Roman', serif; line-height: 1.12; }
        .republic { font-size: 12pt; font-weight: 700; }
        .department { margin-top: 1mm; font-size: 20pt; font-weight: 900; letter-spacing: -.4px; }
        .office-address { margin-top: 1.5mm; font-size: 9pt; font-weight: 700; }
        .contact-line { margin-top: 1mm; font-family: Arial, Helvetica, sans-serif; font-size: 7.5pt; font-weight: 400; }
        .logo-caption { margin-top: 1mm; text-align: center; color: #0038a8; font-size: 7pt; font-weight: 800; }
        .report-heading { padding: 5mm 0 3mm; text-align: center; }
        .report-type { font-size: 9pt; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px; color: #334155; }
        .sub-report { margin-top: 1mm; font-family: Georgia, 'Times New Roman', serif; font-size: 17pt; font-weight: 800; text-transform: uppercase; }
        .report-period { margin-top: 1.5mm; font-size: 8pt; color: #475569; }
        .charts { display: grid; grid-template-columns: repeat(3, 1fr); gap: 3mm; margin: 2mm 0 5mm; page-break-inside: avoid; }
        .chart-panel { height: 48mm; border: 1px solid #94a3b8; padding: 2.5mm; }
        .chart-title { height: 5mm; margin: 0 0 1mm; text-align: center; font-size: 8pt; font-weight: 700; text-transform: uppercase; }
        .chart-canvas { position: relative; height: 37mm; }
        .section-title { margin: 0 0 2mm; padding: 2mm 2.5mm; border: 1px solid #64748b; background: #e2e8f0; font-size: 9pt; font-weight: 800; text-transform: uppercase; letter-spacing: .5px; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; font-size: 6.7pt; }
        thead { display: table-header-group; }
        th, td { border: 1px solid #64748b; padding: 1.5mm 1.2mm; vertical-align: top; overflow-wrap: anywhere; }
        th { background: #e2e8f0; text-align: left; font-size: 6.5pt; font-weight: 800; text-transform: uppercase; }
        tr { page-break-inside: avoid; }
        .empty { padding: 8mm; text-align: center; color: #64748b; }
        .document-footer { display: flex; justify-content: space-between; margin-top: 3mm; padding-top: 2mm; border-top: 1px solid #94a3b8; font-size: 7pt; color: #475569; }
        .screen-actions { position: fixed; right: 18px; top: 18px; display: flex; gap: 8px; }
        .screen-actions button { border: 1px solid #0038a8; background: #0038a8; color: white; padding: 10px 16px; font-weight: 700; cursor: pointer; }
        .screen-actions .secondary { background: white; color: #0038a8; }
        @media print {
            body { background: #fff; }
            .report-sheet { width: auto; min-height: 0; margin: 0; padding: 0; box-shadow: none; }
            .screen-actions { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="screen-actions">
        <button type="button" class="secondary" onclick="window.close()">Close</button>
        <button type="button" onclick="window.print()">Print / Save PDF</button>
    </div>

    <main class="report-sheet">
        <header class="official-header">
            <div>
                <img class="official-logo" src="{{ asset('images/dmw.png') }}" alt="DMW Logo">
                <div class="logo-caption">TAHANAN NG OFW</div>
            </div>
            <div class="header-center">
                <div class="republic">Republic of the Philippines</div>
                <div class="department">Department of Migrant Workers</div>
                <div class="office-address">3rd Floor NCCC Mall, Km. 7 Tigatto Road, Barangay Buhangin, Davao City</div>
                <div class="contact-line">Website: dmw.gov.ph | Email: davao@dmw.gov.ph | Hotline: 0931 029 8009</div>
            </div>
            <div>
                <img class="official-logo" src="{{ asset('images/bagong-pilipinas-logo.png') }}" alt="Bagong Pilipinas Logo">
                <div class="logo-caption">BAGONG PILIPINAS</div>
            </div>
        </header>

        <section class="report-heading">
            <div class="report-type">{{ $reportTypeLabel }}</div>
            <div class="sub-report">{{ $subReportLabel }}</div>
            <div class="report-period">
                Reporting period: {{ \Carbon\Carbon::parse($reportData['filters']['from'])->format('F d, Y') }} to {{ \Carbon\Carbon::parse($reportData['filters']['to'])->format('F d, Y') }}
                &nbsp;|&nbsp; Division: {{ $reportData['filters']['division'] }}
            </div>
        </section>

        <section class="charts">
            @foreach(array_slice($report['charts'] ?? [], 0, 3) as $index => $chart)
                <div class="chart-panel">
                    <h2 class="chart-title">{{ $chart['title'] }}</h2>
                    <div class="chart-canvas"><canvas id="reportChart{{ $index }}"></canvas></div>
                </div>
            @endforeach
        </section>

        <section>
            <h2 class="section-title">Detailed Report</h2>
            <table>
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
                                <td>{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td class="empty" colspan="{{ max(count($report['columns']), 1) }}">No records found for this report.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>

        <footer class="document-footer">
            <span>Generated by DMW Human Resource Information System</span>
            <span>{{ now()->format('F d, Y h:i A') }}</span>
        </footer>
    </main>

    <script>
        window.addEventListener('load', () => {
            const charts = @json(array_slice($report['charts'] ?? [], 0, 3));
            const colors = ['#0038a8', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];

            charts.forEach((chart, index) => {
                const canvas = document.getElementById(`reportChart${index}`);
                if (!canvas || !window.Chart) return;
                const items = chart.items || [];
                const type = chart.type === 'doughnut' ? 'doughnut' : (chart.type === 'line' ? 'line' : 'bar');

                new window.Chart(canvas, {
                    type,
                    data: {
                        labels: items.map(item => item.shortLabel || item.label),
                        datasets: [{
                            data: items.map(item => Number(item.value || 0)),
                            backgroundColor: type === 'doughnut' ? items.map((item, i) => item.color || colors[i % colors.length]) : '#0038a8',
                            borderColor: type === 'line' ? '#0038a8' : '#ffffff',
                            borderWidth: type === 'line' ? 2 : 1,
                            tension: .25,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        animation: false,
                        plugins: { legend: { display: type === 'doughnut', position: 'right', labels: { boxWidth: 8, font: { size: 8 } } } },
                        scales: type === 'doughnut' ? {} : { x: { ticks: { font: { size: 7 } } }, y: { beginAtZero: true, ticks: { precision: 0, font: { size: 7 } } } },
                    },
                });
            });

            setTimeout(() => window.print(), 500);
        });
    </script>
</body>
</html>
