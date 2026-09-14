<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramMobileAppBotController extends Controller
{
    public function mobileApp(Request $request)
    {
        return $this->forward(
            $request,
            env('MOBILE_APP_WEBHOOK_URL')
        );
    }

    private function forward(Request $request, string $url)
    {
        try {
            $response = Http::timeout(10)
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
