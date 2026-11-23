<?php

namespace App\Http\Controllers;

use App\Services\Davomat\DavomatBot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * egs_davomat_bot webhook (POST /api/telegram/davomat-webhook) — mantiq DavomatBot da.
 */
class TelegramDavomatController extends Controller
{
    public function webhook(Request $request, DavomatBot $bot): JsonResponse
    {
        $secret = (string) config('services.telegram.davomat_bot_secret');

        if ($secret !== '' && ! hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            return response()->json(['ok' => false], 403);
        }

        try {
            $bot->handle($request->all());
        } catch (Throwable $exception) {
            // Telegram xato javobda bir xil update ni qayta-qayta yuboradi — 200 qaytaramiz.
            Log::error('Davomat bot update failed', [
                'update_id' => $request->input('update_id'),
                'error' => $exception->getMessage(),
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
