<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WatchTelegramWebhooks extends Command
{
    protected $signature = 'telegram:webhook-watch {--dry-run : Faqat tekshiradi, setWebhook qilmaydi}';
    protected $description = 'Telegram botlar webhookini tekshiradi va uzilib qolsa qayta o\'rnatadi';

    public function handle()
    {
        $bots = config('telegram_webhooks.bots', []);
        $restored = [];

        foreach ($bots as $name => $bot) {
            $token = $bot['token'] ?? null;
            $expected = $bot['url'] ?? null;

            if (empty($token) || empty($expected)) {
                $this->warn("[$name] token yoki url sozlanmagan, o'tkazib yuborildi");
                continue;
            }

            $api = "https://api.telegram.org/bot{$token}";

            try {
                $info = Http::timeout(15)->get("$api/getWebhookInfo")->json('result');
            } catch (\Throwable $e) {
                $this->error("[$name] getWebhookInfo xatosi: {$e->getMessage()}");
                Log::warning("Webhook watch [$name] getWebhookInfo failed", ['error' => $e->getMessage()]);
                continue;
            }

            if ($info === null) {
                $this->error("[$name] getWebhookInfo javob bermadi (token noto'g'rimi?)");
                continue;
            }

            $current = $info['url'] ?? '';

            if ($current === $expected) {
                $line = "[$name] OK (pending: " . ($info['pending_update_count'] ?? 0) . ')';
                if (!empty($info['last_error_message'])) {
                    $line .= ' | oxirgi xato ' . date('Y-m-d H:i', $info['last_error_date']) . ': ' . $info['last_error_message'];
                }
                $this->line($line);
                continue;
            }

            $this->warn("[$name] webhook noto'g'ri: '" . ($current ?: 'bo\'sh') . "' → '$expected'");

            if ($this->option('dry-run')) {
                continue;
            }

            // drop_pending_updates yo'q — uzilish paytida kelgan xabarlar yo'qolmasin
            $response = Http::timeout(15)->asForm()->post("$api/setWebhook", ['url' => $expected]);

            if ($response->json('ok')) {
                $this->info("[$name] webhook qayta o'rnatildi");
                Log::warning("Webhook watch [$name] restored", ['was' => $current, 'now' => $expected]);
                $restored[] = "$name: " . ($current ?: 'bo\'sh') . " → $expected";
            } else {
                $this->error("[$name] setWebhook xatosi: " . $response->json('description'));
                Log::error("Webhook watch [$name] setWebhook failed", ['response' => $response->json()]);
            }
        }

        $this->notify($restored);
    }

    private function notify(array $restored): void
    {
        $chatId = config('telegram_webhooks.notify_chat_id');
        $token = config('services.telegram.telegram_e_ombor_bot_token');

        if (empty($restored) || empty($chatId) || empty($token)) {
            return;
        }

        try {
            Http::timeout(15)->asForm()->post("https://api.telegram.org/bot{$token}/sendMessage", [
                'chat_id' => $chatId,
                'text'    => "⚠️ Webhook uzilgan edi, qayta o'rnatildi:\n\n" . implode("\n", $restored),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Webhook watch notify failed', ['error' => $e->getMessage()]);
        }
    }
}
