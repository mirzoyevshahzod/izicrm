<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class TelegramService
{
     private $token;
    private $apiUrl;

    public function __construct()
    {
        $this->token  = config('services.telegram.egs_materialniy_otchet_bot_token');
        $this->apiUrl = "https://api.telegram.org/bot{$this->token}";
    }


    public function sendMessage(string $chatId, string $text, array $keyboard = []): void
    {

        $payload = [
            'chat_id'=> $chatId,
            'text' => $text,
        ];

        if(!empty($keyboard)){
            $payload['reply_markup'] = $keyboard;
        }

        Http::post($this->apiUrl . '/sendMessage', $payload);
    }

     public function editMessageText(int $chatId, int $messageId, string $text, array $keyboard = []): void
    {

        $payload = [
            'chat_id'=> $chatId,
            'message_id' => $messageId,
            'text' => $text,
        ];

        if(!empty($keyboard)){
            $payload['reply_markup'] = $keyboard;
        }

        Http::post($this->apiUrl . '/editMessageText', $payload);
    }



}