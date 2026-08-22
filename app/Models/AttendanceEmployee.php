<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceEmployee extends Model
{
    protected $table = 'attendance_employees';

    protected $fillable = [
        'person_id',
        'first_name',
        'last_name',
        'department',
        'chat_id',
        'phone',
        'is_active',
    ];

    public function fullName(): string
    {
        return "{$this->last_name} {$this->first_name}";
    }
}
