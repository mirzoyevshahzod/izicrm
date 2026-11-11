<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * HH bo'limi candidate.websol.uz ga ko'chdi — IziCRM dagi HH jadvallari va hh.view ruxsati o'chiriladi.
 * HH bot webhook'i va /hh/callback faqat o'sha yerga uzatiladi (TelegramCandidateBotController::hh, routes/web.php).
 * Qaytarib bo'lmaydi: ma'lumotlar o'chadi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('hh_search_resumes');
        Schema::dropIfExists('hh_resumes');
        Schema::dropIfExists('hh_bot_chats');
        Schema::dropIfExists('hh_searches');
        Schema::dropIfExists('hh_tokens');

        Permission::where(['name' => 'hh.view', 'guard_name' => 'web'])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Jadvallar qayta yaratilmaydi — HH kodi loyihadan olib tashlangan.
    }
};
