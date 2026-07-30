<?php

use App\Http\Controllers\Api\External\QueryController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\DebtViewController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramContactBotController;
use App\Http\Controllers\DriversController;
use App\Http\Controllers\SubscriberController;
use App\Http\Controllers\TelegramDavomatController;
use App\Http\Controllers\TelegramEmployeeController;
use App\Http\Controllers\MaterialRequestController;
use App\Http\Controllers\RequestBotContoller;
use App\Http\Controllers\TariffTelegramController;
use App\Http\Controllers\TestController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/telegram/webhook', [TelegramContactBotController::class, 'webhook']);
Route::post('/telegram-webhook', [SubscriberController::class, 'webhook']);
Route::post('/telegram/davomat-webhook', [TelegramDavomatController::class, 'webhook']);
Route::post('/telegram/debt-webhook', [DebtController::class, 'webhook']);
Route::post('/telegram/contact-webhook', [TelegramEmployeeController::class, 'webhook']);
Route::post('/telegram/incotruck-request-webhook', [RequestBotContoller::class, 'webhook']);
Route::post('/telegram/material-request-webhook', [MaterialRequestController::class, 'webhook']);
Route::post('/incotruck-request-send', [RequestBotContoller::class, 'send']);
Route::post('/kgs-request-send', [RequestBotContoller::class, 'KGSsend']);
Route::post('/tariff-webhook', [TariffTelegramController::class, 'webhook']);

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/attendanceLogin', [AttendanceController::class, 'attendanceLogin']);
Route::post('/debtLogin', [DebtViewController::class, 'debtLogin']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/all-drivers', [DriversController::class, 'index']);
    Route::get('/all-operator', [DriversController::class, 'getOperationUsers']);
    Route::delete('/delete-user/{id}', [DriversController::class, 'deleteOperator']);
    Route::get('/profile', [DriversController::class, 'profile']);
    Route::get('/operator/{id}', [DriversController::class, 'getOperatorById']);
    Route::put('/operator/{id}', [DriversController::class, 'updateOperator']);
    Route::get('/drivers/search', [DriversController::class, 'searchByPhone']);
    Route::get('/attendance', [AttendanceController::class, 'attendance']);
    Route::get('/attendances/search', [AttendanceController::class, 'searchAttendance']);
    Route::get('/attendances/export', [AttendanceController::class, 'exportAttendance']);

});

    Route::get('/admin/debts', [DebtViewController::class, 'index']);
    Route::get('/debts/search', [DebtViewController::class, 'search']);



Route::get('/test', [TestController::class, 'index']);

////
Route::get('/queries', [QueryController::class, 'index']);
Route::post('/queries', [QueryController::class, 'insert']);
