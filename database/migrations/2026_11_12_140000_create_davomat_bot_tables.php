<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * egs_davomat_bot: xodim kechikishi / kelmasligi sababini yozadi, CRM (crm.zanjeer.uz)
 * uni olib ketib Посещаемость dagi "Причина" ga yozadi.
 *
 * CRM ofis tarmog'ida — internetdan unga yozib bo'lmaydi, shuning uchun:
 *  - davomat_employees: CRM o'zi yuboradigan xodimlar ro'yxati (korporativ raqam bilan);
 *  - davomat_reasons:   CRM o'zi olib ketadigan sabablar (id bo'yicha, oxirgisidan keyingilar).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('davomat_employees', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('crm_user_id')->unique();
            $table->string('name');
            $table->string('phone')->index();
            $table->timestamps();
        });

        Schema::create('davomat_chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('telegram_id')->unique();
            $table->string('phone')->nullable();
            $table->unsignedBigInteger('crm_user_id')->nullable();
            $table->string('pending_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('davomat_reasons', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('crm_user_id');
            $table->unsignedBigInteger('telegram_id');
            $table->string('type');
            $table->text('reason');
            $table->date('date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('davomat_reasons');
        Schema::dropIfExists('davomat_chats');
        Schema::dropIfExists('davomat_employees');
    }
};
