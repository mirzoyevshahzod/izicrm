<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/oferta', function () {
    return view('oferta');
});

// Panel — sahifalar ochiq, ma'lumot /api orqali token va ruxsat bilan keladi (public/assets/panel/panel.js)
Route::view('/login', 'panel.login')->middleware('panel.locale')->name('login');
Route::redirect('/', '/panel');

Route::prefix('panel')->middleware('panel.locale')->group(function () {
    Route::view('/', 'panel.dashboard')->name('panel');
    Route::view('/attendance', 'panel.attendance')->name('panel.attendance');
    Route::view('/debts', 'panel.debts')->name('panel.debts');
    Route::view('/hh', 'panel.hh.index')->name('panel.hh');
    Route::view('/hh/searches/create', 'panel.hh.form')->name('panel.hh.create');
    Route::view('/hh/searches/{search}/edit', 'panel.hh.form')->whereNumber('search')->name('panel.hh.edit');
    Route::view('/hh/searches/{search}', 'panel.hh.candidates')->whereNumber('search')->name('panel.hh.candidates');
    Route::view('/users', 'panel.users')->name('panel.users');
    Route::view('/profile', 'panel.profile')->name('panel.profile');
});

// Eski manzillar — saqlab qo'yilgan havolalar ishlashi uchun
Route::permanentRedirect('/attendance-login', '/login');
Route::permanentRedirect('/debt-login', '/login');
Route::permanentRedirect('/attendance-dashboard', '/panel/attendance');
Route::permanentRedirect('/debt-dashboard', '/panel/debts');
Route::permanentRedirect('/operation-list', '/panel/users');
Route::permanentRedirect('/add-user', '/panel/users');
Route::permanentRedirect('/update-user/{id}', '/panel/users');
Route::permanentRedirect('/admin-profile', '/panel/profile');
Route::permanentRedirect('/drivers', '/panel');

// hh.uz OAuth callback: HH bo'limi candidate.websol.uz ga ko'chdi.
// dev.hh.uz dagi Redirect URI shu manzilda qolgan — code va state ni o'zgarishsiz o'sha yerga uzatamiz.
Route::get('/hh/callback', fn (Request $request) => redirect()->away(
    'https://candidate.websol.uz/hh/callback' . ($request->getQueryString() ? '?' . $request->getQueryString() : '')
));
