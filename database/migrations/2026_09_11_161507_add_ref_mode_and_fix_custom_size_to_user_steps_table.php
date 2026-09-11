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
        Schema::table('user_steps', function (Blueprint $table) {
            if (!Schema::hasColumn('user_steps', 'ref_mode')) {
                $table->string('ref_mode')->nullable()->after('transport_type');
            }
        });

        if (Schema::hasColumn('user_steps', 'customs_size') && !Schema::hasColumn('user_steps', 'custom_size')) {
            Schema::table('user_steps', function (Blueprint $table) {
                $table->renameColumn('customs_size', 'custom_size');
            });
        } elseif (!Schema::hasColumn('user_steps', 'custom_size')) {
            Schema::table('user_steps', function (Blueprint $table) {
                $table->string('custom_size')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_steps', function (Blueprint $table) {
            if (Schema::hasColumn('user_steps', 'ref_mode')) {
                $table->dropColumn('ref_mode');
            }
        });

        if (Schema::hasColumn('user_steps', 'custom_size')) {
            Schema::table('user_steps', function (Blueprint $table) {
                $table->renameColumn('custom_size', 'customs_size');
            });
        }
    }
};
