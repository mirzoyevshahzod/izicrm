<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $table = 'drivers';

    protected $fillable = [
        'chat_id',
        'phone',
        'cmr_file',
        'destination_country',
        'destination_region',
    ];

    public function files()
    {
    return $this->hasMany(DriverFile::class);
    }

}
