<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\TelegramChannel;
use Illuminate\Database\Seeder;

class TelegramChannelSeeder extends Seeder
{
    public function run(): void
    {
        $egs = Company::where('code', 'EGS')->first();

        TelegramChannel::insert([
            [
                'title' => 'EGS Talent Community',
                'username' => '@EGS_Talent_Community',
                'description' => 'Вакансии компании и программа внутренних рекомендаций.',
            ],
            [
                'title' => 'EGS Life',
                'username' => '@EGS_Talent_Community',
                'description' => 'Корпоративный канал с новостями, мероприятиями и тимбилдингами.',
            ],
            [
                'title' => 'Доступные грузы',
                'username' => '@egsgrouplogisticsrequest',
                'description' => 'Актуальная информация о доступных грузах.',
            ],
        ]);
    }
}
