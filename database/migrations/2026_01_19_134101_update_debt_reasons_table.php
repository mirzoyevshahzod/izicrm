<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('debt_reasons', function (Blueprint $table) {
            $table->string('period')->after('debt_id');
            $table->bigInteger('chat_id')->after('period');
            $table->text('message_text')->nullable()->change();
            $table->string('file_path')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('debt_reasons', function (Blueprint $table) {
            $table->dropColumn(['period', 'chat_id']);
        });
    }

};
