<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DetectEarlyLeavers extends Command
{
    protected $signature = 'attendance:detect-early-leave
                        {--date= : Sinov uchun aniq sana (Y-m-d), berilmasa bugungi kun olinadi}';

    protected $description = 'Kunning oxirgi chiqishini tekshirib, ish vaqtidan oldin ketganlarni qayd etadi';

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        Log::info('attendance:detect-early-leave ishga tushdi', ['date' => $date->toDateString()]);

        if ($date->dayOfWeek === Carbon::SUNDAY) {
            $this->info('Bugun yakshanba — tekshirilmaydi.');
            return self::SUCCESS;
        }

        $expectedEnd = $date->dayOfWeek === Carbon::SATURDAY
            ? $date->copy()->setTime(16, 0, 0)
            : $date->copy()->setTime(18, 0, 0);

        // ⬇️ Endi Enter va Exit ikkalasini ham olamiz — kunning ENG SO'NGGI hodisasi kerak
        $allRows = DB::connection('hr_db')
            ->table('access_logs')
            ->whereDate('authentication_datetime', $date->toDateString())
            ->where('authentication_result', 'Success')
            ->whereIn('in_out', ['Enter', 'Exit'])
            ->orderBy('authentication_datetime')
            ->get();

        if ($allRows->isEmpty()) {
            $this->info('Bugun uchun yozuvlar topilmadi.');
            return self::SUCCESS;
        }

        // Har bir person_id uchun ENG SO'NGGI hodisani olamiz (turi Enter yoki Exit — farqi yo'q)
        $lastEventByPerson = [];

        foreach ($allRows as $row) {
            $lastEventByPerson[$row->person_id] = $row;
        }

        $count = 0;

        foreach ($lastEventByPerson as $personId => $row) {
            $lastEvent = Carbon::parse($row->authentication_datetime);

            $employee = DB::table('attendance_employees')
                ->where('person_id', $personId)
                ->first();

            if (!$employee) {
                $this->warn("Noma'lum person_id: {$personId} ({$row->person_name})");
                continue;
            }

            // Agar kunning oxirgi hodisasi ish tugash vaqtidan keyin yoki teng bo'lsa —
            // xodim o'sha vaqtda hali binoda bo'lgan, "erta ketgan" emas
            if ($lastEvent->gte($expectedEnd)) {
                DB::table('attendance_early_leaves')
                    ->where('person_id', $personId)
                    ->where('day', $date->day)
                    ->where('month', $date->month)
                    ->where('year', $date->year)
                    ->delete();

                continue;
            }

            $earlyMinutes = max(1, $expectedEnd->diffInMinutes($lastEvent));

            DB::table('attendance_early_leaves')->updateOrInsert(
                [
                    'person_id' => $personId,
                    'day'       => $date->day,
                    'month'     => $date->month,
                    'year'      => $date->year,
                ],
                [
                    'chat_id'           => $employee->chat_id,
                    'fio'               => "{$employee->first_name} {$employee->last_name}",
                    'department'        => $row->department,
                    'door_name'         => $row->door_name,
                    'device_name'       => $row->device_name,
                    'last_exit_time'    => $lastEvent->format('H:i:s'),
                    'expected_end_time' => $expectedEnd->format('H:i:s'),
                    'early_minutes'     => $earlyMinutes,
                    'updated_at'        => now(),
                    'created_at'        => now(),
                ]
            );

            $count++;
        }

        $this->info("{$count} ta erta ketish aniqlandi.");

        return self::SUCCESS;
    }
}
