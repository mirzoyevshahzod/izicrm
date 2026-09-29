<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramPostixBotController extends Controller
{
    public function postixWebhook(Request $request)
    {
        $url = config('services.telegram.postix_webhook_url');

        if (!$url) {
            Log::error('POSTIX_WEBHOOK_URL is not configured');

            return response()->json([
                'ok' => false,
            ], 500);
        }

        return $this->forward($request, $url);
    }

    private function forward(Request $request, string $url)
    {
        try {
            $response = Http::timeout(10)
                ->acceptJson()
                ->asJson()
                ->post($url, $request->all());

            if ($response->successful()) {
                return response()->json([
                    'ok' => true,
                ]);
            }

            Log::error('POSTIX webhook returned error', [
                'url' => $url,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return response()->json([
                'ok' => false,
            ], 500);

        } catch (\Throwable $e) {
            Log::error('POSTIX webhook forwarding failed', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return response()->json([
                'ok' => false,
            ], 500);
        }
    }
}
