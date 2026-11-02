<?php

namespace App\Http\Controllers;

use App\Models\HhBotChat;
use App\Models\HhSearchResume;
use App\Models\User;
use App\Services\HhTelegramBot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * HH bot: Telegram webhook va panel sozlamalari (kim xabar oladi).
 *
 * Shaxsiy chat: HR panelda Profil → "Telegram'ni ulash" → t.me/<bot>?start=<token> — chat shu panel
 * foydalanuvchisiga bog'lanadi. Guruh: bot guruhga qo'shiladi, panelda bir marta yoqiladi.
 */
class HhBotController extends Controller
{
    private const LINK_TTL_MINUTES = 15;

    public function __construct(private HhTelegramBot $bot)
    {
    }

    // ---------- Telegram webhook ----------

    public function webhook(Request $request)
    {
        $secret = config('services.telegram.hh_bot_secret');
        if ($secret && !hash_equals($secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            return response()->json(['ok' => false], 403);
        }

        if ($member = $request->input('my_chat_member')) {
            $this->onMembership($member);
        } elseif ($callback = $request->input('callback_query')) {
            $this->onCallback($callback);
        } elseif ($message = $request->input('message')) {
            $this->onMessage($message);
        }

        return response()->json(['ok' => true]);
    }

    private function onMessage(array $message): void
    {
        $chat = $message['chat'] ?? [];
        $text = trim((string) ($message['text'] ?? ''));
        if (($chat['type'] ?? '') !== 'private' || $text === '') {
            return; // guruhdagi oddiy yozishmalarga javob bermaymiz
        }

        if (preg_match('/^\/start\s+(\S+)/', $text, $m)) {
            $userId = Cache::pull('hh_bot_link:' . $m[1]);
            $user = $userId ? User::find($userId) : null;

            if (!$user || !$user->can('hh.view')) {
                $this->bot->send($chat['id'], "⛔️ Havola eskirgan yoki noto‘g‘ri.\nPanelda <b>Profil → Telegram’ni ulash</b> tugmasini qayta bosing.");
                return;
            }

            // Bitta foydalanuvchi — bitta shaxsiy chat
            HhBotChat::where('user_id', $user->id)->where('chat_id', '!=', $chat['id'])->delete();
            HhBotChat::updateOrCreate(['chat_id' => $chat['id']], [
                'type' => 'private',
                'title' => trim(($chat['first_name'] ?? '') . ' ' . ($chat['last_name'] ?? '')),
                'username' => $chat['username'] ?? null,
                'user_id' => $user->id,
                'is_active' => true,
            ]);

            $this->bot->send($chat['id'], "✅ <b>Ulandi!</b>\n\nhh.uz dan har soatda kelgan yangi nomzodlar (moslik foizi yuqorilari) shu yerga yuboriladi.\nTugmalar orqali nomzodni tanlash yoki rad etish mumkin.");
            return;
        }

        $linked = HhBotChat::where('chat_id', $chat['id'])->where('is_active', true)->exists();
        $this->bot->send($chat['id'], $linked
            ? "ℹ️ Yangi nomzodlar avtomatik keladi. Qidiruvlar va sozlamalar — panelda:\n" . e($this->bot->panelUrl())
            : "👋 Bu bot kompaniya HR xodimlari uchun.\nUlash uchun panelda <b>Profil → Telegram’ni ulash</b> tugmasini bosing.");
    }

    private function onMembership(array $member): void
    {
        $chat = $member['chat'] ?? [];
        $status = $member['new_chat_member']['status'] ?? null;
        $inChat = in_array($status, ['member', 'administrator'], true);

        if (($chat['type'] ?? '') === 'private') {
            // Foydalanuvchi botni bloklasa — yubormaymiz
            if (!$inChat) {
                HhBotChat::where('chat_id', $chat['id'])->update(['is_active' => false]);
            }
            return;
        }

        if (!$inChat) {
            HhBotChat::where('chat_id', $chat['id'])->delete();
            return;
        }

        $record = HhBotChat::firstOrNew(['chat_id' => $chat['id']]);
        $record->fill(['type' => $chat['type'] ?? 'group', 'title' => $chat['title'] ?? null])->save();

        if ($record->wasRecentlyCreated) {
            $this->bot->send($chat['id'], "👋 Salom! Bu guruhga hh.uz dagi yangi nomzodlar yuborilishi uchun panelda <b>HH nomzodlar → Telegram</b> bo‘limida guruhni yoqing.");
        }
    }

    private function onCallback(array $callback): void
    {
        $data = (string) ($callback['data'] ?? '');
        $chatId = $callback['message']['chat']['id'] ?? null;
        $messageId = $callback['message']['message_id'] ?? null;

        if (!preg_match('/^hh:([sr]):(\d+)$/', $data, $m) || !$chatId) {
            $this->bot->answerCallback($callback['id']);
            return;
        }

        $allowed = HhBotChat::where('chat_id', $chatId)->where('is_active', true)->exists();
        $candidate = HhSearchResume::with(['resume', 'search'])->find($m[2]);
        if (!$allowed || !$candidate) {
            $this->bot->answerCallback($callback['id'], $allowed ? 'Nomzod topilmadi' : 'Ruxsat yo‘q');
            return;
        }

        $selected = $m[1] === 's';
        $candidate->update(['status' => $selected ? 'selected' : 'rejected']);

        $who = trim(($callback['from']['first_name'] ?? '') . ' ' . ($callback['from']['last_name'] ?? '')) ?: 'HR';
        $label = ($selected ? '✅ Tanlandi' : '❌ Rad etildi') . ' · ' . $who;
        $this->bot->editKeyboard($chatId, $messageId, $this->bot->candidateKeyboard($candidate->search, $candidate, $label));
        $this->bot->answerCallback($callback['id'], $selected ? 'Tanlanganlarga qo‘shildi' : 'Rad etildi');
    }

    // ---------- Panel: o'z Telegram chatini ulash (Profil) ----------

    public function myTelegram(Request $request)
    {
        $user = $request->user();
        $chat = HhBotChat::where('user_id', $user->id)->first();

        return response()->json([
            'available' => HhTelegramBot::isConfigured() && $user->can('hh.view'),
            'linked' => (bool) $chat?->is_active,
            'username' => $chat?->username,
            'title' => $chat?->title,
        ]);
    }

    public function linkTelegram(Request $request)
    {
        abort_unless(HhTelegramBot::isConfigured() && $request->user()->can('hh.view'), 403);

        $username = $this->bot->username();
        abort_unless($username, 503, __('Bot sozlanmagan.'));

        $token = Str::random(32);
        Cache::put('hh_bot_link:' . $token, $request->user()->id, now()->addMinutes(self::LINK_TTL_MINUTES));

        return response()->json(['url' => "https://t.me/{$username}?start={$token}"]);
    }

    public function unlinkTelegram(Request $request)
    {
        HhBotChat::where('user_id', $request->user()->id)->delete();

        return response()->json(['message' => __('Telegram uzildi.')]);
    }

    // ---------- Panel: xabar oluvchilar ro'yxati (HH nomzodlar sahifasi) ----------

    public function chats()
    {
        return response()->json([
            'configured' => HhTelegramBot::isConfigured(),
            'bot' => HhTelegramBot::isConfigured() ? $this->bot->username() : null,
            'chats' => HhBotChat::with('user:id,name')->orderBy('type')->orderBy('title')->get(),
        ]);
    }

    public function updateChat(Request $request, HhBotChat $chat)
    {
        $chat->update($request->validate(['is_active' => 'required|boolean']));

        return response()->json($chat);
    }

    public function destroyChat(HhBotChat $chat)
    {
        $chat->delete();

        return response()->json(['message' => __('O‘chirildi.')]);
    }

    /** Sozlash paytida tekshirish uchun: barcha faol chatlarga sinov xabari */
    public function test()
    {
        $chats = $this->bot->recipients();
        $ok = 0;
        foreach ($chats as $chat) {
            $ok += $this->bot->send($chat->chat_id, '🔔 Sinov xabari: HH bot ishlayapti. Yangi nomzodlar shu yerga keladi.') ? 1 : 0;
        }

        return response()->json(['sent' => $ok, 'total' => $chats->count()]);
    }
}
