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

        // E-posta veya telefon ile ara
        $user = User::where(function ($q) use ($kullanici) {
            $q->where('eposta', $kullanici)
              ->orWhere('telefon_normal', Phone::normalize($kullanici));
        })->where('aktif', true)->first();

        if (!$user || !password_verify($sifre, $user->sifre_hash)) {
            return response()->json(['ok' => false, 'hata' => 'Hatalı kullanıcı veya şifre'], 401);
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
