<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaterialRequest extends Model
{
    protected $table = 'material_requests';

    protected $fillable = [
        'chat_id',
        'last_name',
        'frist_name',
        'telegram_username',
        'company',
        'request_text',
        'step', 
        'status',
        'approved_by_name',
        'requested_at',
    ];
}
