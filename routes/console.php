<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('attendance:sync')
    ->everyMinute()
    ->withoutOverlapping(5)   // oldingi ishga tushish 5 daqiqadan ko'p davom etsa, lock avtomatik yechiladi
    ->runInBackground();


Schedule::command('attendance:detect-early-leave')
    ->dailyAt('19:00')
    ->withoutOverlapping();

Schedule::command('queries:sync')
    ->everyFiveMinutes()
    ->sendOutputTo(storage_path('logs/scheduler.log'));

// Har soatda bir marta (har soatning 28-daqiqasida)
Schedule::command('telegram:check-all')
    ->hourlyAt(16) // SCHEDULE_START_MINUTE qiymati
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/telegram-check-' . now()->format('Y-m-d') . '.log'));


Schedule::command('telegram:sync-finished')
    ->everyTwoHours()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/telegram-sync.log'));
