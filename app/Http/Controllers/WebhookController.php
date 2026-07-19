<?php

namespace App\Http\Controllers;

use App\Support\Phone;

use App\Models\Call;
use App\Models\Lead;
use App\Models\Office;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function verimor(Request $request): JsonResponse
    {
        // Secret doğrulama
        $secret = $request->query('secret', '');
        if (!hash_equals(config('services.callpilot.webhook_secret', ''), $secret)) {
            return response()->json(['ok' => false, 'hata' => 'Yetkisiz'], 403);
        }

        $data = $request->all();
        Log::info('Verimor webhook', $data);

        // Alanları normalize et (Verimor farklı alan adları kullanabilir)
        $caller   = $data['caller'] ?? $data['source'] ?? $data['from'] ?? $data['calling_number'] ?? null;
        $callee   = $data['callee'] ?? $data['destination'] ?? $data['to'] ?? $data['called_number'] ?? null;
        $duration = (int)($data['duration'] ?? $data['billsec'] ?? $data['sure'] ?? 0);
        $direction= $data['direction'] ?? ($caller ? 'gelen' : 'giden');
        $uniqueId = $data['uniqueid'] ?? $data['call_id'] ?? $data['uuid'] ?? null;
        $status   = $data['status'] ?? $data['disposition'] ?? '';

        // Cevapsız kontrolü
        $yon = match(true) {
            str_contains(strtolower($status), 'no answer'), $duration === 0 => 'cevapsiz',
            $direction === 'inbound' => 'gelen',
            default => 'giden',
        };

        // Tekilleştir
        if ($uniqueId && Call::where('santral_cagri_id', $uniqueId)->exists()) {
            return response()->json(['ok' => true, 'tekrar' => true]);
        }

        // Dahili numara → kullanıcı eşleştir
        $user = null;
        if ($callee) {
            $user = User::where('telefon_normal', Phone::normalize($callee))->first();
        }

        $telefon = $caller ?? $callee;
        $telNorm = $telefon ? Phone::normalize($telefon) : null;

        // Lead eşleştir
        $officeId = $user?->office_id;
        $lead = $officeId && $telNorm
            ? Lead::where('office_id', $officeId)->where('telefon_normal', $telNorm)->first()
            : null;

        $call = Call::create([
            'office_id'        => $officeId,
            'user_id'          => $user?->id,
            'lead_id'          => $lead?->id,
            'telefon'          => $telefon,
            'telefon_normal'   => $telNorm,
            'yon'              => $yon,
            'baslangic'        => now(),
            'sure_sn'          => $duration,
            'kaynak'           => 'santral',
            'santral_cagri_id' => $uniqueId,
            'kayit_durumu'     => $lead ? 'karta_islendi' : 'beklemede',
        ]);

        // Kayıtsız müşteri + danışman atanmışsa "kaydet?" push'u gönder
        if (!$lead && $user && $yon !== 'cevapsiz' && $duration > 0) {
            $dk = $duration < 60 ? $duration . ' sn' : intdiv($duration, 60) . ' dk';
            app(\App\Services\PushService::class)
                ->bildirYeniArama($user, $telefon, $dk, $call->id);
        }

        return response()->json(['ok' => true]);
    }
}
