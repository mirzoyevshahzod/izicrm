<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class DetectAbsentEmployees extends Command
{
    protected $signature = 'attendance:detect-absent
                        {--date= : Sinov uchun aniq sana (Y-m-d), berilmasa bugungi kun olinadi}';

    protected $description = 'Bugun umuman kirish qilmagan (kelmagan) faol xodimlarni aniqlaydi';

    protected string $apiUrl;

    public function __construct()
    {
        parent::__construct();
        $token = config('services.telegram.egs_attendance_bot_token');
        $this->apiUrl = "https://api.telegram.org/bot{$token}";
    }

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        Log::info('attendance:detect-absent ishga tushdi', ['date' => $date->toDateString()]);

        if ($date->dayOfWeek === Carbon::SUNDAY) {
            $this->info('Bugun yakshanba — tekshirilmaydi.');
            return self::SUCCESS;
        }

        // Bugun kirgan (Enter) barcha person_id'larni olamiz
        $presentPersonIds = DB::connection('hr_db')
            ->table('access_logs')
            ->whereDate('authentication_datetime', $date->toDateString())
            ->where('authentication_result', 'Success')
            ->where('in_out', 'Enter')
            ->pluck('person_id')
            ->unique()
            ->toArray();

        // Faol barcha xodimlar
        $activeEmployees = DB::table('attendance_employees')
            ->where('is_active', true)
            ->get();

        $count = 0;

        foreach ($activeEmployees as $employee) {

            if (in_array($employee->person_id, $presentPersonIds, true)) {
                // Kelgan — agar avval "kelmagan" deb yozilgan bo'lsa, tozalaymiz
                DB::table('attendance_absences')
                    ->where('person_id', $employee->person_id)
                    ->where('day', $date->day)
                    ->where('month', $date->month)
                    ->where('year', $date->year)
                    ->delete();

                continue;
            }

            $alreadyNotified = DB::table('attendance_absences')
                ->where('person_id', $employee->person_id)
                ->where('day', $date->day)
                ->where('month', $date->month)
                ->where('year', $date->year)
                ->exists();

            DB::table('attendance_absences')->updateOrInsert(
                [
                    'person_id' => $employee->person_id,
                    'day'       => $date->day,
                    'month'     => $date->month,
                    'year'      => $date->year,
                ],
                [
                    'chat_id'    => $employee->chat_id,
                    'fio'        => "{$employee->first_name} {$employee->last_name}",
                    'department' => $employee->department,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );

            if (!$alreadyNotified) {
                $this->notifyAbsence($employee);
            }

            $count++;
        }

        $this->info("{$count} ta kelmagan xodim aniqlandi.");

        return self::SUCCESS;
    }

    private function notifyAbsence(object $employee): void
    {
        $baseText = "⚫️ *Xodim kelmagan!*\n\n"
            ."👤 {$employee->first_name} {$employee->last_name}\n"
            ."🏢 Bo'lim: {$employee->department}\n"
            ."📅 Bugun face ID orqali kirish qayd etilmagan.";

        $bossIds = config('services.telegram.egs_boss_ids', []);

        foreach ($bossIds as $bossId) {
            $this->sendMessage((int) $bossId, $baseText);
        }

        $hrIds = config('services.telegram.egs_hr_ids', []);
        $hrText = $baseText . "\n🆔 Person ID: {$employee->person_id}";

        if (!$employee->chat_id) {
            $hrText .= "\n\n⚠️ Xodim botda ro'yxatdan o'tmagan.";
        }

        foreach ($hrIds as $hrId) {
            $this->sendMessage((int) $hrId, $hrText);
        }
    }

    private function sendMessage(int $chatId, string $text): void
    {
        Http::post($this->apiUrl . '/sendMessage', [
            'chat_id'    => $chatId,
            'text'       => $text,
            'parse_mode' => 'Markdown',
        ]);
    }
}
