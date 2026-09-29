<?php

return [
    'welcome' => "👋 Добро пожаловать в Transceka Bot!\nПожалуйста, выберите язык:",
    'language_selected' => '✅ Выбран русский язык. Начнем!',
    'select_origin_country' => 'Выберите страну отправления:',
    'select_destination_country' => 'Выберите страну получения:',
    'enter_city' => 'Введите город в :country :flag:',
    'select_transport_type' => 'Выберите тип транспорта:',
    'select_ref_mode' => 'Выберите режим рефрижератора:',
    'enter_temperature_range' => 'Введите диапазон температур', // Yangi kalit qo‘shildi
    'enter_valid_temperature' => 'Пожалуйста, введите корректный диапазон температур', // Yangi kalit qo‘shildi
    'your_temperature' => 'Ваш температурный диапазон:',
    'select_pallet_type' => 'Выберите тип паллет:',
    'enter_pallet_count' => 'Введите количество паллет:',
    'enter_valid_number' => 'Пожалуйста, введите корректное число.',
    'enter_custom_size' => 'Введите размеру своева паллета:',
    'enter_pallet_size' => 'Введите размер паллет:',
    'enter_gross_weight' => 'Введите брутто вес:',
    'enter_valid_weight' => 'Пожалуйста, введите правильный вес (положительное число).',
    'share_contact' => 'Поделитесь контактной информацией:',
    'request_completed' => '✅ Заявка отправлена! Напишите /start чтобы создать новую заявку.',
    'new_transport_request' => '🆕 Новая заявка на перевозку',
    'enter_valid_city' => 'Пожалуйста, введите корректное название города (от 2 до 50 символов, без спецсимволов).',
    'invalid_temperature' => 'Пожалуйста, введите корректный диапазон температур.',
    'invalid_contact' => 'Неверная контактная информация. Пожалуйста, поделитесь контактом снова.',

    // Button texts
    'btn_english' => 'English 🇬🇧',
    'btn_russian' => 'Русский 🇷🇺',
    'btn_uzbek' => 'O\'zbek 🇺🇿',
    'btn_share_contact' => 'Поделиться контактом 📱',
    'btn_ref' => 'Реф 🚛',
    'btn_tent' => 'Тент 🚚',
    'btn_s_mode' => 'C режим ❄️',
    'btn_bez_mode' => 'Без режима 🌡️',

    // Country names
    'country' => [
        'russia' => 'Россия',
        'ukraine' => 'Украина',
        'belarus' => 'Беларусь',
        'turkey' => 'Турция',
        'uzbekistan' => 'Узбекистан',
        'kazakhstan' => 'Казахстан',
        'kyrgyzstan' => 'Кыргызстан',
        'tajikistan' => 'Таджикистан',
        'turkmenistan' => 'Туркменистан',
        'afghanistan' => 'Афганистан',
    ],

    // Temperature options
    'temp' => [
        'cold' => '0°C до -18°C ❄️',
        'warm' => '0°C до +25°C 🌡️',
    ],

    // Pallet types
    'pallet' => [
        'american' => 'Американский паллет (120x120)',
        'finnish' => 'Финский паллет (120x1)',
        'euro' => 'Евро паллет (120x80)',
        'no_standard' => 'Нестандартный',
    ],
];
