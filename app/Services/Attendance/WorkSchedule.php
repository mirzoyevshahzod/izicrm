<?php

namespace App\Services\Attendance;

use Carbon\Carbon;

/**
 * Ish jadvali: Du–Ju 09:00–18:00, Shanba 10:00–16:00, Yakshanba — dam olish.
 * (attendance:sync va attendance:detect-early-leave ham shu vaqtlardan foydalanadi)
 */
class WorkSchedule
{
    /** Shu daqiqagacha kech qolganlardan sabab (tushuntirish xati) so'ralmaydi — faqat ogohlantiriladi */
    public const LATE_GRACE_MINUTES = 2;

    public static function requiresLateReason(int $lateMinutes): bool
    {
        return $lateMinutes > self::LATE_GRACE_MINUTES;
    }

    public static function isWorkday(Carbon $date): bool
    {
        return $date->dayOfWeek !== Carbon::SUNDAY;
    }

    public static function start(Carbon $date): Carbon
    {
        return $date->copy()->setTime($date->dayOfWeek === Carbon::SATURDAY ? 10 : 9, 0, 0);
    }

    public static function end(Carbon $date): Carbon
    {
        return $date->copy()->setTime($date->dayOfWeek === Carbon::SATURDAY ? 16 : 18, 0, 0);
    }
}
