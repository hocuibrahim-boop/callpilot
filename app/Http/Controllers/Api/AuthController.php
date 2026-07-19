<?php

namespace App\Http\Controllers\Api;

use App\Support\Phone;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'kullanici' => 'required|string',
            'sifre'     => 'required|string',
        ]);

        $kullanici = trim($request->kullanici);
        $sifre     = $request->sifre;

        // Telefonla giris denemesi icin numarayi bicimle.
        // E-posta girildiginde null doner; null'i sorguya KOYMAMAK
        // kritik, cunku Laravel null'i "IS NULL"a cevirir ve
        // telefonu bos olan ilk kullaniciyi dondururdu.
        $telefon = Phone::normalize($kullanici);

        $user = User::where('aktif', true)
            ->where(function ($q) use ($kullanici, $telefon) {
                $q->where('eposta', $kullanici);
                if ($telefon !== null) {
                    $q->orWhere('telefon_normal', $telefon);
                }
            })
            ->first();

        if (!$user || empty($user->sifre_hash) || !password_verify($sifre, $user->sifre_hash)) {
            return response()->json(['ok' => false, 'hata' => 'Hatali kullanici veya sifre'], 401);
        }

        // Her girişte yeni token
        $token = Str::random(32);
        $user->update(['api_token' => $token]);

        return response()->json([
            'ok'       => true,
            'token'    => $token,
            'kullanici' => [
                'id'        => $user->id,
                'ad'        => $user->ad,
                'rol'       => $user->rol,
                'office_id' => $user->office_id,
                'ofis'      => $user->office?->ad ?? '',
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        return response()->json([
            'ok'       => true,
            'kullanici' => [
                'id'        => $user->id,
                'ad'        => $user->ad,
                'rol'       => $user->rol,
                'office_id' => $user->office_id,
                'ofis'      => $user->office?->ad ?? '',
            ],
        ]);
    }
}
