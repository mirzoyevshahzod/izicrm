<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mail extends Model
{
    protected $fillable = [
        'chat_id', 'lang', 'sender_name', 'sender_address', 'sender_phone',
        'recipient_name', 'recipient_address', 'recipient_country', 'recipient_postal_code',
        'tarif', 'delivery_time', 'delivery_price', 'currier_phone', 'images', 'step', 'status', 'oferta_checked'
    ];

    protected $casts = [
        'images' => 'array',
    ];
}
