<?php

namespace App\Services;

use App\Models\HhBotChat;
use App\Models\HhSearch;
use App\Models\HhSearchResume;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HH bot: yangi nomzodlarni HR larning shaxsiy chatlari va guruhlarga yuboradi.
 */
class HhTelegramBot
{
    /** Bir qidiruvda bundan ko'p yangi nomzod bo'lsa — eng yaxshilari + qisqa jamlanma */
    private const DIGEST_AFTER = 10;
    private const DIGEST_TOP = 5;

    public function __construct(private HhService $hh)
    {
    }

    public static function isConfigured(): bool
    {
        return !empty(config('services.telegram.hh_bot_token'));
    }

    public function username(): ?string
    {
        if (!self::isConfigured()) {
            return null;
        }

        // Xato bo'lsa ham qisqa muddat eslab qolamiz — har so'rovda Telegram'ga bormaslik uchun
        $name = Cache::get('hh_bot:username');
        if ($name === null) {
            $name = $this->call('getMe')['result']['username'] ?? '';
            Cache::put('hh_bot:username', $name, $name ? now()->addDay() : now()->addMinutes(5));
        }

        return $name ?: null;
    }

    /**
     * Qidiruvning yangi nomzodlarini (chegaradan yuqorisini) faol chatlarga yuboradi.
     *
     * @param  int[]  $linkIds  hh_search_resumes.id — shu ishga tushirishda yangi qo'shilganlar
     */
    public function notify(HhSearch $search, array $linkIds): int
    {
        if (!self::isConfigured() || !$search->notify || !$linkIds) {
            return 0;
        }

        $candidates = HhSearchResume::with('resume')
            ->whereIn('id', $linkIds)
            ->where('score', '>=', $search->notify_min_score)
            ->orderByDesc('score')
            ->get();

        $chats = $this->recipients();
        if ($candidates->isEmpty() || $chats->isEmpty()) {
            return 0;
        }

        $detailed = $candidates->count() > self::DIGEST_AFTER ? $candidates->take(self::DIGEST_TOP) : $candidates;
        $rest = $candidates->count() - $detailed->count();
        $sent = 0;

        foreach ($chats as $chat) {
            foreach ($detailed as $candidate) {
                $sent += $this->sendCandidate($chat->chat_id, $search, $candidate) ? 1 : 0;
                usleep(60_000); // Telegram: guruhga daqiqasiga ~20 xabar
            }
            if ($rest > 0) {
                $this->send($chat->chat_id,
                    "🔎 <b>" . e($search->name) . "</b> bo‘yicha yana <b>$rest</b> ta nomzod ({$search->notify_min_score}% dan yuqori).",
                    [[['text' => '📋 Hammasini panelda ko‘rish', 'url' => $this->panelUrl($search)]]]);
            }
        }

        return $sent;
    }

    /** Xabar oladiganlar: faol guruhlar va hali hh.view ruxsati bor HR larning shaxsiy chatlari */
    public function recipients(): Collection
    {
        return HhBotChat::with('user')
            ->where('is_active', true)
            ->get()
            ->filter(fn (HhBotChat $c) => !$c->isPrivate() || ($c->user && $c->user->can('hh.view')))
            ->values();
    }

    public function sendCandidate(int $chatId, HhSearch $search, HhSearchResume $candidate): bool
    {
        return (bool) $this->send($chatId, $this->candidateText($search, $candidate), $this->candidateKeyboard($search, $candidate));
    }

    public function candidateText(HhSearch $search, HhSearchResume $candidate): string
    {
        $r = $candidate->resume;
        $d = $r->data ?? [];
        $dict = $this->dictionaries();
        $name = fn (string $list, ?string $id, ?string $fallback = null) => collect($dict[$list] ?? [])->firstWhere('id', $id)['name'] ?? $fallback;

        $score = $candidate->score;
        $lines = [($score >= 85 ? '🟢' : '🟡') . " <b>{$score}%</b> · <b>" . e($r->title ?: 'Lavozim ko‘rsatilmagan') . '</b>'];
        $lines[] = '🔎 ' . e($search->name);

        $person = array_filter([
            $r->age ? "{$r->age} yosh" : null,
            mb_strtolower((string) $name('gender', $r->gender, $d['gender']['name'] ?? null)) ?: null,
        ]);
        $area = collect($dict['areas'] ?? [])->firstWhere('id', $r->area_id)['name'] ?? $r->area_name;
        $lines[] = '👤 ' . e(implode(', ', $person) ?: '—') . ($area ? ' · 📍 ' . e($area) : '');

        $exp = $r->experience_months !== null ? 'Tajriba: ' . $this->months($r->experience_months) : 'Tajriba yo‘q';
        $salary = $r->salary_amount ? number_format($r->salary_amount, 0, '.', ' ') . ' ' . $r->salary_currency : 'maosh ko‘rsatilmagan';
        $lines[] = '💼 ' . e($exp) . ' · 💰 ' . e($salary);

        if ($job = $d['experience'][0] ?? null) {
            $period = $this->month($job['start'] ?? null) . ' – ' . ($job['end'] ? $this->month($job['end']) : 'hozirgacha');
            $lines[] = '🏢 ' . e(trim(($job['position'] ?? '') . ' · ' . ($job['company'] ?? ''), ' ·')) . " ($period)";
        }

        $edu = $name('education_level', $r->education_level, $d['education']['level']['name'] ?? null);
        if ($edu) {
            $place = $d['education']['primary'][0]['name'] ?? null;
            $lines[] = '🎓 ' . e($edu . ($place ? " · $place" : ''));
        }

        if ($status = $name('job_search_statuses_employer', $d['job_search_status']['id'] ?? null, $d['job_search_status']['name'] ?? null)) {
            $lines[] = '🔥 ' . e($status);
        }

        return implode("\n", $lines);
    }

    public function candidateKeyboard(HhSearch $search, HhSearchResume $candidate, ?string $doneLabel = null): array
    {
        $rows = [];
        if ($candidate->resume->alternate_url) {
            $rows[] = [['text' => '🔗 hh.uz da ochish', 'url' => $candidate->resume->alternate_url]];
        }
        $rows[] = $doneLabel
            ? [['text' => $doneLabel, 'callback_data' => 'hh:x']]
            : [
                ['text' => '✅ Tanlash', 'callback_data' => "hh:s:{$candidate->id}"],
                ['text' => '❌ Rad etish', 'callback_data' => "hh:r:{$candidate->id}"],
            ];
        $rows[] = [['text' => '📋 Panelda ko‘rish', 'url' => $this->panelUrl($search)]];

        return $rows;
    }

    public function send(int $chatId, string $text, array $keyboard = []): ?array
    {
        $params = ['chat_id' => $chatId, 'text' => $text, 'parse_mode' => 'HTML', 'disable_web_page_preview' => true];
        if ($keyboard) {
            $params['reply_markup'] = json_encode(['inline_keyboard' => $keyboard]);
        }

        $result = $this->call('sendMessage', $params);
        if (!($result['ok'] ?? false)) {
            $this->handleSendError($chatId, $result);
            return null;
        }

        return $result['result'];
    }

    public function editKeyboard(int $chatId, int $messageId, array $keyboard): void
    {
        $this->call('editMessageReplyMarkup', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'reply_markup' => json_encode(['inline_keyboard' => $keyboard]),
        ]);
    }

    public function answerCallback(string $id, string $text = ''): void
    {
        $this->call('answerCallbackQuery', ['callback_query_id' => $id, 'text' => $text]);
    }

    public function panelUrl(?HhSearch $search = null): string
    {
        return rtrim(config('panel.url'), '/') . ($search ? "/panel/hh/searches/{$search->id}" : '/panel/hh');
    }

    public function call(string $method, array $params = []): array
    {
        $token = config('services.telegram.hh_bot_token');

        try {
            return Http::timeout(15)->asForm()->post("https://api.telegram.org/bot{$token}/{$method}", $params)->json() ?? [];
        } catch (\Throwable $e) {
            Log::warning("HH bot $method failed", ['error' => $e->getMessage()]);
            return ['ok' => false, 'description' => $e->getMessage()];
        }
    }

    /** Bot bloklangan yoki guruhdan chiqarilgan bo'lsa — chatni o'chirib qo'yamiz */
    private function handleSendError(int $chatId, array $result): void
    {
        $code = $result['error_code'] ?? null;
        Log::warning('HH bot sendMessage failed', ['chat_id' => $chatId, 'response' => $result]);

        if ($code === 403 || ($code === 400 && str_contains($result['description'] ?? '', 'chat not found'))) {
            HhBotChat::where('chat_id', $chatId)->update(['is_active' => false]);
        }
    }

    /** Nomlar uchun hh spravochniklari (o'zbekcha), xato bo'lsa rezyumedagi nomlar ishlatiladi */
    private function dictionaries(): array
    {
        try {
            return $this->hh->dictionaries() + ['areas' => $this->hh->uzbekistanAreas()];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function months(int $months): string
    {
        $y = intdiv($months, 12);
        $m = $months % 12;

        return trim(($y ? "$y yil " : '') . ($m ? "$m oy" : '')) ?: '1 oydan kam';
    }

    private function month(?string $date): string
    {
        return $date ? substr($date, 5, 2) . '.' . substr($date, 0, 4) : '';
    }
}
