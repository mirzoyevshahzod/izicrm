<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN2'),
        'bot_token3' => env('TELEGRAM_BOT_TOKEN3'),
        'contact_bot_token' => env('TELEGRAM_CONTACT_BOT_TOKEN'),
        'incotruck_request_bot' => env('TELEGRAN_INCOTRUCK_BOT_TOKEN'),
        'egs_materialniy_otchet_bot_token' => env('TELEGRAM_MATERIAL_REQUEST_BOT_TOKEN'),
        'contact_as_bot_token' => env('TELEGRAM_CONTACT_AS_BOT_TOKEN'),
        'telegram_material_request_group_id' => env('TELEGRAM_MATERIAL_REQUEST_GROUP_ID'),
        'telegram_tariff_bot_token' => env('TELEGRAM_TARIFF_BOT_TOKEN'),
        'telegram_super_admin_id' => env('TELEGRAM_SUPER_ADMIN_ID'),
        'feedback_group_id' => env('TELEGRAM_FEEDBACK_GROUP_ID'),
    ],

];
