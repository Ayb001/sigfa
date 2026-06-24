<?php

use App\Console\Commands\ComputePredictions;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Nightly AI prediction job
Schedule::command(ComputePredictions::class)->dailyAt('01:00');
