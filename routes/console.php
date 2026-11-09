<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Spatie\Health\Commands\RunHealthChecksCommand;
use Spatie\Health\Models\HealthCheckResultHistoryItem;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// All commands related about Health
Schedule::command(RunHealthChecksCommand::class)->everyMinute();

Schedule::command(
    'model:prune',
    ['--model' => [HealthCheckResultHistoryItem::class]]
)->daily();
