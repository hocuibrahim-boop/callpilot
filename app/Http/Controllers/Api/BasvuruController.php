<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Basvuru;
use App\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BasvuruController extends Controller
{
    /**
     * Siteden gelen deneme basvurusu. Kimlik dogrulamasi gerektirmez.
     */
    public function store(Request $request): JsonResponse
    {
        $dogrulama = validator($request->all(), [
            'ad'      => 'required|string|max:120',
            'telefon' => 'required|string|max:32',
            'ofis'    => 'nullable|string|max:120',
            'eposta'  => 'nullable|email|max:191',
            'paket'   => 'nullable|string|max:30',
            'mesaj'   => 'nullable|string|max:1000',
        ], [
            'ad.required'      => 'Adinizi yazin.',
            'telefon.required' => 'Telefon numarasi gerekli.',
            'eposta.email'     => 'E-posta adresi gecerli degil.',
        ]);

        if ($dogrulama->fails()) {
            return response()->json([
                'ok'   => false,
                'hata' => $dogrulama->errors()->first(),
            ], 422);
        }

        // Ayni numaradan son 10 dakikada gelen tekrari engelle
        $telNorm = Phone::normalize($request->telefon);
        $tekrar = Basvuru::where('telefon', $telNorm)
            ->where('created_at', '>=', now()->subMinutes(10))
            ->exists();

        if ($tekrar) {
            return response()->json([
                'ok'    => true,
                'mesaj' => 'Basvurunuz zaten alindi, en kisa surede donecegiz.',
            ]);
        }

        Basvuru::create([
            'ad'      => $request->ad,
            'ofis'    => $request->ofis,
            'telefon' => $telNorm ?? $request->telefon,
            'eposta'  => $request->eposta,
            'paket'   => $request->paket ?? 'ofis',
            'danisman_sayisi' => $request->danisman_sayisi,
            'mesaj'   => $request->mesaj,
            'kaynak'  => 'site',
        ]);

        return response()->json([
            'ok'    => true,
            'mesaj' => 'Basvurunuz alindi. En kisa surede sizi arayacagiz.',
        ], 201);
    }
}
