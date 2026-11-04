<?php

use App\Http\Controllers\Api\External\QueryController;
use App\Http\Controllers\Attendance\EGSAttendanceBotController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HhBotController;
use App\Http\Controllers\HhController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DebtController;
use App\Http\Controllers\DebtViewController;
use App\Http\Controllers\TelegramCandidateBotController;
use App\Http\Controllers\TelegramExpressBotController;
use App\Http\Controllers\TelegramPostixBotController;
use App\Http\Controllers\TelegramMyfinEgsBotController;
use App\Http\Controllers\TelegramMyfinExpressBotController;
use App\Http\Controllers\TelegramTrancekaBotController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TelegramContactBotController;
use App\Http\Controllers\SubscriberController;
use App\Http\Controllers\TelegramDavomatController;
use App\Http\Controllers\TelegramEmployeeController;
use App\Http\Controllers\MaterialRequestController;
use App\Http\Controllers\RequestBotContoller;
use App\Http\Controllers\TariffTelegramController;
use App\Http\Controllers\TelegramMobileAppBotController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/telegram/tranceka-webhook', [TelegramTrancekaBotController::class, 'webhook']);
Route::post('/telegram/express-webhook', [TelegramExpressBotController::class, 'handle']);
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
Route::post('/telegram/egs-attendance-webhook', [EGSAttendanceBotController::class, 'webhook']);
Route::post('/telegram/myfin-egs-webhook', [TelegramMyfinEgsBotController::class, 'webhook']);
Route::post('/telegram/myfin-express-webhook', [TelegramMyfinExpressBotController::class, 'webhook']);
Route::post('/telegram/postix-webhook', [TelegramPostixBotController::class, 'postixWebhook']);
Route::post('/candidate/telegram/webhook', [TelegramCandidateBotController::class, 'candidate']);
Route::post('/express/telegram/webhook', [TelegramCandidateBotController::class, 'express']);
Route::post('/telegram/mobile-app-webhook', [TelegramMobileAppBotController::class, 'mobileApp']);
// HH bot candidate.websol.uz ga ko'chdi — update o'sha yerga uzatiladi (secret sarlavhasi bilan)
Route::post('/telegram/hh-webhook', [TelegramCandidateBotController::class, 'hh']);


Route::post('/login', [AuthController::class, 'login'])->middleware(['throttle:10,1', 'panel.locale']);

// Panel API — token (sanctum) bilan, bo'limlar spatie ruxsatlari bilan
Route::middleware(['auth:sanctum', 'panel.locale'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/me/password', [AuthController::class, 'changePassword']);
    Route::get('/me/telegram', [HhBotController::class, 'myTelegram']);
    Route::post('/me/telegram/link', [HhBotController::class, 'linkTelegram']);
    Route::delete('/me/telegram', [HhBotController::class, 'unlinkTelegram']);
    Route::get('/dashboard', DashboardController::class);

    Route::middleware('permission:attendance.view')->group(function () {
        Route::get('/attendances/search', [AttendanceController::class, 'searchAttendance']);
        Route::get('/attendances/export', [AttendanceController::class, 'exportAttendance']);
    });

    Route::middleware('permission:debts.view')->group(function () {
        Route::get('/debts/search', [DebtViewController::class, 'search']);
    });

    Route::middleware('permission:hh.view')->prefix('hh')->group(function () {
        Route::get('/dictionaries', [HhController::class, 'dictionaries']);
        Route::get('/skills', [HhController::class, 'skills']);
        Route::post('/preview', [HhController::class, 'preview']);
        Route::get('/searches', [HhController::class, 'index']);
        Route::post('/searches', [HhController::class, 'store']);
        Route::get('/searches/{search}', [HhController::class, 'show']);
        Route::put('/searches/{search}', [HhController::class, 'update']);
        Route::delete('/searches/{search}', [HhController::class, 'destroy']);
        Route::post('/searches/{search}/run', [HhController::class, 'run']);
        Route::get('/searches/{search}/candidates', [HhController::class, 'candidates']);
        Route::patch('/candidates/{candidate}', [HhController::class, 'updateCandidate']);
        Route::get('/bot/chats', [HhBotController::class, 'chats']);
        Route::patch('/bot/chats/{chat}', [HhBotController::class, 'updateChat']);
        Route::delete('/bot/chats/{chat}', [HhBotController::class, 'destroyChat']);
        Route::post('/bot/test', [HhBotController::class, 'test']);
    });

    Route::middleware('permission:users.manage')->group(function () {
        Route::apiResource('users', UserController::class)->except('show');
    });
});


////
Route::get('/queries', [QueryController::class, 'index']);
Route::post('/queries', [QueryController::class, 'insert']);
