<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * IziCRM Monitor bot orqali TELEGRAM_MONITOR_CHAT_IDS dagi hamma chatlarga xabar yuboradi.
 */
class MonitorNotifier
{
    public static function send(string $text): void
    {
        $token = config('services.telegram.izicrm_monitor_bot_token');
        $chatIds = config('services.telegram.monitor_chat_ids', []);

        if (empty($token) || empty($chatIds)) {
            Log::warning('MonitorNotifier: token yoki chat id sozlanmagan', ['text' => $text]);
            return;
        }

        // Telegram xabar limiti 4096 belgi
        $text = Str::limit($text, 4000);

        foreach ($chatIds as $chatId) {
            try {
                $response = Http::timeout(15)->asForm()->post("https://api.telegram.org/bot{$token}/sendMessage", [
                    'chat_id' => trim($chatId),
                    'text'    => $text,
                ]);

                if (!$response->json('ok')) {
                    Log::warning("MonitorNotifier [$chatId] failed", ['response' => $response->json()]);
                }
            } catch (\Throwable $e) {
                Log::warning("MonitorNotifier [$chatId] failed", ['error' => $e->getMessage()]);
            }
        }
    }
}
