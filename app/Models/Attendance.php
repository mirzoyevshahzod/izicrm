<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    protected $table = 'attendances';
    
    protected $fillable = [
        'chat_id',
        'fio',
        'company',
        'day',
        'month',
        'year',
        'reason',
        'late_minutes',
    ];
}
