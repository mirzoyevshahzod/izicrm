<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    protected $table = 'feedbacks';

    protected $fillable = [
        'bot_slug',
        'full_name',
        'message',
        'type',
        'is_active',
    ];
}
