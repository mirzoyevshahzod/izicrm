<?php

/*
|--------------------------------------------------------------------------
| Panel bo'limlari ruxsatlari (spatie/laravel-permission)
|--------------------------------------------------------------------------
| Kalit — permission nomi, qiymat — foydalanuvchilar sahifasidagi yozuv.
| `admin` roli hammasini ko'radi (AppServiceProvider dagi Gate::before).
| Yangi bo'lim qo'shsangiz shu yerga yozing va migratsiyada yarating.
*/

return [

    // Panel manzili (bot xabarlaridagi havolalar uchun). izicrm.uz hozir Kerio orqali ochilmaydi.
    'url' => env('PANEL_URL', 'https://webhook.izicrm.uz'),

    'permissions' => [
        'attendance.view' => 'Davomat',
        'debts.view' => 'Qarzdorlik',
        'employees.manage' => 'Xodimlar (Face ID)',
        'users.manage' => 'Foydalanuvchilar',
    ],

];
