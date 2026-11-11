<?php

/*
|--------------------------------------------------------------------------
| Komandalar monitoringi
|--------------------------------------------------------------------------
| `monitor:heartbeat` har bir komandaning oxirgi muvaffaqiyatli tugagan
| vaqtini tekshiradi. Ko'rsatilgan daqiqadan ko'p o'tib ketsa, IziCRM
| Monitor bot orqali xabar yuboradi. Komanda muvaffaqiyatli tugaganda
| Cache::forever('heartbeat:<komanda>', now()->toIso8601String()) yozishi kerak.
*/

return [

    'heartbeats' => [
        // Har soatda ishlaydi, ishlash vaqti bilan birga 2.5 soatgacha kutamiz
        'telegram:check-all' => 150,
    ],

];
