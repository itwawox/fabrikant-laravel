<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Тексты и SEO страниц, которые правятся в админке. Пустое поле — текст по умолчанию из App\Support\PageTexts
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('key', 30)->unique();
            $table->string('seo_title')->nullable();
            $table->string('seo_description', 500)->nullable();
            // Картинка превью ссылки (Telegram, ВКонтакте), путь на публичном диске
            $table->string('og_image')->nullable();
            $table->json('content')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
