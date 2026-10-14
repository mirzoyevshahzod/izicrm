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
            $table->string('signature_path')->nullable()->after('reason');
        });

        DB::statement("
            ALTER TABLE attendance_late_events
            MODIFY status ENUM(
                'waiting_company',
                'waiting_reason',
                'waiting_signature',
                'completed'
            ) DEFAULT 'waiting_company'
        ");
    }

    public function down(): void
    {
        DB::table('attendance_late_events')
            ->where('status', 'waiting_signature')
            ->update(['status' => 'waiting_reason']);

        DB::statement("
            ALTER TABLE attendance_late_events
            MODIFY status ENUM(
                'waiting_company',
                'waiting_reason',
                'completed'
            ) DEFAULT 'waiting_company'
        ");

        Schema::table('attendance_late_events', function (Blueprint $table) {
            $table->dropColumn('signature_path');
        });
    }
};
