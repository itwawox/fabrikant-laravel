<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            // Якорь карточки на странице «Акции» (#happyhours) — на него ведёт плашка
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('discount', 20)->nullable();
            $table->string('subject')->nullable();
            // Коротко для плашки «Сейчас действует»: «−25% на меню кухни»
            $table->string('short')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('photo_webp_path')->nullable();
            $table->string('alt')->nullable();
            // Строки карточки списком [{label, value}] — список, а не объект, чтобы порядок был явным
            $table->json('rows')->nullable();
            $table->json('terms')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            // Расписание по времени Симферополя. days — 1 (пн) … 7 (вс); null — акция без расписания
            // («Имениннику»): она есть на странице «Акции», но в плашке не появляется.
            $table->json('days')->nullable();
            $table->string('time_from', 5)->nullable();
            $table->string('time_to', 5)->nullable();
            $table->boolean('not_holidays')->default(false);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->timestamps();
        });

        // Праздники без года (месяц-день): в них не идут акции с not_holidays
        Schema::create('holidays', function (Blueprint $table) {
            $table->id();
            $table->string('month_day', 5)->unique();
            $table->string('title')->nullable();
            $table->timestamps();
        });

        // Конкретные дни без акций: концерты, переносы выходных
        Schema::create('promo_blackouts', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->string('reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_blackouts');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('promos');
    }
};
