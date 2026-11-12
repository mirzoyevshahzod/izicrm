<?php

namespace App\Services\Davomat;

use App\Models\Davomat\DavomatChat;
use App\Models\Davomat\DavomatEmployee;
use App\Models\Davomat\DavomatReason;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * egs_davomat_bot: xodim bir marta raqamini yuboradi (CRM dagi korporativ raqam bilan
 * solishtiriladi), keyin "Kechikasizmi?" / "Kelmaysizmi?" ni bosib sababini yozadi.
 * Sabab davomat_reasons ga tushadi, CRM uni olib ketib bugungi "Причина" ga yozadi.
 */
class DavomatBot
{
    public const BUTTONS = [
        'late' => 'Kechikasizmi?',
        'absent' => 'Kelmaysizmi?',
    ];

    private const SHARE_PHONE_BUTTON = '📱 Raqamni yuborish';

    /**
     * @param  array<string, mixed>  $update  Telegram webhook update
     */
    public function handle(array $update): void
    {
        $message = $update['message'] ?? null;

        if (! is_array($message) || ($message['chat']['type'] ?? null) !== 'private' || ! isset($message['from']['id'])) {
            return;
        }

        $chat = DavomatChat::query()->firstOrCreate([
            'telegram_id' => (int) $message['from']['id'],
        ]);

        if (isset($message['contact'])) {
            $this->link($chat, $message['contact']);

            return;
        }

        $employee = $chat->employee;

        if ($employee === null) {
            $this->askPhone($chat);

            return;
        }

        $text = trim((string) ($message['text'] ?? ''));
        $reason = array_search($text, self::BUTTONS, true);

        if ($text === '/start') {
            $chat->update(['pending_reason' => null]);
            $this->menu($chat, 'Kechikadigan yoki kelmaydigan bo‘lsangiz, quyidagi tugmalardan birini bosing.');
        } elseif ($reason !== false) {
            $chat->update(['pending_reason' => $reason]);
            $this->send($chat->telegram_id, 'Sababini kiriting:', ['remove_keyboard' => true]);
        } elseif ($chat->pending_reason === null) {
            $this->menu($chat, 'Quyidagi tugmalardan birini tanlang.');
        } elseif ($text === '') {
            $this->send($chat->telegram_id, 'Sababini matn ko‘rinishida yozing:');
        } else {
            DavomatReason::query()->create([
                'crm_user_id' => $employee->crm_user_id,
                'telegram_id' => $chat->telegram_id,
                'type' => $chat->pending_reason,
                'reason' => Str::limit($text, 1900),
                'date' => now()->toDateString(),
            ]);

            $chat->update(['pending_reason' => null]);
            $this->menu($chat, 'Rahmat! Sababingiz qabul qilindi.');
        }
    }

    /**
     * @param  array<string, mixed>  $contact
     */
    private function link(DavomatChat $chat, array $contact): void
    {
        if ((int) ($contact['user_id'] ?? 0) !== $chat->telegram_id) {
            $this->askPhone($chat, 'Iltimos, o‘zingizning raqamingizni pastdagi tugma orqali yuboring.');

            return;
        }

        $phone = '+'.preg_replace('/\D/', '', (string) ($contact['phone_number'] ?? ''));
        $employee = DavomatEmployee::query()->where('phone', $phone)->first();

        $chat->update([
            'phone' => $phone,
            'crm_user_id' => $employee?->crm_user_id,
            'pending_reason' => null,
        ]);

        if ($employee === null) {
            $this->send($chat->telegram_id, 'Siz EGS Group xodimi emassiz.', ['remove_keyboard' => true]);

            return;
        }

        $this->menu($chat, "Rahmat, {$employee->name}! Endi kechikadigan yoki kelmaydigan bo‘lsangiz, shu yerdan xabar bering.");
    }

    private function askPhone(DavomatChat $chat, ?string $text = null): void
    {
        $this->send(
            $chat->telegram_id,
            $text ?? 'Assalomu alaykum! Xodimligingizni tasdiqlash uchun pastdagi «'.self::SHARE_PHONE_BUTTON.'» tugmasini bosing.',
            [
                'keyboard' => [[['text' => self::SHARE_PHONE_BUTTON, 'request_contact' => true]]],
                'resize_keyboard' => true,
                'one_time_keyboard' => true,
            ]
        );
    }

    private function menu(DavomatChat $chat, string $text): void
    {
        $this->send($chat->telegram_id, $text, [
            'keyboard' => array_map(
                static fn (string $button): array => [['text' => $button]],
                array_values(self::BUTTONS)
            ),
            'resize_keyboard' => true,
            'is_persistent' => true,
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $replyMarkup
     */
    private function send(int $chatId, string $text, ?array $replyMarkup = null): void
    {
        Http::timeout(15)->post(
            'https://api.telegram.org/bot'.config('services.telegram.davomat_bot_token').'/sendMessage',
            array_filter([
                'chat_id' => $chatId,
                'text' => $text,
                'reply_markup' => $replyMarkup,
            ], static fn (mixed $value): bool => $value !== null)
        )->throw();
    }
}
