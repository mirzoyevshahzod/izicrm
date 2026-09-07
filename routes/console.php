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
