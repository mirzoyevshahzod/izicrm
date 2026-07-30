<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\DepartmentHead;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    public function run(): void
    {

        $companies = [
            [
                'code' => 'EGS',
                'name' => 'Eastline General Services',
                'description' => 'EASTLINE GENERAL SERVICES GROUP',
                'address' => 'Tashkent, NestOne',
                'phone' => '+998712000806',
                'website' => 'https://egsgroup.uz',
                'email' => 'info@egsgroup.uz',
                'logo_path' => 'logos/egs.png',
                'departments' => [
                    [
                        'name' => 'sales',
                        'head' => [
                            'full_name' => "SAFAROV ULUG'BEK",
                            'position' => 'Sales Manager',
                            'phone' => '+998946602505',
                            'telegram' => '@ulugbekeastline_uz',
                            'email' => 'tls.ulugbek@gmail.com',
                        ]
                    ],
                    [
                        'name' => 'operations',
                        'head' => [
                            'full_name' => "ALISHER JO'RAQULOV",
                            'position' => 'Operations Manager',
                            'phone' => '+998946608101',
                            'telegram' => '@alisheregs',
                            'email' => 'alisher@egs.uz',
                        ]
                    ],
                ]
            ],

            [
                'code' => 'TLS',
                'name' => 'TRANCEKA',
                'description' => 'TRANSCEKA GROUP',
                'address' => 'Tashkent, NestOne',
                'phone' => '+998781136232',
                'website' => 'https://transceka.uz/en/',
                'email' => 'info@transceka.uz',
                'logo_path' => 'logos/tls.png',
                'departments' => [
                    [
                        'name' => 'sales',
                        'head' => [
                            'full_name' => "SAFAROV ULUG'BEK",
                            'position' => 'Sales Manager',
                            'phone' => '+998946602505',
                            'telegram' => '@ulugbekeastline_uz',
                            'email' => 'tls.ulugbek@gmail.com',
                        ]
                    ],
                    [
                        'name' => 'operations',
                        'head' => [
                            'full_name' => "ALISHER JO'RAQULOV",
                            'position' => 'Operations Manager',
                            'phone' => '+998946608101',
                            'telegram' => '@alisheregs',
                            'email' => 'alisher@egs.uz',
                        ]
                    ],
                ]
            ],

            [
                'code' => 'KGS',
                'name' => 'CARGOMOST',
                'description' => null,
                'address' => null,
                'phone' => null,
                'website' => 'https://www.cargomost.com/',
                'email' => null,
                'logo_path' => null,
                'departments' => [],
            ],

            [
                'code' => 'EXP',
                'name' => 'Eastline Express',
                'description' => 'EASTLINE EXPRESS GROUP',
                'address' => 'Tashkent, NestOne',
                'phone' => '+998712002000',
                'website' => 'https://www.eastline.uz/',
                'email' => null,
                'logo_path' => 'logos/exp.png',
                'departments' => [
                    [
                        'name' => 'sales',
                        'head' => [
                            'full_name' => "SAFAROV ULUG'BEK",
                            'position' => 'Sales Manager',
                            'phone' => '+998946602505',
                            'telegram' => '@ulugbekeastline_uz',
                            'email' => 'tls.ulugbek@gmail.com',
                        ]
                    ],
                    [
                        'name' => 'operations',
                        'head' => [
                            'full_name' => "ALISHER JO'RAQULOV",
                            'position' => 'Support Manager',
                            'phone' => '+998946608101',
                            'telegram' => '@alisheregs',
                            'email' => 'alisher@eastline.uz',
                        ]
                    ],
                ]
            ],

            [
                'code' => 'WESTLINE',
                'name' => 'WESTLINE GLOBAL SERVICE',
                'description' => null,
                'address' => null,
                'phone' => null,
                'website' => 'https://westlinegs.com/',
                'email' => null,
                'logo_path' => null,
                'departments' => [],
            ],

            [
                'code' => 'INCOTRUCK',
                'name' => 'INCOTRUCK',
                'description' => null,
                'address' => null,
                'phone' => null,
                'website' => 'https://incotruck.com/',
                'email' => null,
                'logo_path' => null,
                'departments' => [],
            ],
        ];

        foreach ($companies as $companyData) {

            $departments = $companyData['departments'];
            unset($companyData['departments']);

            $company = Company::create($companyData);

            foreach ($departments as $departmentData) {

                $head = $departmentData['head'];
                unset($departmentData['head']);

                $department = Department::create([
                    'company_id' => $company->id,
                    'name' => $departmentData['name'],
                    'description' => null,
                ]);

                DepartmentHead::create([
                    'department_id' => $department->id,
                    'full_name' => $head['full_name'],
                    'position' => $head['position'],
                    'phone' => $head['phone'],
                    'telegram' => $head['telegram'],
                    'email' => $head['email'],
                ]);
            }
        }
    }
}
