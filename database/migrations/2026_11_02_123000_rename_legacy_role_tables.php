<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Eski roles / role_user jadvallari spatie/laravel-permission jadvallari bilan
 * nomi to'qnashadi. Ularni vaqtincha qayta nomlaymiz, ma'lumot keyingi
 * migratsiyada spatie ga ko'chiriladi.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('role_user')) {
            Schema::rename('role_user', 'legacy_role_user');
        }
        if (Schema::hasTable('roles') && !Schema::hasColumn('roles', 'guard_name')) {
            Schema::rename('roles', 'legacy_roles');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('legacy_roles')) {
            Schema::rename('legacy_roles', 'roles');
        }
        if (Schema::hasTable('legacy_role_user')) {
            Schema::rename('legacy_role_user', 'role_user');
        }
    }
};
