<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Weekly AI model training — setiap Minggu jam 02:00
Schedule::command(AiTrainModels::class)
    ->weekly()
    ->sundays()
    ->at('02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->environments(['production', 'local']);

// Monthly stok minimum recalculation — tiap tanggal 1 jam 03:00
Schedule::command(KalkulasiStokMinimumCommand::class)
    ->monthlyOn(1, '03:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->environments(['production', 'local']);

// Daily backup cleanup — setiap hari jam 01:00
Schedule::command('backup:clean')
    ->daily()
    ->at('01:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->environments(['production', 'local']);

// Daily database backup — setiap hari jam 02:00
Schedule::command('backup:run --only-db')
    ->daily()
    ->at('02:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->environments(['production', 'local']);
