<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriverStep extends Model
{
    protected $table = 'driver_steps';

    protected $fillable = [
        'chat_id',
        'step',

    ];
}
