<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Offices
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->string('ad');
            $table->string('plan', 20)->default('ofis');
            $table->string('tz', 40)->default('Europe/Istanbul');
            $table->string('santral_saglayici', 40)->nullable();
            $table->string('webhook_secret', 191)->nullable();
            $table->timestamps();
        });

        // Users
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->string('rol', 20)->default('danisman'); // yonetici | danisman
            $table->string('ad');
            $table->string('telefon', 32)->nullable();
            $table->string('telefon_normal', 20)->nullable();
            $table->string('eposta', 191)->nullable()->unique();
            $table->string('sifre_hash', 255)->nullable();
            $table->string('api_token', 64)->nullable()->unique();
            $table->string('calisma_baslangic', 8)->default('09:00');
            $table->string('calisma_bitis', 8)->default('19:00');
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        // Devices
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('platform', 10); // android | ios
            $table->string('push_token', 255)->nullable();
            $table->json('izinler_json')->nullable();
            $table->timestamp('son_gorulme')->nullable();
            $table->timestamps();
        });

        // Leads
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('atanan_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('telefon', 32)->nullable();
            $table->string('telefon_normal', 20)->nullable()->index();
            $table->string('ad_soyad')->nullable();
            $table->string('talep_tipi', 30)->nullable(); // satilik_alici | kiralik_kiraci | satici | kiraya_veren
            $table->string('gayrimenkul_turu', 40)->nullable();
            $table->string('bolge', 80)->nullable();
            $table->string('butce', 60)->nullable();
            $table->string('oncelik', 10)->default('orta'); // dusuk | orta | yuksek
            $table->string('asama', 30)->default('yeni');
            $table->string('kaynak', 20)->default('manuel'); // cihaz | santral | manuel | form
            $table->string('kvkk_durumu', 20)->default('aydinlatildi');
            $table->timestamps();

            $table->index(['office_id', 'telefon_normal']);
        });

        // Calls
        Schema::create('calls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('telefon', 32)->nullable();
            $table->string('telefon_normal', 20)->nullable();
            $table->string('yon', 10); // gelen | giden | cevapsiz
            $table->timestamp('baslangic')->nullable();
            $table->integer('sure_sn')->default(0);
            $table->string('kaynak', 10)->default('cihaz'); // cihaz | santral
            $table->string('santral_cagri_id', 80)->nullable()->unique();
            $table->string('kayit_durumu', 20)->default('beklemede'); // beklemede | karta_islendi | sahsi | cevapsiz
            $table->boolean('donuldu')->default(false);
            $table->timestamps();

            $table->index(['office_id', 'created_at']);
            $table->index('lead_id');
        });

        // Lead Notes
        Schema::create('lead_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('metin')->nullable();
            $table->string('ses_url', 255)->nullable();
            $table->text('transkript')->nullable();
            $table->text('ai_ozet')->nullable();
            $table->timestamps();
        });

        // Reminders
        Schema::create('reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('zaman');
            $table->text('not_text')->nullable();
            $table->string('durum', 20)->default('bekliyor'); // bekliyor | tamamlandi | ertelendi
            $table->boolean('bildirim_gonderildi')->default(false);
            $table->timestamps();

            $table->index(['durum', 'zaman']);
        });

        // Stage History
        Schema::create('stage_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('eski_asama', 30)->nullable();
            $table->string('yeni_asama', 30)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        // WA Templates
        Schema::create('wa_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->string('ad', 80)->nullable();
            $table->text('metin')->nullable();
            $table->string('tur', 20)->default('birebir'); // birebir | waba_sablon
            $table->timestamps();
        });

        // Activities
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('office_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tip', 20); // whatsapp | push | ...
            $table->json('veri_json')->nullable();
            $table->timestamps();
        });

        // Consents
        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('tur', 20)->nullable(); // aydinlatma | acik_riza | iys
            $table->string('kanal', 30)->nullable();
            $table->timestamp('zaman')->nullable();
            $table->string('kanit_ref', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consents');
        Schema::dropIfExists('activities');
        Schema::dropIfExists('wa_templates');
        Schema::dropIfExists('stage_history');
        Schema::dropIfExists('reminders');
        Schema::dropIfExists('lead_notes');
        Schema::dropIfExists('calls');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('devices');
        Schema::dropIfExists('users');
        Schema::dropIfExists('offices');
    }
};
