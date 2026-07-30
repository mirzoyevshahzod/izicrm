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
        Schema::create('contact_bot_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('company_slug')->nullable();
            $table->string('telegram_id')->unique();
            $table->enum('type', ['operations', 'sales'])->default('operations');
            $table->dateTime('last_used_at')->nullable();
            $table->string('state')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_bot_users');
    }
};
