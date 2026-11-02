<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Panel ruxsatlari va admin rolini yaratadi, eski role_user dagi
 * foydalanuvchilarni spatie ga ko'chiradi va eski jadvallarni o'chiradi.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (config('panel.permissions') as $name => $label) {
            Permission::findOrCreate($name, 'web');
        }
        $admin = Role::findOrCreate('admin', 'web');

        if (Schema::hasTable('legacy_role_user') && Schema::hasTable('legacy_roles')) {
            $legacy = DB::table('legacy_role_user')
                ->join('legacy_roles', 'legacy_roles.id', '=', 'legacy_role_user.role_id')
                ->select('legacy_role_user.user_id', 'legacy_roles.name')
                ->get();

            foreach ($legacy as $row) {
                $user = User::find($row->user_id);
                if (!$user) {
                    continue;
                }
                if ($row->name === 'admin') {
                    $user->assignRole($admin);
                } elseif ($row->name === 'operation') {
                    // operatorlar avval davomat sahifasini ko'rardi
                    $user->givePermissionTo('attendance.view');
                }
            }
        }

        Schema::dropIfExists('legacy_role_user');
        Schema::dropIfExists('legacy_roles');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Eski jadvallar 2026_11_02_123000 ning down() ida qayta nomlanadi;
        // admin larni qaytarish uchun ularni shu yerda qayta yaratamiz.
        Schema::create('legacy_roles', function ($table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('legacy_role_user', function ($table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('role_id');
            $table->timestamps();
        });

        $adminId = DB::table('legacy_roles')->insertGetId(['name' => 'admin']);
        DB::table('legacy_roles')->insert(['name' => 'operation']);

        foreach (User::role('admin')->pluck('id') as $userId) {
            DB::table('legacy_role_user')->insert(['user_id' => $userId, 'role_id' => $adminId]);
        }
    }
};
