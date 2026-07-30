<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactBotUser extends Model
{

    protected $fillable = [
        'telegram_id',
        'company_slug',
        'type',
        'name',
        'last_used_at',
        'state',
    ];
}
