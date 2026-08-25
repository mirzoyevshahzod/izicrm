<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceEarlyLeave extends Model
{
    protected $table = 'attendance_early_leaves';

    protected $fillable = [
        'person_id',
        'chat_id',
        'fio',
        'department',
        'door_name',
        'device_name',
        'last_exit_time',
        'expected_end_time',
        'early_minutes',
        'day',
        'month',
        'year'
    ];
}
