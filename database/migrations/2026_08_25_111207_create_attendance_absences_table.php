<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_absences', function (Blueprint $table) {
            $table->id();
            $table->string('person_id');
            $table->unsignedBigInteger('chat_id')->nullable();
            $table->string('fio');
            $table->string('department')->nullable();
            $table->unsignedTinyInteger('day');
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->timestamps();

            $table->unique(['person_id', 'day', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_absences');
    }
};
