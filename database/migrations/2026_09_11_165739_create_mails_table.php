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
        Schema::create('mails', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('chat_id')->nullable();     // Telegram chat ID
            $table->string('step')->nullable();   // Joriy bosqich

            $table->string('lang')->nullable();

            $table->string('sender_name')->nullable();            // Yuboruvchining_ismi
            $table->string('sender_address')->nullable();         // Yuboruvchining_manzili
            $table->string('sender_phone')->nullable();           // Yuboruvchining_tel_raqami

            $table->string('recipient_name')->nullable();         // Qabul_qiluvchining_ismi
            $table->string('recipient_address')->nullable();      // Qabul_qiluvchining_manzili
            $table->string('recipient_country')->nullable();      // Qabul_qiluvchining_davlati
            $table->string('recipient_postal_code')->nullable();  // Qabul_qiluvchining_index

            $table->string('tarif')->nullable();                 // Tarif
            $table->string('delivery_time')->nullable();          // Yetkazib_berish_muddati
            $table->decimal('delivery_price', 10, 2)->nullable(); // Yetkazib_berish_narxi
            $table->string('currier_phone')->nullable(); //currier telefon raqami
            $table->json('images')->nullable(); //Rasm

            $table->string('status')->default('pending')->nullable(); //Zayavka statusi
            $table->boolean('oferta_checked')->nullable()->default(false); // Oferta holati

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mails');
    }
};
