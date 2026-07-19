<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('basvurular')) return;

        Schema::create('basvurular', function (Blueprint $table) {
            $table->id();
            $table->string('ad');
            $table->string('ofis')->nullable();
            $table->string('telefon', 32);
            $table->string('eposta')->nullable();
            $table->string('paket', 30)->default('ofis');
            $table->unsignedSmallInteger('danisman_sayisi')->nullable();
            $table->text('mesaj')->nullable();
            $table->string('durum', 20)->default('yeni');
            $table->string('kaynak', 40)->default('site');
            $table->timestamps();

            $table->index('durum');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('basvurular');
    }
};
