<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Call;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PanelController extends Controller
{
    public function ozet(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->rol !== 'yonetici') {
            return response()->json(['ok' => false, 'hata' => 'Yetkisiz'], 403);
        }

        $officeId = $user->office_id;

        return response()->json([
            'ok' => true,
            'bugun' => [
                'toplam_arama'   => Call::where('office_id', $officeId)->whereDate('created_at', today())->count(),
                'cevapsiz'       => Call::where('office_id', $officeId)->whereDate('created_at', today())->where('yon', 'cevapsiz')->count(),
                'yeni_lead'      => Lead::where('office_id', $officeId)->whereDate('created_at', today())->count(),
                'toplam_lead'    => Lead::where('office_id', $officeId)->count(),
                'aktif_danisman' => User::where('office_id', $officeId)->where('aktif', true)->where('rol', 'danisman')->count(),
            ],
        ]);
    }

    public function danismanlar(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->rol !== 'yonetici') {
            return response()->json(['ok' => false, 'hata' => 'Yetkisiz'], 403);
        }

        $danismanlar = User::where('office_id', $user->office_id)
            ->where('rol', 'danisman')
            ->where('aktif', true)
            ->get()
            ->map(function ($u) {
                return [
                    'id'            => $u->id,
                    'ad'            => $u->ad,
                    'bu_ay_arama'   => Call::where('user_id', $u->id)->whereMonth('created_at', now()->month)->count(),
                    'bu_ay_lead'    => Lead::where('atanan_user_id', $u->id)->whereMonth('created_at', now()->month)->count(),
                    'satis'         => Lead::where('atanan_user_id', $u->id)->where('asama', 'satis')->whereMonth('updated_at', now()->month)->count(),
                ];
            });

        return response()->json(['ok' => true, 'danismanlar' => $danismanlar]);
    }

    public function huni(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->rol !== 'yonetici') {
            return response()->json(['ok' => false, 'hata' => 'Yetkisiz'], 403);
        }

        $asamalar = ['yeni', 'gorusuldu', 'portfoy', 'randevu', 'gosterim', 'teklif', 'satis'];
        $huni = [];
        foreach ($asamalar as $asama) {
            $huni[$asama] = Lead::where('office_id', $user->office_id)
                ->where('asama', $asama)
                ->whereMonth('created_at', now()->month)
                ->count();
        }

        return response()->json(['ok' => true, 'huni' => $huni]);
    }

    public function kacan(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user->rol !== 'yonetici') {
            return response()->json(['ok' => false, 'hata' => 'Yetkisiz'], 403);
        }

        $kacan = Lead::where('office_id', $user->office_id)
            ->where('asama', 'ulasilamadi')
            ->with('atananUser:id,ad')
            ->latest()
            ->limit(20)
            ->get();

        return response()->json(['ok' => true, 'kacan' => $kacan]);
    }
}
