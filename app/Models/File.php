<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class File extends Model
{

    protected $table = 'files';
    protected $fillable = ['file_path', 'file_name', 'status', 'subscription_id'];

    public function subscription()
    {
        return $this->belongsTo(Subscription::class);
    }
}
