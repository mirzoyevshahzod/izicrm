<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\TelegramBot;
use Illuminate\Database\Seeder;

class TelegramBotSeeder extends Seeder
{
    public function run(): void
    {
        $egs = Company::where('code', 'EGS')->first();

        TelegramBot::insert([
            [
                'title' => 'Information Bot',
                'username' => '@EGS_phone_number_bot',
                'description' => '🤖 Ушбу бот орқали компания ҳақидаги асосий маълумотлар, хизматлар, алоқа маълумотлари ва бошқа муҳим маълумотлар билан танишишингиз мумкин.',
            ],
            [
                'title' => 'EGS Davomat Bot',
                'username' => '@egs_davomat_bot',
                'description' => '⏰ Ушбу бот ходимларнинг давоматини назорат қилиш ҳамда ишга кеч қолган ходимлардан кечикиш сабабини қабул қилиш учун мўлжалланган.',
            ],
            [
                'title' => 'EGS Materialniy Otchet Bot',
                'username' => '@egs_materialniy_otchet_bot',
                'description' => '📋 Ушбу бот орқали ходимлар ўзларига зарур бўлган моддий воситалар учун ариза қолдиришлари мумкин. Мурожаат раҳбариятга юборилади ва тасдиқланганидан сўнг ижроси таъминланади.
',
            ],
        ]);
    }
}
