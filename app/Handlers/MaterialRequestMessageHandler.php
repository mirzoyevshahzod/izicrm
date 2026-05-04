<?php

namespace App\Handlers;

use App\Builders\MaterialRequestKeyboardBuilder;
use App\Services\MaterialRequestService;
use App\Services\TelegramService;

class MaterialRequestMessageHandler
{

    public function __construct(
        private TelegramService $telegram,
        private MaterialRequestService $materialRequestService
    ) {}

    public function handle(array $message): void
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? null;

        if($text == '/start'){
            $this->sendWelcome($chatId);
            return;
        }

         $this->handleMaterialInput($chatId, $text);
    }

    private function sendWelcome(int $chatId): void
    {
        $text = "Assalomu aleykum. EGS botiga xush kelibsiz.\n\nKompaniya nomini tanlang:";
        $this->telegram->sendMessage(
            $chatId,
            $text,
            MaterialRequestKeyboardBuilder::companies(),
        );
    }

      private function handleMaterialInput(int $chatId, ?string $text): void
    {
        if (!$text) {
            return;
        }
 
        $request = $this->materialRequestService->findPendingRequest($chatId);
 
        if (!$request) {
            return;
        }
 
        $this->materialRequestService->completRequest($request, $text);
    }
}