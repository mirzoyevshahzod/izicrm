<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceAbsence extends Model
{
    protected $table = 'attendance_absences';

    protected $fillable = [
        'person_id',
        'chat_id',
        'fio',
        'department',
        'day',
        'month',
        'year',
    ];
}
