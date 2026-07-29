<?php

namespace App\Http\Controllers;

use App\Handlers\MaterialRequestCallbackHandler;
use App\Handlers\MaterialRequestMessageHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MaterialRequestController extends Controller
{
    public function __construct(
        private MaterialRequestMessageHandler $materialRequestMessageHandler,
        private MaterialRequestCallbackHandler $materialRequestCallbackHandler
    ) {}

    public function webhook(Request $request): JsonResponse
    {
	    $start = microtime(true);

	    Log::info('Webhook start');

        $update = $request->all();

        if (isset($update['message'])) {
            $this->materialRequestMessageHandler->handle($update['message']);
        }

        if (isset($update['callback_query'])) {
            $this->materialRequestCallbackHandler->handle($update['callback_query']);
        }

        Log::info('Webhook end', [
            'time' => microtime(true) - $start
        ]);

        return response()->json(['ok' => true]);

    }
}
