<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * HH bot xabarlarini oladigan chat: HR ning shaxsiy chati (panel foydalanuvchisiga bog'langan) yoki guruh
 */
class HhBotChat extends Model
{
    protected $table = 'hh_bot_chats';

    protected $fillable = [
        'chat_id',
        'type',
        'title',
        'username',
        'user_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPrivate(): bool
    {
        return $this->type === 'private';
    }
}
