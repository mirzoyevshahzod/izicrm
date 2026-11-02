<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

/**
 * Panel tili (uz / ru) — panel_lang cookie dan. Faqat panel route'lariga qo'llanadi,
 * botlarning o'z til sozlamalariga tegmaydi.
 */
class SetPanelLocale
{
    public const LOCALES = ['uz', 'ru'];

    public function handle(Request $request, Closure $next)
    {
        $lang = $request->cookie('panel_lang');
        App::setLocale(in_array($lang, self::LOCALES, true) ? $lang : 'uz');

        return $next($request);
    }
}
