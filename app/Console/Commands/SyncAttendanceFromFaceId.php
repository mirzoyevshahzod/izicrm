<?php
// app/Console/Commands/SyncAttendanceFromFaceId.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class SyncAttendanceFromFaceId extends Command
{
    protected $signature = 'attendance:sync
                        {--dry-run : Faqat ko\'rsatadi, xabar yubormaydi va bazaga yozmaydi}
                        {--test= : Faqat shu log id ni sinov uchun qayta ishlaydi (cursor o\'zgarmaydi)}';

    protected $description = 'HikCentral (face_id) bazasidan yangi kirish yozuvlarini o\'qib, kech qolganlarga xabar yuboradi';

    protected string $apiUrl;

    public function __construct()
    {
        parent::__construct();
        $token = config('services.telegram.egs_attendance_bot_token');
        $this->apiUrl = "https://api.telegram.org/bot{$token}";
    }

    public function handle(): int
    {
        Log::info('attendance:sync ishga tushdi', ['time' => now()->toDateTimeString()]);
        // 🧪 TEST REJIMI — cursor bilan ishlamaydi, faqat bitta yozuvni sinaydi
        if ($testId = $this->option('test')) {
            $row = DB::connection('hr_db')
                ->table('access_logs')
                ->where('id', $testId)
                ->first();

            if (!$row) {
                $this->error("id={$testId} bo'lgan yozuv topilmadi.");
                return self::FAILURE;
            }

            $this->info("TEST: {$row->person_name} ({$row->person_id}) - {$row->authentication_datetime}");

            // Test paytida "kunning birinchi kirishi" tekshiruvini chetlab o'tamiz
            DB::table('attendance_daily_marks')
                ->where('person_id', $row->person_id)
                ->where('mark_date', \Carbon\Carbon::parse($row->authentication_datetime)->toDateString())
                ->delete();

            $this->processRow($row, false);

            $this->info('Test tugadi.');
            return self::SUCCESS;
        }

        $isDryRun = $this->option('dry-run');

        // 🔒 Cursor'ni xavfsiz olamiz/yaratamiz
        $state = DB::table('attendance_sync_state')->first();

        if (!$state) {
            // Birinchi marta ishga tushmoqda — tarixni qayta ishlamaslik uchun
            // hozirgi eng katta id'ni boshlang'ich nuqta qilib olamiz
            $maxId = DB::connection('hr_db')->table('access_logs')->max('id') ?? 0;

            DB::table('attendance_sync_state')->insert([
                'last_log_id' => $maxId,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            $this->info("Birinchi ishga tushish. Cursor {$maxId} ga o'rnatildi. Tarix qayta ishlanmaydi.");
            return self::SUCCESS;
        }

        $lastId = $state->last_log_id;

        $rows = DB::connection('hr_db')
            ->table('access_logs')
            ->where('id', '>', $lastId)
            ->where('authentication_result', 'Success')
            ->where('in_out', 'Enter')
            ->orderBy('id')
            ->limit(500) // xavfsizlik uchun — bir martada juda ko'p yozuv qayta ishlanmasin
            ->get();

        if ($rows->isEmpty()) {
            return self::SUCCESS;
        }

        $this->info("{$rows->count()} ta yangi yozuv topildi.");

        $maxId = $lastId;

        foreach ($rows as $row) {
            $maxId = max($maxId, $row->id);

            try {
                $this->processRow($row, $isDryRun);
            } catch (\Throwable $e) {
                Log::error('Attendance sync row error', [
                    'log_id' => $row->id,
                    'error'  => $e->getMessage(),
                ]);
            }
        }

        if (!$isDryRun) {
            DB::table('attendance_sync_state')->update([
                'last_log_id' => $maxId,
                'updated_at'  => now(),
            ]);
        }

        return self::SUCCESS;
    }

    private function processRow(object $row, bool $isDryRun): void
    {
        $arrival = Carbon::parse($row->authentication_datetime);
        $today   = $arrival->toDateString();

        // Kunning birinchi kirishimi?
        $alreadyMarked = DB::table('attendance_daily_marks')
            ->where('person_id', $row->person_id)
            ->where('mark_date', $today)
            ->exists();

        if ($alreadyMarked) {
            return;
        }

        if (!$isDryRun) {
            DB::table('attendance_daily_marks')->insert([
                'person_id'  => $row->person_id,
                'mark_date'  => $today,
                'log_id'     => $row->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $employee = DB::table('attendance_employees')
            ->where('person_id', $row->person_id)
            ->first();

        if (!$employee) {
            $this->warn("Noma'lum person_id: {$row->person_id} ({$row->person_name})");
            return;
        }

        // Yakshanba — dam olish kuni
        if ($arrival->dayOfWeek === Carbon::SUNDAY) {
            return;
        }

        $startHour = $arrival->dayOfWeek === Carbon::SATURDAY ? 10 : 9;
        $workStart = $arrival->copy()->setTime($startHour, 0, 0);
        $lateMinutes = $workStart->diffInMinutes($arrival, false);

        $this->line("{$employee->first_name} {$employee->last_name} - {$arrival->format('H:i:s')} - kech: {$lateMinutes} daqiqa");

        if ($isDryRun) {
            return;
        }

        if ($lateMinutes > 0) {
            $this->handleLate($employee, $row, $arrival, $lateMinutes);
        } else {
            if ($employee->chat_id) {
                $this->sendMessage($employee->chat_id, "✅ {$employee->first_name}, siz vaqtida keldingiz. Kunni yaxshi o'tkazing!");
            }
        }
    }

    private function handleLate(object $employee, object $row, Carbon $arrival, int $lateMinutes): void
    {
        DB::table('attendance_late_events')->insert([
            'log_id'       => $row->id,
            'chat_id'      => $employee->chat_id,
            'person_id'    => $row->person_id,
            'fio'          => "{$employee->first_name} {$employee->last_name}",
            'department'   => $row->department,
            'door_name'    => $row->door_name,
            'device_name'  => $row->device_name,
            'day'          => $arrival->day,
            'month'        => $arrival->month,
            'year'         => $arrival->year,
            'late_minutes' => $lateMinutes,
            'status'       => 'waiting_company',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        $lateDuration = $this->formatLateDuration($lateMinutes);

        // 🚨 Boshliqlarga DARHOL xabar — sababni kutmasdan
        $bossIds = config('services.telegram.egs_boss_ids', []);
        $hrIds   = config('services.telegram.egs_hr_ids', []);
        $notifyIds = array_unique((array)array_merge($bossIds, $hrIds));

        $baseText = "🔴 *Kech qolish!*\n\n"
            ."👤 {$employee->first_name} {$employee->last_name}\n"
            ."🏢 Bo'lim: {$row->department}\n"
            ."🚪 Eshik: {$row->door_name}\n"
            ."⏰ Vaqt: {$arrival->format('H:i:s')}\n"
            ."⏱ Kech qoldi: {$lateDuration}";

        $bossText = $baseText . "\n\n_Sabab hali yozilmagan — xodim javob yozganda tushuntirish xati generatsiya qilinadi._";

        foreach ($bossIds as $bossId) {
            $this->sendMessage((int) $bossId, $bossText);
        }

        $hrText = $baseText;

        if (!$employee->chat_id) {
            $hrText .= "\n\n⚠️ Xodim botda ro'yxatdan o'tmagan, shuning uchun unga xabar yuborib bo'lmadi.";
        } else {
            $hrText .= "\n\n_Sabab hali yozilmagan — xodim javob yozganda tushuntirish xati generatsiya qilinadi._";
        }

        foreach ($hrIds as $hrId) {
            $this->sendMessage((int) $hrId, $hrText);
        }

        if ($employee->chat_id) {
            $this->sendMessageWithButton(
                $employee->chat_id,
                "⏰ Assalomu alaykum, {$employee->first_name}.\n\n"
                ."Bugun ishga *{$lateMinutes} daqiqa* kech keldingiz ({$row->door_name}).\n"
                ."Iltimos, sababini yozing.",
                'write_late_reason'
            );
        }
    }

    private function formatLateDuration(int $totalMinutes): string
    {
        $hours   = intdiv($totalMinutes, 60);
        $minutes = $totalMinutes % 60;

        if ($hours > 0) {
            return $minutes > 0
                ? "{$hours} soat {$minutes} daqiqa"
                : "{$hours} soat";
        }

        return "{$minutes} daqiqa";
    }

    private function sendMessage(int $chatId, string $text): void
    {
        Http::post($this->apiUrl . '/sendMessage', [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'Markdown',
        ]);
    }

    private function sendMessageWithButton(int $chatId, string $text, string $callbackData): void
    {
        Http::post($this->apiUrl . '/sendMessage', [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'Markdown',
            'reply_markup' => [
                'inline_keyboard' => [[
                    ['text' => 'Sababini yozish', 'callback_data' => $callbackData],
                ]],
            ],
        ]);
    }
}
