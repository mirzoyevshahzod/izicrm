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

    'permissions' => [
        'attendance.view' => 'Davomat',
        'debts.view' => 'Qarzdorlik',
        'users.manage' => 'Foydalanuvchilar',
    ],

];
