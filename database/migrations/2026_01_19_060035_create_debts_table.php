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
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('employee_name');
            $table->bigInteger('total_amount');
            $table->bigInteger('chat_id');
            $table->bigInteger('day_0_7')->nullable();
            $table->bigInteger('day_8_15')->nullable();
            $table->bigInteger('day_16_30')->nullable();
            $table->bigInteger('day_31_60')->nullable();
            $table->bigInteger('day_61_90')->nullable();
            $table->bigInteger('day_90_plus')->nullable();
            $table->string('uploded_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};
