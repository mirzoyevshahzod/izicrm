<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramCandidateBotController extends Controller
{
    public function candidate(Request $request)
    {
        return $this->forward(
            $request,
            env('CANDIDATE_WEBHOOK_URL')
        );
    }

    public function express(Request $request)
    {
        return $this->forward(
            $request,
            env('CANDIDATE_EXPRESS_WEBHOOK_URL')
        );
    }

    /**
     * HH bot candidate.websol.uz ga ko'chdi. Telegram faqat webhook.izicrm.uz ga yeta oladi,
     * shuning uchun update shu yerdan uzatiladi. Manzil: CANDIDATE_HH_WEBHOOK_URL yoki
     * CANDIDATE_WEBHOOK_URL dagi host + /api/telegram/hh-webhook.
     * Secret sarlavhasi ham uzatiladi — candidate uni o'zining TELEGRAM_HH_BOT_SECRET bilan tekshiradi.
     */
    public function hh(Request $request)
    {
        $url = env('CANDIDATE_HH_WEBHOOK_URL')
            ?: preg_replace('#/api/.*$#', '/api/telegram/hh-webhook', (string) env('CANDIDATE_WEBHOOK_URL'));

        return $this->forward($request, $url, [
            'X-Telegram-Bot-Api-Secret-Token' => (string) $request->header('X-Telegram-Bot-Api-Secret-Token'),
        ]);
    }


    private function forward(Request $request, string $url, array $headers = [])
    {
        try {
            $response = Http::timeout(10)
                ->withHeaders($headers)
                ->acceptJson()
                ->asJson()
                ->post($url, $request->all());

            Log::info('Telegram webhook forwarded', [
                'url' => $url,
                'status' => $response->status(),
                'body' => $request->all(),
            ]);

            if ($response->successful()) {
                return response()->json([
                    'ok' => true,
                ]);
            }

            Log::error('Candidate webhook returned error', [
                'url' => $url,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return response()->json([
                'ok' => false,
            ], 500);

        } catch (\Throwable $e) {
            Log::error('Telegram webhook forwarding failed', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
            ], 500);
        }
    }
}
