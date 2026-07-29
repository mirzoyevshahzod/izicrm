<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    protected $table = 'employees';

    protected $fillable = [
        'full_name',
        'work_phone',
        'personal_phone',
        'company',
    ];
}
