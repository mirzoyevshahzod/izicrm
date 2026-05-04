<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Debt extends Model
{
    protected $table = 'debts';

    protected $fillable = [
        'company_name',
        'employee_name',
        'total_amount',
        'chat_id',
        'day_0_7',
        'day_8_15',
        'day_16_30',
        'day_31_60',
        'day_61_90',
        'day_90_plus',
        'uploded_by'
    ];
}
