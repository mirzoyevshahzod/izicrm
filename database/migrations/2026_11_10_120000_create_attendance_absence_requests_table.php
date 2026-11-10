<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Xodim botda "Bugun kela olmayman" tugmasini bosib yozgan sabablar
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_absence_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->string('person_id');
            $table->unsignedBigInteger('chat_id')->nullable();
            $table->string('fio');
            $table->string('department')->nullable();
            $table->date('date');
            $table->text('reason');
            $table->timestamps();
            $table->unique(['person_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_absence_requests');
    }
};
