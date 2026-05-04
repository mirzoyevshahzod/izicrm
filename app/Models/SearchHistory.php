<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchHistory extends Model
{
    protected $fillable = [
        'searcher_chat_id',
        'target_contact_id',
        'query'
    ];
}
