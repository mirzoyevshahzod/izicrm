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
        Schema::create('material_requests', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('chat_id')->nullable();
            $table->string('last_name')->nullable();
            $table->string('frist_name')->nullable();
            $table->string('telegram_username')->nullable();
            $table->string('status')->default('pending');
            $table->string('approved_by_name')->nullable();
            $table->string('step')->nullable();
            $table->enum('company', [
                'EGS',
                'INCOTRUCK', 
                'EASTLINE EXPRESS', 
                'KGS', 'IZISOL', 
                'TRANSCEKA', 
                'LOGEEL', 
                'CARGOMOST', 
                'WESTLINE'
            ])->nullable();
            $table->string('request_text')->nullable();
            $table->timestamp('requested_at')->nullable();

            $table->timestamps();
        });
    }



    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('material_requests');
    }
};
