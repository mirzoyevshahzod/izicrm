<?php

namespace App\Services\Attendance;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Bir kunlik davomat holati: kim vaqtida keldi, kim kech qoldi (qancha),
 * kim hali kelmagan / kelmagan, kim sabab yozgan, kim erta ketgan.
 *
 * Kirish-chiqish vaqtlari to'g'ridan-to'g'ri HikCentral (hr_db) dan olinadi.
 * hr_db ga ulanib bo'lmasa — lokal jadvallar (attendance_daily_marks,
 * attendance_late_events, attendance_early_leaves) bilan ishlaydi.
 */
class DailyAttendanceReport
{
    public const STATUSES = ['on_time', 'late', 'not_arrived', 'absent', 'excused', 'day_off'];

    public function build(Carbon $date): array
    {
        $date = $date->copy()->startOfDay();
        $now = now();

        $isWorkday = WorkSchedule::isWorkday($date);
        $start = WorkSchedule::start($date);
        $end = WorkSchedule::end($date);
        $isToday = $date->isSameDay($now);
        $dayOver = $date->lt($now->copy()->startOfDay()) || $now->gte($end);

        $employees = DB::table('attendance_employees')
            ->where('is_active', true)
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'person_id', 'first_name', 'last_name', 'department', 'chat_id']);

        $dayKey = ['day' => $date->day, 'month' => $date->month, 'year' => $date->year];

        $lateEvents = DB::table('attendance_late_events')->where($dayKey)
            ->orderBy('id')->get()->keyBy('person_id');

        $excuses = DB::table('attendance_absence_requests')
            ->whereDate('date', $date->toDateString())
            ->get()->keyBy('person_id');

        [$live, $logs] = $this->loadLogs($date);

        if (!$live) {
            $marked = DB::table('attendance_daily_marks')
                ->where('mark_date', $date->toDateString())
                ->pluck('person_id')->flip();
            $earlyRows = DB::table('attendance_early_leaves')->where($dayKey)->get()->keyBy('person_id');
        }

        $rows = [];

        foreach ($employees as $employee) {
            $pid = $employee->person_id;
            $log = $logs[$pid] ?? null;
            $late = $lateEvents[$pid] ?? null;
            $excuse = $excuses[$pid] ?? null;

            $arrivedAt = $log['first_enter'] ?? $log['first_event'] ?? null;
            $present = $arrivedAt !== null || $late !== null || (!$live && isset($marked[$pid]));

            $lateMinutes = 0;
            if ($present && $isWorkday) {
                if ($arrivedAt) {
                    $seconds = $start->diffInSeconds($arrivedAt, false);
                    $lateMinutes = $seconds > 0 ? max(1, intdiv((int) $seconds, 60)) : 0;
                } elseif ($late) {
                    $lateMinutes = (int) $late->late_minutes;
                }
            }

            // Erta ketish — faqat ish vaqti tugagandan keyin aniq bo'ladi
            $leftAt = null;
            $earlyMinutes = 0;
            if ($present && $isWorkday && $dayOver) {
                if ($live) {
                    $leftAt = $log['last_event'] ?? null;
                    if ($leftAt && $leftAt->lt($end)) {
                        $earlyMinutes = max(1, (int) $leftAt->diffInMinutes($end));
                    }
                } elseif (isset($earlyRows[$pid])) {
                    $leftAt = Carbon::parse($date->toDateString() . ' ' . $earlyRows[$pid]->last_exit_time);
                    $earlyMinutes = (int) $earlyRows[$pid]->early_minutes;
                }
            }

            if (!$isWorkday) {
                $status = $present ? 'on_time' : 'day_off';
            } elseif ($present) {
                $status = $lateMinutes > 0 ? 'late' : 'on_time';
            } elseif ($excuse) {
                $status = 'excused';
            } else {
                $status = $dayOver ? 'absent' : 'not_arrived';
            }

            $rows[] = [
                'id' => $employee->id,
                'person_id' => $pid,
                'name' => trim("{$employee->first_name} {$employee->last_name}"),
                'department' => $employee->department,
                'registered' => (bool) $employee->chat_id,
                'status' => $status,
                'arrived_at' => $arrivedAt?->format('H:i'),
                'late_minutes' => $lateMinutes,
                'reason_required' => WorkSchedule::requiresLateReason($lateMinutes),
                'late_reason' => $late?->reason,
                'left_at' => $leftAt?->format('H:i'),
                'early_minutes' => $earlyMinutes,
                'excuse_reason' => $excuse?->reason,
                'excuse_at' => $excuse ? Carbon::parse($excuse->updated_at)->format('H:i') : null,
            ];
        }

        $rows = $this->sort($rows);
        $count = fn (string $status) => count(array_filter($rows, fn ($r) => $r['status'] === $status));

        return [
            'date' => $date->toDateString(),
            'is_today' => $isToday,
            'is_workday' => $isWorkday,
            'day_over' => $dayOver,
            'work_start' => $start->format('H:i'),
            'work_end' => $end->format('H:i'),
            'live' => $live,
            'late_grace_minutes' => WorkSchedule::LATE_GRACE_MINUTES,
            'generated_at' => $now->format('H:i'),
            'summary' => [
                'total' => count($rows),
                'present' => $count('on_time') + $count('late'),
                'on_time' => $count('on_time'),
                'late' => $count('late'),
                'late_minutes' => array_sum(array_column($rows, 'late_minutes')),
                'not_arrived' => $count('not_arrived'),
                'absent' => $count('absent'),
                'excused' => $count('excused'),
                'early_left' => count(array_filter($rows, fn ($r) => $r['early_minutes'] > 0)),
            ],
            'employees' => $rows,
        ];
    }

    /**
     * Paneldagi filtrlar: kartochka (filter), bo'lim va qidiruv (ism yoki person_id)
     */
    public static function filter(array $rows, ?string $filter, ?string $department, ?string $search): array
    {
        $search = mb_strtolower(trim((string) $search));

        return array_values(array_filter($rows, function ($r) use ($filter, $department, $search) {
            $byStatus = match ($filter) {
                'present' => in_array($r['status'], ['on_time', 'late'], true),
                'late' => $r['status'] === 'late',
                'missing' => in_array($r['status'], ['absent', 'not_arrived'], true),
                'excused' => $r['status'] === 'excused',
                'early' => $r['early_minutes'] > 0,
                default => true,
            };

            return $byStatus
                && (!$department || $r['department'] === $department)
                && ($search === '' || str_contains(mb_strtolower($r['name']), $search) || str_contains((string) $r['person_id'], $search));
        }));
    }

    /**
     * HikCentral'dan kunlik yozuvlar: har bir person_id uchun birinchi kirish,
     * birinchi va oxirgi hodisa (Enter yoki Exit).
     *
     * @return array{0: bool, 1: array<string, array<string, Carbon|null>>}
     */
    private function loadLogs(Carbon $date): array
    {
        try {
            $rows = DB::connection('hr_db')
                ->table('access_logs')
                ->whereDate('authentication_datetime', $date->toDateString())
                ->where('authentication_result', 'Success')
                ->whereIn('in_out', ['Enter', 'Exit'])
                ->get(['person_id', 'in_out', 'authentication_datetime']);
        } catch (\Throwable $e) {
            Log::warning('Davomat hisoboti: hr_db ga ulanib bo\'lmadi', ['error' => $e->getMessage()]);
            return [false, []];
        }

        $byPerson = [];

        foreach ($rows as $row) {
            $at = Carbon::parse($row->authentication_datetime);
            $p = &$byPerson[$row->person_id];
            $p ??= ['first_enter' => null, 'first_event' => null, 'last_event' => null];

            if ($row->in_out === 'Enter' && (!$p['first_enter'] || $at->lt($p['first_enter']))) {
                $p['first_enter'] = $at;
            }
            if (!$p['first_event'] || $at->lt($p['first_event'])) {
                $p['first_event'] = $at;
            }
            if (!$p['last_event'] || $at->gt($p['last_event'])) {
                $p['last_event'] = $at;
            }
            unset($p);
        }

        return [true, $byPerson];
    }

    /**
     * Avval muammoli holatlar: kelmagan → hali kelmagan → sababli → kech qolgan (ko'pidan) → vaqtida
     */
    private function sort(array $rows): array
    {
        $order = array_flip(['absent', 'not_arrived', 'excused', 'late', 'on_time', 'day_off']);

        usort($rows, function ($a, $b) use ($order) {
            return [$order[$a['status']], -$a['late_minutes'], -$a['early_minutes'], $a['arrived_at'] ?? '99', $a['name']]
                <=> [$order[$b['status']], -$b['late_minutes'], -$b['early_minutes'], $b['arrived_at'] ?? '99', $b['name']];
        });

        return $rows;
    }
}
