<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InformationLink;

class InformationLinkSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        InformationLink::truncate();

        InformationLink::insert([
            [
                'type' => 'operations',
                'title' => '🚛 Актуальная информация о доступных грузах',
                'url' => 'https://t.me/egsgrouplogisticsrequest',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'type' => 'operations',
                'title' => '👨‍💼 Вакансии компании',
                'url' => 'https://t.me/EGS_Talent_Community',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
