<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_late_events', function (Blueprint $table) {
            $table->string('company')->nullable()->after('department');
        });

        DB::statement("
            ALTER TABLE attendance_late_events
            MODIFY status ENUM(
                'waiting_company',
                'waiting_reason',
                'completed'
            ) DEFAULT 'waiting_company'
        ");
    }

    public function down(): void
    {
        Schema::table('attendance_late_events', function (Blueprint $table) {
            $table->dropColumn('company');
        });

        DB::statement("
            ALTER TABLE attendance_late_events
            MODIFY status ENUM(
                'waiting_reason',
                'completed'
            ) DEFAULT 'waiting_reason'
        ");
    }
};
