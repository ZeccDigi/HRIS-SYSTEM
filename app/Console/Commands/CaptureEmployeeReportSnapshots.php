<?php

namespace App\Console\Commands;

use App\Services\EmployeeReportSnapshotService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CaptureEmployeeReportSnapshots extends Command
{
    protected $signature = 'reports:capture-snapshots {--month= : Month to capture, in YYYY-MM format}';

    protected $description = 'Capture monthly employee, leave balance, and CTO report history.';

    public function handle(EmployeeReportSnapshotService $snapshots): int
    {
        $month = $this->option('month');

        try {
            $snapshotMonth = $month ? Carbon::createFromFormat('Y-m', (string) $month)->startOfMonth() : now()->startOfMonth();
        } catch (\Throwable) {
            $this->error('Use the YYYY-MM format for --month, for example 2026-11.');

            return self::FAILURE;
        }

        $count = $snapshots->captureForMonth($snapshotMonth);
        $this->info("Captured {$count} employee report snapshots for {$snapshotMonth->format('F Y')}. ");

        return self::SUCCESS;
    }
}
