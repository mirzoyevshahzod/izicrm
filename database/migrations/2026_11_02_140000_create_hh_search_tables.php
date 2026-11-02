<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * hh.uz rezyume qidiruvi: saqlangan filtrlar, topilgan rezyumelar va
 * har bir filtr bo'yicha moslik foizi / HR holati.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hh_searches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('params');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->unsignedInteger('last_found')->nullable();
            $table->text('last_error')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('hh_resumes', function (Blueprint $table) {
            $table->id();
            $table->string('resume_id')->unique();
            $table->string('title')->nullable();
            $table->unsignedSmallInteger('age')->nullable();
            $table->string('gender', 16)->nullable();
            $table->string('area_id', 16)->nullable();
            $table->string('area_name')->nullable();
            $table->decimal('salary_amount', 15, 2)->nullable();
            $table->string('salary_currency', 8)->nullable();
            $table->decimal('salary_uzs', 18, 2)->nullable();
            $table->unsignedInteger('experience_months')->nullable();
            $table->string('education_level', 32)->nullable();
            $table->timestamp('hh_updated_at')->nullable();
            $table->string('alternate_url')->nullable();
            $table->json('data');
            $table->timestamps();
        });

        Schema::create('hh_search_resumes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hh_search_id')->constrained()->cascadeOnDelete();
            $table->foreignId('hh_resume_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score')->default(0);
            $table->json('score_details')->nullable();
            $table->string('status', 16)->default('new'); // new | viewed | selected | rejected
            $table->timestamps();

            $table->unique(['hh_search_id', 'hh_resume_id']);
            $table->index(['hh_search_id', 'status', 'score']);
        });

        Permission::findOrCreate('hh.view', 'web');
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('hh_search_resumes');
        Schema::dropIfExists('hh_resumes');
        Schema::dropIfExists('hh_searches');
        Permission::where('name', 'hh.view')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
