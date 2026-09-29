<?php

namespace App\Console\Commands;

use App\Services\MonitorNotifier;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CheckHeartbeats extends Command
{
    protected $signature = 'monitor:heartbeat';
    protected $description = 'Komandalar vaqtida ishlayotganini tekshiradi, to\'xtab qolsa monitor botga xabar yuboradi';

    public function handle()
    {
        foreach (config('monitor.heartbeats', []) as $command => $maxMinutes) {
            $key = "heartbeat:$command";
            $alertedKey = "heartbeat-alerted:$command";
            $last = Cache::get($key);

            // Birinchi marta ko'rilyapti — hisobni hozirdan boshlaymiz
            if (!$last) {
                Cache::forever($key, now()->toIso8601String());
                $this->line("[$command] birinchi tekshiruv, hisob boshlandi");
                continue;
            }

            $minutes = (int) Carbon::parse($last)->diffInMinutes(now());

            if ($minutes <= $maxMinutes) {
                if (Cache::pull($alertedKey)) {
                    MonitorNotifier::send("✅ $command yana ishlayapti\n\nOxirgi muvaffaqiyatli ishga tushish: " . Carbon::parse($last)->format('Y-m-d H:i'));
                }
                $this->line("[$command] OK ($minutes daqiqa oldin)");
                continue;
            }

            $this->warn("[$command] $minutes daqiqadan beri muvaffaqiyatli ishlamadi");

            // Bir marta xabar beramiz, qayta ishlaguncha takrorlamaymiz
            if (!Cache::has($alertedKey)) {
                MonitorNotifier::send("🚨 $command ishlamayapti\n\n"
                    . "Oxirgi muvaffaqiyatli ishga tushish: " . Carbon::parse($last)->format('Y-m-d H:i') . " ($minutes daqiqa oldin)\n"
                    . "Ruxsat etilgan: $maxMinutes daqiqa\n\n"
                    . "Tekshiring: scheduler (cron), withoutOverlapping lock, storage/logs");
                Cache::forever($alertedKey, true);
            }
        }
    }
}
