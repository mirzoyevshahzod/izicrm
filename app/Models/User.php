<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\IdentifyBot\AdminNotification;
use App\Models\IdentifyBot\Document;
use App\Models\IdentifyBot\Message;
use App\Models\IdentifyBot\Payment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'password',
        'telegram_id',
        'username',
        'first_name',
        'last_name',
        'state',
        'last_activity_at',
        'new_id',
        'documents_completed',
        'variables',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    protected $casts = [
        'variables' => 'array',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }
    public function documents()
    {
        return $this->hasMany(Document::class);
    }
    public function queues()
    {
        return $this->hasMany(IdentifyBot\Queue::class);
    }
   public function payments()
{
    // Vaqtinchalik, null qaytaradi va with() ishlaganda xato bermaydi
    return $this->hasMany(\App\Models\IdentifyBot\Payment::class)->whereRaw('0 = 1');
}
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }
    public function notifications()
    {
        return $this->hasMany(AdminNotification::class, 'user_id');
    }
    public function unreadNotifications()
{
    return $this->hasMany(AdminNotification::class, 'user_id')
                ->where('is_read', false);
}
}
