<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDebtQueue extends Model
{
    protected $table = 'user_debt_queues';

    protected $fillable = [
        'chat_id',
        'debt_ids',
        'current_index'
    ];
}
