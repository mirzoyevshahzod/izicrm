<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SuperAdminSeeder extends Seeder
{
    public function run()
    {
        // 1️⃣ Admin rolini yaratish (agar mavjud bo'lmasa)
        $roleId = DB::table('roles')->updateOrInsert(
            ['name' => 'admin'],
            ['created_at' => now(), 'updated_at' => now()]
        );

        $role = DB::table('roles')->where('name', 'admin')->first();

        // 2️⃣ Super admin foydalanuvchini yaratish
        $user = User::updateOrCreate(
            ['email' => 'super@admin.uz'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('izicrm2025'),
            ]
        );

        // 3️⃣ Role_user jadvaliga bog'lash
        DB::table('role_user')->updateOrInsert(
            [
                'user_id' => $user->id,
                'role_id' => $role->id,
            ]
        );

        // 4️⃣ Sanctum token yaratish
        $token = $user->createToken('Super Admin Token', ['*'])->plainTextToken;

        $this->command->info("✅ Super Admin yaratildi!");
        $this->command->info("Email: super@admin.uz");
        $this->command->info("Password: izicrm2025");
        $this->command->info("Sanctum Token: $token");
    }
}
