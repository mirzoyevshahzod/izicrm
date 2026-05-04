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
        Schema::create('user_states', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('chat_id')->unique();
            $table->bigInteger('message_id')->nullable();
            $table->foreignId('debt_id')->constrained()->cascadeOnDelete();
            $table->string('period')->nullable();
            $table->enum('status', ['waiting_reason', 'finished'])->default('waiting_reason');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_states');
    }
};
