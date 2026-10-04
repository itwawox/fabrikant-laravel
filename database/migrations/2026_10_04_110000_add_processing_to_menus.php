<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            // Перенести подписи страниц и разделы из опубликованного меню (если страниц столько же)
            $table->boolean('carry_over')->default(true)->after('storage_dir');
            // Прогресс обработки для админки: «Страница 3 из 8»
            $table->string('progress_step', 20)->nullable()->after('carry_over');
            $table->unsignedSmallInteger('progress_done')->default(0)->after('progress_step');
            $table->unsignedSmallInteger('progress_total')->default(0)->after('progress_done');
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn(['carry_over', 'progress_step', 'progress_done', 'progress_total']);
        });
    }
};
