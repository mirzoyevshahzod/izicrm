<?php

namespace App\Builders;

class MaterialRequestKeyboardBuilder
{
    public static function companies(): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => 'EGS',            'callback_data' => 'EGS'],
                    ['text' => 'INCOTRUCK',       'callback_data' => 'INCOTRUCK'],
                ],
                [
                    ['text' => 'EASTLINE EXPRESS','callback_data' => 'EASTLINE EXPRESS'],
                    ['text' => 'KGS',             'callback_data' => 'KGS'],
                ],
                [
                    ['text' => 'IZISOL',          'callback_data' => 'IZISOL'],
                    ['text' => 'TRANSCEKA',        'callback_data' => 'TRANSCEKA'],
                ],
                [
                    ['text' => 'LOGEEL',          'callback_data' => 'LOGEEL'],
                    ['text' => 'CARGOMOST',        'callback_data' => 'CARGOMOST'],
                ],
                [
                    ['text' => 'WESTLINE',         'callback_data' => 'WESTLINE'],
                ],
            ],
        ];
    }

    public static function backToCompanies(): array
    {
        return [
            'inline_keyboard' => [
                [
                    ['text' => '⬅️ Ortga', 'callback_data' => 'back_to_companies'],
                ],
            ],
        ];
    }
}