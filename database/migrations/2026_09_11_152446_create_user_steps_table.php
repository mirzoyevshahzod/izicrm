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
        Schema::create('user_steps', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('chat_id')->unsigned()->unique();
            $table->string('language')->default('en');
            $table->string('step')->default('start');

            $table->string('from_country')->nullable();
            $table->string('from_city')->nullable();
            $table->string('to_country')->nullable();
            $table->string('to_city')->nullable();

            $table->string('transport_type')->nullable();
            $table->string('temperature_range')->nullable();

            $table->string('pallet_type')->nullable();
            $table->string('pallet_count')->nullable();
            $table->string('pallet_size')->nullable();
            $table->string('customs_size')->nullable();
            $table->decimal('gross_weight', 10, 2)->nullable();

            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_steps');
    }
};
