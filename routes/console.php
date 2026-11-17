<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('reports:capture-snapshots --month='.now()->subMonth()->format('Y-m'))
    ->monthlyOn(1, '00:10')
    ->withoutOverlapping();

Schedule::command('activity:prune-visits')
    ->dailyAt('01:30')
    ->withoutOverlapping();
