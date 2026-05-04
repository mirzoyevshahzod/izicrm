<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserState extends Model
{
    protected $table = 'user_states';

    protected $fillable = [
        'debt_id',
        'chat_id',
        'period',
        'status'
    ];
}
