<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCrsToken extends Middleware
{
    public $except = [
        'api/telegram/webhook',
        'telegram/webhook',
    ];
}
