<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;



Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/login', [HomeController::class, 'login'])->name('login');
Route::get('/register', [HomeController::class, 'register'])->name('register');

Route::get('/drivers', [HomeController::class, 'drivers'])->name('drivers');

Route::get('/operation-list', [HomeController::class, 'operation']);
Route::get('/admin-profile', [HomeController::class, 'profile']);
Route::get('/add-user', [HomeController::class, 'addUser']);
Route::get('/update-user/{id}', [HomeController::class, 'updateUser']);

Route::get('/attendance-login', [HomeController::class, 'attendanceLogin'])->name('attendanceLogin');
Route::get('/attendance-dashboard', [HomeController::class, 'attendanceDashboard'])->name('attendance');

Route::get('/debt-login', [HomeController::class, 'debtLogin'])->name('debtLogin');
Route::get('/debt-dashboard', [HomeController::class, 'debtDashboard'])->name('debtDashboard');


Route::get('/debt-test', [HomeController::class, 'debtTest'])->name('debtTest');

