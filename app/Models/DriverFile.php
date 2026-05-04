<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Driver;

class DriverFile extends Model
{
    protected $fillable = ['driver_id', 'file_path'];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
