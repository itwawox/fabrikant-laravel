<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Меню одного сезона: PDF из типографии и картинки его страниц.
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('season');
            // Основа имён файлов (menu_restaurant_fabrikant2026_osen). У каждого меню своя:
            // браузер держит картинки в кэше месяц, и под старым именем гость увидел бы старые цены.
            $table->string('name')->unique();
            $table->string('status', 20)->default('draft')->index();
            $table->string('source_pdf_path')->nullable();
            $table->string('web_pdf_path')->nullable();
            $table->unsignedSmallInteger('pages_count')->default(0);
            // Размер листа в пунктах PDF (A3 = 841.89 × 1190.55)
            $table->decimal('sheet_w_pt', 7, 2)->nullable();
            $table->decimal('sheet_h_pt', 7, 2)->nullable();
            $table->string('storage_dir')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('error')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('menu_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('number');
            // Подпись в содержании и описание страницы для незрячих: «Горячее и гриль»
            $table->string('title')->nullable();
            // Размер картинки 1600 px в пикселях — для width/height у <img> (без скачков вёрстки)
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->timestamps();

            $table->unique(['menu_id', 'number']);
        });

        Schema::create('menu_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('page_number');
            $table->string('title');
            // Адрес раздела (/menu#supy). На него печатают QR-коды, поэтому его можно закрепить:
            // тогда переименование раздела адрес не меняет.
            $table->string('slug');
            $table->boolean('slug_locked')->default(false);
            // Порядок на телефоне и в оглавлении
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();

            $table->unique(['menu_id', 'slug']);
        });

        // Рамка раздела на листе в долях 0…1. Раздел, перетекающий в соседнюю колонку, — несколько рамок.
        Schema::create('menu_section_boxes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('menu_sections')->cascadeOnDelete();
            $table->decimal('x', 4, 3);
            $table->decimal('y', 4, 3);
            $table->decimal('w', 4, 3);
            $table->decimal('h', 4, 3);
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_section_boxes');
        Schema::dropIfExists('menu_sections');
        Schema::dropIfExists('menu_pages');
        Schema::dropIfExists('menus');
    }
};
