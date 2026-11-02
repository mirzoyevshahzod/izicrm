<?php

namespace App\Http\Controllers;

use App\Services\HhService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HhAuthController extends Controller
{
    /**
     * hh.uz OAuth callback. Havola `php artisan hh:auth-url` orqali olinadi.
     */
    public function callback(Request $request, HhService $hh)
    {
        if (!HhService::checkState($request->query('state'))) {
            return response("Havola eskirgan yoki noto'g'ri. `php artisan hh:auth-url` bilan yangisini oling.", 403);
        }

        if ($request->filled('error')) {
            return response("hh.uz ruxsat bermadi: " . $request->query('error'), 400);
        }

        try {
            $token = $hh->exchangeCode((string) $request->query('code'));
        } catch (\Throwable $e) {
            Log::error('hh callback failed', ['error' => $e->getMessage()]);
            return response("Ulanishda xato: " . $e->getMessage(), 500);
        }

        return response("hh.uz ulandi: {$token->employer_name} (employer_id: {$token->employer_id}). Oynani yopishingiz mumkin.");
    }
}
