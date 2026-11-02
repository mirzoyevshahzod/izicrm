<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HhToken extends Model
{
    protected $table = 'hh_tokens';

    protected $fillable = [
        'access_token',
        'refresh_token',
        'expires_at',
        'employer_id',
        'employer_name',
        'manager_id',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
    ];

    protected $casts = [
        'access_token' => 'encrypted',
        'refresh_token' => 'encrypted',
        'expires_at' => 'datetime',
    ];
}
