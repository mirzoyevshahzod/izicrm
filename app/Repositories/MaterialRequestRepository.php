<?php

namespace App\Repositories;

use App\Models\MaterialRequest;

class MaterialRequestRepository
{
    public function findPending(int $chatId): ?MaterialRequest
    {
        return MaterialRequest::where('chat_id', $chatId)
            ->where('step', 'waiting_material')
            ->latest()
            ->first();
    }

    public function create(int $chatId, string $company, array $from): MaterialRequest
    {
        return MaterialRequest::create([
            'chat_id'           => $chatId,
            'last_name'         => $from['last_name'] ?? '',
            'frist_name'        => $from['first_name'] ?? '',
            'telegram_username' => $from['username'] ?? '',
            'company'           => $company,
            'step'              => 'waiting_material',
        ]);
    }

    public function complete(MaterialRequest $request, string $materialText): void
    {
        $request->update([
            'request_text' => $materialText,
            'requested_at' => now(),
            'step'         => 'done',
        ]);
    }

    public function deletePending(int $chatId): void
    {
        MaterialRequest::where('chat_id', $chatId)
            ->where('step', 'waiting_material')
            ->latest()
            ->first()
            ?->delete();
    }

    public function findById(int $id): ?MaterialRequest
    {
        return MaterialRequest::find($id);
    }

    public function updateStatus(MaterialRequest $request, string $status, string $admin): void
    {
        $request->update([
            'status' => $status,
            'approved_by_name' => $admin,
        ]);
    }
}