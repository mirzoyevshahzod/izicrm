<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramChannel extends Model
{
    protected $table = 'telegram_channels';

    protected $fillable = [
        'title',
        'username',
        'description',
        'is_active',
    ];
}
