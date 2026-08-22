<?php
// app/Console/Commands/ProcessFaceAttendance.php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\TelegramDavomatController;
use Carbon\Carbon;

class ProcessFaceAttendance extends Command
{
    protected $signature = 'attendance:process-face';
    protected $description = 'Face ID bazasidan yangi kirish yozuvlarini olib, kech qolganlarga xabar yuboradi';

    public function handle(TelegramDavomatController $telegram): int
    {
        $state = DB::table('face_sync_state')->first();
        $lastId = $state->last_face_log_id ?? 0;

        $rows = DB::connection('hr_db')
            ->table('access_logs')
            ->where('id', '>', $lastId)
            ->where('authentication_result', 'Success')
            ->where('in_out', 'Enter')
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            return self::SUCCESS;
        }

        $maxId = $lastId;

        foreach ($rows as $row) {
            $maxId = max($maxId, $row->id);

            try {
                $this->processRow($row, $telegram);
            } catch (\Throwable $e) {
                Log::error('Face attendance row error', [
                    'row_id' => $row->id,
                    'error'  => $e->getMessage(),
                ]);
            }
        }

        DB::table('face_sync_state')->update([
            'last_face_log_id' => $maxId,
            'updated_at' => now(),
        ]);

        return self::SUCCESS;
    }

    private function processRow(object $row, TelegramDavomatController $telegram): void
    {
        $arrival = Carbon::parse($row->authentication_datetime);
        $today   = $arrival->toDateString();

        $alreadyMarked = DB::table('daily_face_marks')
            ->where('person_id', $row->person_id)
            ->where('mark_date', $today)
            ->exists();

        if ($alreadyMarked) {
            return;
        }

        DB::table('daily_face_marks')->insert([
            'person_id'   => $row->person_id,
            'mark_date'   => $today,
            'face_log_id' => $row->id,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $employee = DB::table('employes')->where('person_id', $row->person_id)->first();

        if (!$employee) {
            $telegram->notifyHrUnknownEmployee($row->person_name, $row->person_id, $row->department);
            return;
        }

        $dayOfWeek = $arrival->dayOfWeek;
        if ($dayOfWeek === Carbon::SUNDAY) {
            return;
        }

        $startHour = $dayOfWeek === Carbon::SATURDAY ? 10 : 9;
        $workStart = $arrival->copy()->setTime($startHour, 0, 0);

        $lateMinutes = $workStart->diffInMinutes($arrival, false);

        if ($lateMinutes > 0) {
            $telegram->handleFaceLateEvent($employee, $row, $arrival, $lateMinutes);
        } else {
            $telegram->handleFaceOnTimeEvent($employee, $row, $arrival);
        }
    }
}
