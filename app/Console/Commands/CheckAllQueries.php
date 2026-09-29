<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Query;
use App\Services\MonitorNotifier;
use Illuminate\Support\Facades\Cache;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use Illuminate\Support\Facades\Http;

class CheckAllQueries extends Command
{
    protected $signature = 'telegram:check-all';
    protected $description = 'Check all queries via Telegram search';

    public function handle()
    {
        $this->info("🚀 Starting Telegram check...");

        $startedAt = now();
        $checked = 0;
        $errors = [];

        try {
            $settings = new Settings;

            $settings->getAppInfo()
                ->setApiId((int) '24613586')
                ->setApiHash('30e63ed7236511cb1b3620ce0f2a5d33');

            $MadelineProto = new API(
                storage_path('app/session.madeline'),
                $settings
            );

            $MadelineProto->start();

            Query::query()
                ->where('is_finished', 0)
                ->chunk(50, function ($queries) use ($MadelineProto, &$checked, &$errors) {
                    foreach ($queries as $query) {
                        $search = $query->custom_id;

                        try {
                            $results = $MadelineProto->messages->searchGlobal([
                                'q' => $search,
                                'limit' => 100
                            ]);

                            $count = $count = $results['count'] ?? count($results['messages'] ?? []);
                            $query->update(['count' => $count]);

                            $response = Http::withoutVerifying()
                                ->post('https://crm.zanjeer.uz/api/v1/queries/tg', [
                                    'custom_id' => $query->custom_id,
                                    'telegram_group_count' => 331,
                                    'telegram_count' => $query->count
                                ]);
                            $this->info("Zanjeer API response status code: " . $response->status());
                            if (!$response->successful()) {
                                $errors[] = "{$query->custom_id}: Zanjeer API {$response->status()}";
                            }
                            $checked++;
                            $this->info("✅ {$query->custom_id} => {$count} (groups: 331) " . now());


                        } catch (\Throwable $e) {
                            \Log::error("Query {$query->id}: " . $e->getMessage());
                            $errors[] = "{$query->custom_id}: " . $e->getMessage();
                        }

                        usleep(500000); // 0.5 sekund
                    }
                });
            $this->info("🎉 Finished!");
        } catch (\Throwable $e) {
            \Log::error('telegram:check-all failed: ' . $e->getMessage());
            MonitorNotifier::send("❌ telegram:check-all to'xtab qoldi\n\n"
                . "Tekshirildi: $checked ta\n"
                . "Xato: " . $e->getMessage());
            exit(1);
        }

        // monitor:heartbeat shu vaqtga qarab komanda ishlayotganini tekshiradi
        Cache::forever('heartbeat:telegram:check-all', now()->toIso8601String());

        if ($errors) {
            MonitorNotifier::send("⚠️ telegram:check-all xatolar bilan tugadi\n\n"
                . "Tekshirildi: $checked ta, xato: " . count($errors) . " ta\n"
                . "Davomiyligi: " . $startedAt->diffForHumans(now(), true) . "\n\n"
                . implode("\n", array_slice($errors, 0, 10))
                . (count($errors) > 10 ? "\n... va yana " . (count($errors) - 10) . " ta" : ''));
        }

        gc_collect_cycles();

        exit(0);
    }
}
