<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TelegramState extends Model
{
    //
    protected $fillable = [
        'chat_id',
        'state',
    ];
}
