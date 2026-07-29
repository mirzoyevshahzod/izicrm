<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
    protected $table = 'subscriptions';
    protected $fillable = ['telegram_id', 'name', 'phone', 'status', 'tariff', 'check_file_id', 'check_file'];

    public function files()
    {
        return $this->hasMany(File::class);
    }
}
