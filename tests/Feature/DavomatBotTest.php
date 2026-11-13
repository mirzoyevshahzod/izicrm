<?php

namespace Tests\Feature;

use App\Models\Davomat\DavomatChat;
use App\Models\Davomat\DavomatEmployee;
use App\Models\Davomat\DavomatReason;
use App\Services\Davomat\DavomatBot;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * egs_davomat_bot: xodim raqamini yuboradi, tugmani bosadi, sababini yozadi —
 * sabab CRM olib ketishi uchun saqlanadi. Telegram soxtalashtirilgan.
 */
class DavomatBotTest extends TestCase
{
    private const SECRET = 'webhook-secret';

    private const CRM_KEY = 'crm-key';

    private const LATE = DavomatBot::BUTTONS['late'];

    private const ABSENT = DavomatBot::BUTTONS['absent'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', ['--path' => [
            'database/migrations/2026_11_12_140000_create_davomat_bot_tables.php',
            'database/migrations/2026_11_12_180000_add_pending_date_to_davomat_chats_table.php',
        ]]);

        config([
            'services.telegram.davomat_bot_token' => 'test-token',
            'services.telegram.davomat_bot_secret' => self::SECRET,
            'services.davomat.crm_api_key' => self::CRM_KEY,
        ]);

        Carbon::setTestNow('2026-11-12 08:10:00');
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        DavomatEmployee::query()->create(['crm_user_id' => 77, 'name' => 'ALI VALIYEV', 'phone' => '+998901234567']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function botReceives(array $message, int $telegramId = 555): void
    {
        $this->postJson('/api/telegram/davomat-webhook', [
            'update_id' => 1,
            'message' => ['chat' => ['id' => $telegramId, 'type' => 'private'], 'from' => ['id' => $telegramId]] + $message,
        ], ['X-Telegram-Bot-Api-Secret-Token' => self::SECRET])->assertOk();
    }

    private function linked(): void
    {
        DavomatChat::query()->create(['telegram_id' => 555, 'crm_user_id' => 77]);
    }

    /**
     * @return list<string>
     */
    private function replies(): array
    {
        return Http::recorded()
            ->map(fn (array $pair) => $pair[0])
            ->filter(fn (HttpRequest $request) => str_ends_with($request->url(), '/sendMessage'))
            ->map(fn (HttpRequest $request) => $request['text'])
            ->values()
            ->all();
    }

    public function test_it_asks_for_the_phone_first(): void
    {
        $this->botReceives(['text' => '/start']);

        $this->assertStringContainsString('Raqamni yuborish', $this->replies()[0]);
        Http::assertSent(fn (HttpRequest $request) => $request['reply_markup']['keyboard'][0][0]['request_contact'] === true);
    }

    public function test_it_links_the_corporate_number_and_shows_both_buttons(): void
    {
        $this->botReceives(['contact' => ['phone_number' => '998901234567', 'user_id' => 555]]);

        $this->assertSame(['Rahmat, ALI VALIYEV! Endi kechikadigan yoki kelmaydigan bo‘lsangiz, shu yerdan xabar bering.'], $this->replies());
        $this->assertSame(77, DavomatChat::query()->sole()->crm_user_id);
        Http::assertSent(fn (HttpRequest $request) => $request['reply_markup']['keyboard'] === [
            [['text' => 'Кечикаяпман (Жорий кун учун эрталабки соат 08-30гача киритиш)']],
            [['text' => 'Боролмайман (Эртанги кун учун жорий кундаги 00-00 гача белгилаш)']],
        ]);
    }

    public function test_a_late_reason_until_0830_is_for_today(): void
    {
        $this->linked();

        $this->botReceives(['text' => self::LATE]);
        Carbon::setTestNow('2026-11-12 08:30:40');
        $this->botReceives(['text' => 'Пробка на кольце']);

        $this->assertSame([
            'Kechikish sababini kiriting (bugun, 12.11.2026 uchun):',
            'Rahmat! 12.11.2026 uchun sababingiz qabul qilindi.',
        ], $this->replies());

        $reason = DavomatReason::query()->sole();
        $this->assertSame(77, $reason->crm_user_id);
        $this->assertSame('late', $reason->type);
        $this->assertSame('Пробка на кольце', $reason->reason);
        $this->assertSame('2026-11-12', $reason->date->toDateString());
    }

    public function test_the_old_keyboard_buttons_still_work(): void
    {
        $this->linked();
        Carbon::setTestNow('2026-11-12 08:00:00');

        $this->botReceives(['text' => 'Kechikasizmi? (00:00 dan 8:30 gacha ishlaydi)']);
        $this->botReceives(['text' => 'Kelmaysizmi?']);

        $this->assertSame([
            'Kechikish sababini kiriting (bugun, 12.11.2026 uchun):',
            'Sababini kiriting (13.11.2026 uchun sabab kirityapsiz):',
        ], $this->replies());
    }

    public function test_the_late_button_does_not_work_after_0830(): void
    {
        $this->linked();
        Carbon::setTestNow('2026-11-12 08:31:00');

        $this->botReceives(['text' => self::LATE]);
        $this->botReceives(['text' => 'Пробка']);

        $this->assertSame('⛔ Kechikish sababini faqat 00:00 dan 08:30 gacha yozish mumkin.', $this->replies()[0]);
        $this->assertSame(0, DavomatReason::query()->count());
    }

    public function test_a_late_reason_sent_after_0830_is_refused(): void
    {
        $this->linked();
        Carbon::setTestNow('2026-11-12 08:29:00');

        $this->botReceives(['text' => self::LATE]);
        Carbon::setTestNow('2026-11-12 08:31:00');
        $this->botReceives(['text' => 'Пробка']);

        $this->assertStringStartsWith('⛔ Vaqt o‘tdi', $this->replies()[1]);
        $this->assertSame(0, DavomatReason::query()->count());
    }

    public function test_a_wont_come_reason_is_for_tomorrow(): void
    {
        $this->linked();
        Carbon::setTestNow('2026-11-12 20:00:00');

        $this->botReceives(['text' => self::ABSENT]);
        Carbon::setTestNow('2026-11-12 23:59:30');
        $this->botReceives(['text' => 'Врач']);

        $this->assertSame([
            'Sababini kiriting (13.11.2026 uchun sabab kirityapsiz):',
            'Rahmat! 13.11.2026 uchun sababingiz qabul qilindi.',
        ], $this->replies());
        $this->assertSame('2026-11-13', DavomatReason::query()->sole()->date->toDateString());
    }

    public function test_a_wont_come_reason_sent_after_midnight_is_asked_again_for_the_next_day(): void
    {
        $this->linked();
        Carbon::setTestNow('2026-11-12 23:58:00');

        $this->botReceives(['text' => self::ABSENT]);
        Carbon::setTestNow('2026-11-13 00:01:00');
        $this->botReceives(['text' => 'Врач']);

        $this->assertSame(0, DavomatReason::query()->count());
        $this->assertSame("Sana o‘zgardi, sabab saqlanmadi.\nSababini kiriting (14.11.2026 uchun sabab kirityapsiz):", $this->replies()[1]);

        $this->botReceives(['text' => 'Врач']);

        $this->assertSame('2026-11-14', DavomatReason::query()->sole()->date->toDateString());
    }

    public function test_an_unknown_number_is_told_it_is_not_egs_group(): void
    {
        $this->botReceives(['contact' => ['phone_number' => '+998977777777', 'user_id' => 555]]);
        $this->botReceives(['text' => self::ABSENT]);

        $replies = $this->replies();
        $this->assertSame('Siz EGS Group xodimi emassiz.', $replies[0]);
        $this->assertStringContainsString('Raqamni yuborish', $replies[1]);
        $this->assertSame(0, DavomatReason::query()->count());
    }

    public function test_it_accepts_only_the_senders_own_contact(): void
    {
        $this->botReceives(['contact' => ['phone_number' => '998901234567', 'user_id' => 999]]);

        $this->assertStringContainsString('o‘zingizning raqamingizni', $this->replies()[0]);
        $this->assertNull(DavomatChat::query()->sole()->crm_user_id);
    }

    public function test_an_employee_removed_from_the_crm_list_can_no_longer_write(): void
    {
        $this->linked();

        $this->withToken(self::CRM_KEY)
            ->putJson('/api/davomat/employees', ['employees' => [['crm_user_id' => 88, 'name' => 'NEW ONE', 'phone' => '+998911112233']]])
            ->assertOk()
            ->assertJson(['count' => 1]);

        $this->botReceives(['text' => self::ABSENT]);

        $this->assertSame(['NEW ONE'], DavomatEmployee::query()->pluck('name')->all());
        $this->assertStringContainsString('Raqamni yuborish', $this->replies()[0]);
    }

    public function test_the_webhook_rejects_a_wrong_secret(): void
    {
        $this->postJson('/api/telegram/davomat-webhook', ['update_id' => 1], ['X-Telegram-Bot-Api-Secret-Token' => 'wrong'])
            ->assertForbidden();
    }

    public function test_the_crm_reads_reasons_after_the_last_one_it_has(): void
    {
        foreach (['first', 'second', 'third'] as $text) {
            DavomatReason::query()->create(['crm_user_id' => 77, 'telegram_id' => 555, 'type' => 'absent', 'reason' => $text, 'date' => '2026-11-13']);
        }

        $this->getJson('/api/davomat/reasons?after=1')->assertUnauthorized();

        $this->withToken(self::CRM_KEY)
            ->getJson('/api/davomat/reasons?after=1')
            ->assertOk()
            ->assertExactJson(['reasons' => [
                ['id' => 2, 'crm_user_id' => 77, 'type' => 'absent', 'reason' => 'second', 'date' => '2026-11-13'],
                ['id' => 3, 'crm_user_id' => 77, 'type' => 'absent', 'reason' => 'third', 'date' => '2026-11-13'],
            ]]);
    }
}
