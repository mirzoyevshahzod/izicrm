<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Query;
use Illuminate\Support\Facades\Http;

class SyncFinishedQueries extends Command
{
    protected $signature = 'telegram:sync-finished';
    protected $description = 'Send finished queries to API';

    public function handle()
    {
        $this->info("🚀 Syncing finished queries...");

        Query::query()
            ->where('is_finished', 1)
            ->where('is_synced', 1)
            ->chunk(50, function ($queries) {

                foreach ($queries as $query) {
                    try {
                        $response = Http::withoutVerifying()
                            ->post('https://crm.zanjeer.uz/api/v1/queries/tg', [
                                'custom_id' => $query->custom_id,
                                'telegram_group_count' => 331,
                                'telegram_count' => $query->count
                            ]);
                        $this->info($response->status());
                        if ($response->ok()) {
                            $query->update([
                                'is_synced' => 1
                            ]);

                            $this->info("✅ Synced: {$query->custom_id} " . now());
                        } else {
                            \Log::error('API error', [
                                'query_id' => $query->id,
                                'response' => $response->body()
                            ]);
                        }

                    } catch (\Throwable $e) {
                        \Log::error("Query {$query->id}: " . $e->getMessage());
                    }
                }
            });

        $this->info("🎉 Done!");
    }
}
