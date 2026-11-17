<?php

namespace App\Console\Commands;

use App\Models\ActivityAudit;
use Illuminate\Console\Command;

class PruneActivityVisits extends Command
{
    protected $signature = 'activity:prune-visits {--days= : Override the configured retention period}';

    protected $description = 'Archive expired page visits while preserving other audit events';

    public function handle(): int
    {
        $days = max(1, (int) ($this->option('days') ?: config('activity-tracking.retention_days', 365)));
        $archived = ActivityAudit::query()
            ->whereNotNull('visit_token')
            ->where('created_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Archived {$archived} page visit(s) after {$days} days.");

        return self::SUCCESS;
    }
}
