<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';

    protected $fillable = [
        'standard_price',
        'premium_price',
        'card_number',
        'card_holder',
        'link',
    ];
}
