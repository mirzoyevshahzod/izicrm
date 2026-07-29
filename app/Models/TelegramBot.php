<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramBot extends Model
{
    protected $table = 'telegram_bots';

    protected $fillable = [
        'title',
        'username',
        'description',
        'is_active',
    ];
}
