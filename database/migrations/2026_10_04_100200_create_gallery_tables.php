<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gallery_rubrics', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('title');
            $table->string('lede')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('gallery_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubric_id')->constrained('gallery_rubrics')->restrictOnDelete();
            // Попадает в адрес (#foto-fasad) и в имена файлов копий
            $table->string('slug', 40)->unique();
            // Место в сетке: lead, std, tall
            $table->string('size', 10)->default('std');
            $table->string('alt')->nullable();
            $table->string('caption')->nullable();
            $table->string('source_path')->nullable();
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            // Готовые копии: {"avif": {480: path}, "webp": {...}, "jpg": path}
            $table->json('variants')->nullable();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_photos');
        Schema::dropIfExists('gallery_rubrics');
    }
};
