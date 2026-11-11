<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Panel: "Xodimlar (Face ID)" bo'limi — attendance_employees CRUD ruxsati
 */
return new class extends Migration
{
    public function up(): void
    {
        Permission::findOrCreate('employees.manage', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::where(['name' => 'employees.manage', 'guard_name' => 'web'])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
