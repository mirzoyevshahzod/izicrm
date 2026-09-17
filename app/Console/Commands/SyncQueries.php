<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncQueries extends Command
{
    protected $signature = 'queries:sync';
    protected $description = 'Sync queries from external API';

    public function handle()
    {
        $response = Http::withoutVerifying()
            ->timeout(30)
            ->get('https://crm.zanjeer.uz/api/v1/queries/ids');

        if (!$response->ok()) {
            $this->error('API error');
            return;
        }

        $statusMap = [
            'Одобрен'                       => 15,
            'В поиске перевозчика'          => 12,
            'Актуальный'                    => 11,
            'Ставка перевозчика предложена' => 13,
            'Жду ответа от клиента'         => 14,
            'Поставлен'                     => 16,
            'КП отправлено клиенту'         => 18,
            'Отмена'                        => 17,
        ];

        $apiItems = collect($response->json()['data'] ?? [])
            ->filter(fn($i) => isset($statusMap[$i['status']]));

        // DB dagi hozirgi holatni olamiz
        $dbItems = \App\Models\Query::pluck('status', 'custom_id'); // ['EGS001' => 15, ...]

        // Faqat o'zgargan yoki yangilarni ajratamiz
        $changed = $apiItems->filter(function ($i) use ($statusMap, $dbItems) {
            $newStatus = $statusMap[$i['status']];
            $oldStatus = $dbItems->get($i['custom_id']);

            return $oldStatus === null || (int)$oldStatus !== $newStatus; // yangi yoki o'zgargan
        });

        if ($changed->isEmpty()) {
            $this->info('No changes detected');
            return;
        }

        $rows = $changed->map(fn($i) => [
            'custom_id'   => $i['custom_id'],
            'status'      => $statusMap[$i['status']],
            'is_finished' => $statusMap[$i['status']] == 17 ? 1 : 0,
            'updated_at'  => now(),
            'created_at'  => now(),
        ])->values()->toArray();

        foreach (array_chunk($rows, 1000) as $chunk) {
            \App\Models\Query::upsert(
                $chunk,
                ['custom_id'],
                ['status', 'is_finished', 'updated_at']
            );
        }

        $this->info("Changed: {$changed->count()} | Total API: {$apiItems->count()}" . now());
    }
}
