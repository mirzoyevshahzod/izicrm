<?php

/*
|--------------------------------------------------------------------------
| Telegram webhook watchdog
|--------------------------------------------------------------------------
| `telegram:webhook-watch` komandasi har bir bot uchun getWebhookInfo
| qiladi va URL bo'sh yoki boshqa bo'lib qolsa, shu yerdagi URL ga
| qaytadan setWebhook qiladi. Yangi bot qo'shsangiz shu ro'yxatga qo'shing.
*/

return [

    // Webhook qayta o'rnatilganda xabar yuboriladigan Telegram chat ID
    'notify_chat_id' => env('TELEGRAM_WEBHOOK_NOTIFY_CHAT_ID', env('TELEGRAM_IT_ADMIN_ID')),

    'bots' => [
        'logistic_group_bot' => [
            'token' => env('TELEGRAM_TRANCEKA_BOT_TOKEN'),
            'url'   => 'https://webhook.izicrm.uz/api/telegram/tranceka-webhook',
        ],
        'E_Ombor_Bot' => [
            'token' => env('TELEGRAM_E_OMBOR_BOT_TOKEN'),
            'url'   => 'https://webhook.izicrm.uz/api/telegram-webhook',
        ],
        'egs_davomat_bot' => [
            'token' => env('TELEGRAM_DAVOMAT_BOT_TOKEN'),
            'url'   => 'https://webhook.izicrm.uz/api/telegram/davomat-webhook',
        ],
        'egs_zadolzhennost_bot' => [
            'token' => env('TELEGRAM_BOT_TOKEN3'),
            'url'   => 'https://webhook.izicrm.uz/api/telegram/debt-webhook',
        ],
        'EGS_phone_number_bot' => [
            'token' => env('TELEGRAM_CONTACT_BOT_TOKEN'),
            'url'   => 'https://webhook.izicrm.uz/api/telegram/contact-webhook',
        ],
        'egs_materialniy_otchet_bot' => [
            'token' => env('TELEGRAM_MATERIAL_REQUEST_BOT_TOKEN'),
            'url'   => 'https://webhook.izicrm.uz/api/telegram/material-request-webhook',
        ],
        'Kanal_13_Admin_Bot' => [
            'token' => env('TELEGRAM_TARIFF_BOT_TOKEN'),
            'url'   => 'https://izicrm.uz/api/tariff-webhook',
        ],
        'contact_asst_bot' => [
            'token' => env('TELEGRAM_CONTACT_AS_BOT_TOKEN'),
            'url'   => 'https://webhook.izicrm.uz/api/telegram/webhook',
        ],
        'egs_attendance_bot' => [
            'token' => env('TELEGRAM_EGS_ATTENDANCE_BOT_TOKEN'),
            'url'   => 'https://webhook.izicrm.uz/api/telegram/egs-attendance-webhook',
        ],
        'MyFinEGSbot' => [
            'token' => env('TELEGRAM_MYFIN_EGS_BOT_TOKEN'),
            'url'   => 'https://webhook.izicrm.uz/api/telegram/myfin-egs-webhook',
        ],
        'MyFinExpressBot' => [
            'token' => env('TELEGRAM_MYFIN_EXPRESS_BOT_TOKEN'),
            'url'   => 'https://webhook.izicrm.uz/api/telegram/myfin-express-webhook',
        ],
        'eastline_express_bot' => [
            'token' => env('TELEGRAM_EXPRESS_BOT_TOKEN'),
            'url'   => 'https://webhook.izicrm.uz/api/telegram/express-webhook',
        ],
    ],

];
