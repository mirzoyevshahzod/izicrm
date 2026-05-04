<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domain\Telegram\Services\TelegramService;
use App\Domain\Telegram\Services\TelegramServiceInterface;

class TelegramServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
