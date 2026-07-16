<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CallController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user   = $request->user();
        $kapsam = $request->kapsam ?? 'today';

        $query = Call::where('office_id', $user->office_id)
            ->where('user_id', $user->id)
            ->with('lead:id,ad_soyad,asama,oncelik');

        if ($kapsam === 'today') {
            $query->whereDate('created_at', today());
        }

        $calls     = $query->latest()->get();
        $kayitsiz  = Call::where('office_id', $user->office_id)
            ->where('user_id', $user->id)
            ->whereNull('lead_id')
            ->where('kayit_durumu', 'beklemede')
            ->count();

        return response()->json(['ok' => true, 'aramalar' => $calls, 'kayitsiz_sayi' => $kayitsiz]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->validate([
            'telefon' => 'required|string',
            'yon'     => 'required|in:gelen,giden,cevapsiz',
            'sure_sn' => 'nullable|integer',
        ]);

        $telNorm = normalizePhone($request->telefon);

        // Tekilleştir
        if ($request->santral_cagri_id) {
            $existing = Call::where('santral_cagri_id', $request->santral_cagri_id)->first();
            if ($existing) {
                return response()->json(['ok' => true, 'id' => $existing->id, 'tekrar' => true]);
            }
        }

        // Lead eşleştir
        $lead = Lead::where('office_id', $user->office_id)
            ->where('telefon_normal', $telNorm)->first();

        $call = Call::create([
            'office_id'        => $user->office_id,
            'user_id'          => $user->id,
            'lead_id'          => $lead?->id,
            'telefon'          => $request->telefon,
            'telefon_normal'   => $telNorm,
            'yon'              => $request->yon,
            'baslangic'        => $request->baslangic ?? now(),
            'sure_sn'          => $request->sure_sn ?? 0,
            'kaynak'           => 'cihaz',
            'santral_cagri_id' => $request->santral_cagri_id,
            'kayit_durumu'     => $lead ? 'karta_islendi' : 'beklemede',
        ]);

        // Kayıtsız müşteriyse "kaydet?" push'u gönder
        if (!$lead && $request->yon !== 'cevapsiz' && ($request->sure_sn ?? 0) > 0) {
            $sureMetni = $this->sureBicimle($request->sure_sn ?? 0);
            app(\App\Services\PushService::class)
                ->bildirYeniArama($user, $request->telefon, $sureMetni, $call->id);
        }

        return response()->json(['ok' => true, 'id' => $call->id, 'lead' => $lead], 201);
    }

    private function sureBicimle(int $saniye): string
    {
        if ($saniye < 60) return $saniye . ' sn';
        $dk = intdiv($saniye, 60);
        return $dk . ' dk';
    }

    public function sahsiIsaretle(Request $request, Call $call): JsonResponse
    {
        $call->update(['kayit_durumu' => 'sahsi']);
        return response()->json(['ok' => true]);
    }

    public function geriArandi(Request $request, Call $call): JsonResponse
    {
        $call->update(['donuldu' => true, 'kayit_durumu' => 'karta_islendi']);
        return response()->json(['ok' => true]);
    }
}
