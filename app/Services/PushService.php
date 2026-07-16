<?php

namespace App\Services;

use App\Models\Device;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushService
{
    /**
     * Bir kullanıcının tüm cihazlarına push bildirimi gönderir.
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        $devices = Device::where('user_id', $user->id)
            ->whereNotNull('push_token')
            ->get();

        foreach ($devices as $device) {
            $this->send($device, $title, $body, $data);
        }
    }

    /**
     * Tek bir cihaza push bildirimi gönderir.
     */
    public function send(Device $device, string $title, string $body, array $data = []): bool
    {
        $serverKey = config('services.fcm.server_key');

        // FCM anahtarı yoksa sadece logla (geliştirme modu)
        if (empty($serverKey)) {
            Log::info('Push bildirimi (FCM anahtarı yok, loglandı)', [
                'device'   => $device->id,
                'platform' => $device->platform,
                'title'    => $title,
                'body'     => $body,
                'data'     => $data,
            ]);
            return false;
        }

        try {
            $payload = [
                'to' => $device->push_token,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                    'sound' => 'default',
                ],
                'data' => array_merge($data, [
                    'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                ]),
                'priority' => 'high',
            ];

            $response = Http::withHeaders([
                'Authorization' => 'key=' . $serverKey,
                'Content-Type'  => 'application/json',
            ])->post('https://fcm.googleapis.com/fcm/send', $payload);

            if ($response->successful()) {
                $result = $response->json();
                // Token geçersizse cihazı temizle
                if (($result['failure'] ?? 0) > 0) {
                    $error = $result['results'][0]['error'] ?? '';
                    if (in_array($error, ['NotRegistered', 'InvalidRegistration'])) {
                        $device->update(['push_token' => null]);
                        Log::info('Geçersiz push token temizlendi', ['device' => $device->id]);
                    }
                }
                return true;
            }

            Log::warning('FCM push başarısız', ['status' => $response->status(), 'body' => $response->body()]);
            return false;
        } catch (\Exception $e) {
            Log::error('Push gönderim hatası', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Yeni arama bildirimi — danışmana "müşteri kaydet?" push'u.
     */
    public function bildirYeniArama(User $user, string $telefon, string $sure, int $callId): void
    {
        $this->sendToUser(
            $user,
            'Yeni Görüşme',
            "$telefon ile $sure görüştünüz. Müşteri olarak kaydetmek ister misiniz?",
            [
                'tip'       => 'yeni_arama',
                'telefon'   => $telefon,
                'sure'      => $sure,
                'call_id'   => (string) $callId,
            ]
        );
    }

    /**
     * Hatırlatma bildirimi — vakti gelen tekrar arama.
     */
    public function bildirHatirlatma(User $user, string $musteriAdi, int $leadId, int $reminderId): void
    {
        $this->sendToUser(
            $user,
            'Hatırlatma',
            "$musteriAdi için tekrar arama zamanı geldi.",
            [
                'tip'         => 'hatirlatma',
                'lead_id'     => (string) $leadId,
                'reminder_id' => (string) $reminderId,
            ]
        );
    }
}
