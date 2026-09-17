<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QueryMessage extends Model
{
    protected $table = 'query_messages';

    protected $fillable = [
        'query_id',
        'group_id',
        'message_id',
    ];

    public function group()
    {
        return $this->belongsTo(Group::class);
    }
}
