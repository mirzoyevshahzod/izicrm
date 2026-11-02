<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * HH bot: yangi nomzodlarni Telegram'ga yuborish.
 * hh_bot_chats — xabar oladiganlar (panelga bog'langan HR shaxsiy chatlari va guruhlar).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hh_bot_chats', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('chat_id')->unique();
            $table->string('type', 16); // private | group | supergroup
            $table->string('title')->nullable();
            $table->string('username')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->cascadeOnDelete();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::table('hh_searches', function (Blueprint $table) {
            $table->boolean('notify')->default(true)->after('is_active');
            $table->unsignedTinyInteger('notify_min_score')->default(70)->after('notify');
        });
    }

    public function down(): void
    {
        Schema::table('hh_searches', function (Blueprint $table) {
            $table->dropColumn(['notify', 'notify_min_score']);
        });
        Schema::dropIfExists('hh_bot_chats');
    }
};
