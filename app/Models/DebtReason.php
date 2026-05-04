<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebtReason extends Model
{
    protected $table = 'debt_reasons';

    protected $fillable = [
        'debt_id',
        'type',
        'message_text',
        'file_path'
    ];
}
