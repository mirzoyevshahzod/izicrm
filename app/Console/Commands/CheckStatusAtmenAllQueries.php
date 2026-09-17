<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Query;
use danog\MadelineProto\API;
use danog\MadelineProto\Settings;
use Illuminate\Support\Facades\Http;

class CheckStatusAtmenAllQueries extends Command
{
    protected $signature = 'app:check-status-atmen-all-queries';
    protected $description = 'Check all queries via Telegram search';

    public function handle()
    {
        $this->info("🚀 Starting Telegram check...");

        $settings = new Settings;

        $settings->getAppInfo()
            ->setApiId((int) env('TG_API_ID'))
            ->setApiHash(env('TG_API_HASH'));

        $MadelineProto = new API(
            storage_path('app/session.madeline'),
            $settings
        );

        $MadelineProto->start();

        Query::query()
            ->where('is_finished', 1)
            ->chunk(50, function ($queries) use ($MadelineProto) {
                foreach ($queries as $query) {
                    $search = $query->custom_id;

                    try {
                        $results = $MadelineProto->messages->searchGlobal([
                            'q' => $search,
                            'limit' => 100
                        ]);

                        $count = $count = $results['count'] ?? count($results['messages'] ?? []);
                        $query->update(['count' => $count]);


                        $this->info("✅ {$query->custom_id} => {$count} (groups: {331})" . now());


                    } catch (\Throwable $e) {
                        \Log::error("Query {$query->id}: " . $e->getMessage());
                    }

                    usleep(500000); // 0.5 sekund
                }
            });
        $this->info("🎉 Finished!");
    }
}
