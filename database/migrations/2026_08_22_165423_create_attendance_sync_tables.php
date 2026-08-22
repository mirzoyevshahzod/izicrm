<?php
// database/migrations/xxxx_create_attendance_sync_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_sync_state', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('last_log_id')->default(0);
            $table->timestamps();
        });

        Schema::create('attendance_daily_marks', function (Blueprint $table) {
            $table->id();
            $table->string('person_id');
            $table->date('mark_date');
            $table->unsignedBigInteger('log_id');
            $table->timestamps();
            $table->unique(['person_id', 'mark_date']);
        });

        Schema::create('attendance_late_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('log_id')->unique();
            $table->unsignedBigInteger('chat_id')->nullable();
            $table->string('person_id');
            $table->string('fio');
            $table->string('department')->nullable();
            $table->string('door_name')->nullable();
            $table->string('device_name')->nullable();
            $table->unsignedTinyInteger('day');
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('year');
            $table->integer('late_minutes');
            $table->text('reason')->nullable();
            $table->enum('status', ['waiting_reason', 'completed'])->default('waiting_reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_late_events');
        Schema::dropIfExists('attendance_daily_marks');
        Schema::dropIfExists('attendance_sync_state');
    }
};
