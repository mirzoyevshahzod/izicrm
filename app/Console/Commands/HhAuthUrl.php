<?php

namespace App\Console\Commands;

use App\Services\HhService;
use Illuminate\Console\Command;

class HhAuthUrl extends Command
{
    protected $signature = 'hh:auth-url';
    protected $description = 'hh.uz ga ulanish havolasini chiqaradi (10 daqiqa amal qiladi)';

    public function handle()
    {
        if (empty(config('services.hh.client_id')) || empty(config('services.hh.client_secret'))) {
            $this->error('.env da HH_CLIENT_ID va HH_CLIENT_SECRET to\'ldirilmagan');
            return self::FAILURE;
        }

        $this->info("Havolani kompaniyaning hh.uz ish beruvchi akkaunti bilan oching:");
        $this->line(HhService::authorizeUrl());

        return self::SUCCESS;
    }
}
