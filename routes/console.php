<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('reports:send-open-urgents-digest')
    ->dailyAt('08:00')
    ->timezone('America/Tijuana');

Schedule::command('reports:send-weekly-incidents-digest')
    ->weeklyOn(5, '13:00')
    ->timezone('America/Tijuana');

Schedule::command('reports:send-daily-patrols-digest')
    ->dailyAt('20:00')
    ->timezone('America/Tijuana');
