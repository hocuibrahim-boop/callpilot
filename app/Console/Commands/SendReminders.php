<?php

namespace App\Console\Commands;

use App\Models\Reminder;
use App\Services\PushService;
use Illuminate\Console\Command;

class SendReminders extends Command
{
    protected $signature = 'callpilot:reminders';
    protected $description = 'Vakti gelen hatirlatmalar icin push bildirimi gonderir';

    public function handle(PushService $push): int
    {
        $due = Reminder::where('durum', 'bekliyor')
            ->where('bildirim_gonderildi', false)
            ->where('zaman', '<=', now())
            ->with(['lead:id,ad_soyad', 'user:id,ad'])
            ->limit(100)
            ->get();

        $sayi = 0;
        foreach ($due as $r) {
            if (!$r->user || !$r->lead) continue;

            $push->bildirHatirlatma(
                $r->user,
                $r->lead->ad_soyad ?? 'Musteri',
                $r->lead_id,
                $r->id
            );

            $r->update(['bildirim_gonderildi' => true]);
            $sayi++;
        }

        $this->info("Gonderilen hatirlatma: {$sayi}");
        return self::SUCCESS;
    }
}
