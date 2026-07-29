<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ContactAsBotController extends Controller
{
    private $botToken;

    private $apiUrl;

    public function __construct()
    {
        $this->botToken = config('services.telegram.contact_as_bot_token');
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}/sendMessage";
    }

    public function webhook(Request $request)
    {
        $update = $request->all();
        $message = $update['message'] ?? null;
        $chatId = $message['chat']['id'] ?? null;
        $text = $message['text'] ?? null;

        if($text === '/start') {
            $responseText = "Welcome to the Contact As Bot!";

            $this->sendMessage($chatId, $responseText);
        } 
        return response()->json([
            'ok' => true,
        ]);
    }

   public function sendMessage($chatId, $message)
    {
        return Http::post($this->apiUrl, [
            'chat_id' => $chatId,
            'text' => $message,
        ]);
    }


}
