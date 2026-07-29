<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::insert([
            [
                'code' => 'EGS',
                'name' => 'Eastline General Services',
                'website' => 'https://egsgroup.uz',
            ],
            [
                'code' => 'TLS',
                'name' => 'TRANCEKA',
                'website' => 'https://transceka.uz/en/',
            ],
            [
                'code' => 'KGS',
                'name' => 'CARGOMOST',
                'website' => 'https://www.cargomost.com/',
            ],
            [
                'code' => 'EXP',
                'name' => 'Eastline Express',
                'website' => 'https://www.cargomost.com/',
            ],
            [
                'code' => 'WESTLINE',
                'name' => 'WESTLINE GLOBAL SERVICE',
                'website' => 'https://westlinegs.com/',
            ],
            [
                'code' => 'INCOTRUCK',
                'name' => 'INCOTRUCK',
                'website' => 'https://incotruck.com/',
            ],
        ]);
    }
}
