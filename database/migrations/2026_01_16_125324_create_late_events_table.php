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
        Schema::create('late_events', function (Blueprint $table) {
        $table->id();
        $table->bigInteger('chat_id')->index();
        $table->string('fio');
        $table->string('company')->nullable();
        $table->unsignedTinyInteger('day');
        $table->unsignedTinyInteger('month');
        $table->unsignedSmallInteger('year');
        $table->integer('late_minutes');
        $table->text('reason')->nullable();
        $table->enum('status', ['waiting_company', 'waiting_reason', 'completed'])
            ->default('waiting_company');
        $table->timestamps();
});

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('late_events');
    }
};
