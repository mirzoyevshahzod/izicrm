<?php

namespace App\Handlers;

use App\Builders\MaterialRequestKeyboardBuilder;
use App\Repositories\MaterialRequestRepository;
use App\Services\MaterialRequestService;
use App\Services\TelegramService;

class MaterialRequestCallbackHandler
{
    public function __construct(
        private TelegramService $telegram,
        private MaterialRequestService $materialRequestService,
        private MaterialRequestRepository $materialRequestRepository
    ) {}

    public function handle(array $callback): void
    {
        $chatId    = $callback['message']['chat']['id'];
        $messageId = $callback['message']['message_id'];
        $data      = $callback['data'];

        if ($data === 'back_to_companies') {
            $this->handleBack($chatId, $messageId);
            return;
        }

        if (str_starts_with($data, 'completed_') || str_starts_with($data, 'rejected_')) {

            [$action, $requestId] = explode('_', $data, 2);
            $admin = $callback['from'];

            $adminName = ($admin['last_name'] ?? '') . ' ' . ($admin['first_name'] ?? '');
            $adminUsername = $admin['username'] ?? null;
            $adminText = $adminUsername 
                ? "@{$adminUsername}" 
                : trim($adminName);

            $request = $this->materialRequestRepository->findById($requestId);

            if (!$request) return;

            if ($action === 'completed') {
                $text = "✅ Sizning so‘rovingiz tasdiqlandi!";
                $status = 'completed';
                $groupText =
                    "✅ Tasdiqlandi\n\n" .
                    "👤 {$request->last_name} {$request->frist_name}\n" .
                    "📱 @{$request->telegram_username}\n" .
                    "🏢 Kompaniya: {$request->company}\n" .
                    "📦 Material: {$request->request_text}\n" .
                    "⏰ Sana: " . $request->requested_at . "\n".
                    "👨‍💼 Admin: {$adminText}";
            } else {
                $text = "❌ Sizning so‘rovingiz rad etildi!";
                $status = 'rejected';
                $groupText =
                    "❌ Rad etildi\n\n" .
                    "👤 {$request->last_name} {$request->frist_name}\n" .
                    "📱 @{$request->telegram_username}\n" .
                    "🏢 Kompaniya: {$request->company}\n" .
                    "📦 Material: {$request->request_text}\n" .
                    "⏰ Sana: " . $request->requested_at . "\n" .
                    "👨‍💼 Admin: {$adminText}";
            }

            // 🔥 USERGA XABAR

            $this->materialRequestRepository->updateStatus($request, $status, $adminText);
            $this->telegram->sendMessage($request->chat_id, $text);

             $this->telegram->editMessageText(
                $chatId,
                $messageId,
                $groupText,
                ['inline_keyboard' => []]
            );

            return; // ❗ MUHIM
        }

        $this->handleCompanySelected($chatId, $messageId, $data, $callback['from']);
    }

    private function handleBack(int $chatId, int $messageId): void
    {
        $this->materialRequestService->cancelPendingRequest($chatId);

        $this->telegram->editMessageText(
            $chatId,
            $messageId,
            "Assalomu aleykum. EGS botiga xush kelibsiz.\n\nKompaniya nomini tanlang:",
            MaterialRequestKeyboardBuilder::companies()
        );
    }

    private function handleCompanySelected(int $chatId, int $messageId, string $company, array $from): void
    {
        $this->materialRequestService->createRequest($chatId, $company, $from);

        $text =
            "Qanday material kerakligini yozib qoldiring.\n\n" .
            "Iltimos, quyidagilarni ko'rsating:\n" .
            "- Material nomi\n" .
            "- Miqdori (soni)\n" .
            "- Qaysi maqsadda foydalaniladi.";

        $this->telegram->editMessageText(
            $chatId,
            $messageId,
            $text,
            MaterialRequestKeyboardBuilder::backToCompanies()
        );
    }
}