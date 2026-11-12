<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * egs_davomat_bot: the day the reason being typed is for — shown when the button is
 * pressed ("13.11.2026 uchun"), so a reason sent after midnight isn't saved for it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('davomat_chats', function (Blueprint $table) {
            $table->date('pending_date')->nullable()->after('pending_reason');
        });
    }

    public function down(): void
    {
        Schema::table('davomat_chats', function (Blueprint $table) {
            $table->dropColumn('pending_date');
        });
    }
};
