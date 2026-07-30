<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Appeal extends Model
{
    protected $table = 'appeals';

    protected $fillable = [
        'user_id',
        'company_name',
        'Phone',
        'Message',
        'type',
    ];
}
