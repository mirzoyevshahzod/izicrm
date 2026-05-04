<?php

namespace App\Services;

use App\Models\MaterialRequest;
use App\Repositories\MaterialRequestRepository;

class MaterialRequestService
{
    public function __construct(
        private TelegramService $telegram,
        private MaterialRequestRepository $materialRequestRepository
    ) {}

    public function findPendingRequest(int $chatId): ?MaterialRequest
    {
        return $this->materialRequestRepository->findPending($chatId);
    }

    public function createRequest(int $chatId, string $company, array $from): void
    {
        $this->materialRequestRepository->create($chatId, $company, $from);
    }

    public function completRequest(MaterialRequest $request, string $materialText): void
    {
        $this->materialRequestRepository->complete($request, $materialText);

        $this->notifyGroup($request, $materialText);
        $this->confirmToUser($request, $materialText);
    }

    public function cancelPendingRequest(int $chatId): void
    {
        $this->materialRequestRepository->deletePending($chatId);
    }

    private function notifyGroup(MaterialRequest $request, string $materialText): void
    {
        $groupId = config('services.telegram.telegram_material_request_group_id');

        $text =
            "📥 Yangi material so'rovi\n\n" .
            "👤 {$request->last_name} {$request->frist_name}\n" .
            "📱 @{$request->telegram_username}\n" .
            "🏢 Kompaniya: {$request->company}\n" .
            "📦 Material: {$materialText}\n" .
            "⏰ Sana: " . now()->format('Y-m-d H:i');

        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '✅ Tasdiqlash',
                        'callback_data' => 'completed_' . $request->id
                    ],
                    [
                        'text' => '❌ Rad Etish',
                        'callback_data' => 'rejected_' . $request->id
                    ],
                ],
            ],
        ];

        $this->telegram->sendMessage($groupId, $text, $keyboard);
    }

    private function confirmToUser(MaterialRequest $request, string $materialText): void
    {
        $text =
            "✅ Raxmat, sizga tez orada aloqaga chiqiladi !!!!!!\n\n" .
            "Qayta boshlash uchun /start bosing!\n";

        $this->telegram->sendMessage($request->chat_id, $text);
    }
}