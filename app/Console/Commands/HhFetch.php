<?php

namespace App\Console\Commands;

use App\Models\HhSearch;
use App\Services\HhFetcher;
use App\Services\HhService;
use App\Services\MonitorNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class HhFetch extends Command
{
    protected $signature = 'hh:fetch {--search= : Faqat shu filtr ID si}';
    protected $description = 'Faol hh filtrlari bo\'yicha yangi rezyumelarni olib, moslik foizini hisoblaydi';

    public function handle(HhFetcher $fetcher)
    {
        if (!HhService::isConnected()) {
            $this->warn('hh.uz ulanmagan (hh:auth-url), o\'tkazib yuborildi');
            return self::SUCCESS;
        }

        $searches = HhSearch::query()
            ->when($this->option('search'), fn ($q, $id) => $q->whereKey($id), fn ($q) => $q->where('is_active', true))
            ->get();

        foreach ($searches as $search) {
            try {
                $r = $fetcher->run($search);
                $this->info("[#{$search->id} {$search->name}] hh da: {$r['found']}, olindi: {$r['fetched']}, yangi: {$r['new']}");
            } catch (\Throwable $e) {
                $this->error("[#{$search->id} {$search->name}] {$e->getMessage()}");
                // Har soatda takror yubormaslik uchun faqat birinchi xatoda xabar beramiz
                if ($search->last_error === null) {
                    MonitorNotifier::send("hh:fetch [#{$search->id} {$search->name}] xato: " . $e->getMessage());
                }
                $search->forceFill(['last_error' => mb_substr($e->getMessage(), 0, 1000)])->save();
            }
        }

        Cache::forever('heartbeat:hh:fetch', now()->toIso8601String());

        return self::SUCCESS;
    }
}
