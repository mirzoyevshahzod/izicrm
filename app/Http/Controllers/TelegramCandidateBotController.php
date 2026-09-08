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
