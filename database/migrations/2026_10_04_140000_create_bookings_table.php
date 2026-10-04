<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Заявки на бронь с сайта. Это персональные данные (152-ФЗ): храним только нужное
        // и удаляем через срок из настроек (bookings:prune)
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('time', 5);
            $table->unsignedSmallInteger('guests');
            $table->string('name', 80);
            $table->string('phone', 30);
            $table->string('comment', 500)->nullable();
            $table->string('status', 20)->default('new')->index();
            $table->string('source', 20)->default('site');
            // Когда гость согласился на обработку персональных данных
            $table->timestamp('consent_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
