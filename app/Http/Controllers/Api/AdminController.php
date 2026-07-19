<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Basvuru;
use App\Models\Call;
use App\Models\Lead;
use App\Models\Office;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    /** Panel ust bilgi kartlari */
    public function ozet(): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'ozet' => [
                'basvuru_bugun'  => Basvuru::whereDate('created_at', today())->count(),
                'basvuru_yeni'   => Basvuru::where('durum', 'yeni')->count(),
                'basvuru_toplam' => Basvuru::count(),
                'ofis_toplam'    => Office::count(),
                'kullanici'      => User::where('rol', '!=', 'superadmin')->count(),
                'lead_toplam'    => Lead::count(),
                'arama_toplam'   => Call::count(),
                'arama_bugun'    => Call::whereDate('created_at', today())->count(),
            ],
        ]);
    }

    /** Basvuru listesi */
    public function basvurular(Request $request): JsonResponse
    {
        $q = Basvuru::query()->latest();

        if ($request->durum && $request->durum !== 'hepsi') {
            $q->where('durum', $request->durum);
        }
        if ($request->ara) {
            $a = $request->ara;
            $q->where(function ($s) use ($a) {
                $s->where('ad', 'like', "%$a%")
                  ->orWhere('ofis', 'like', "%$a%")
                  ->orWhere('telefon', 'like', "%$a%")
                  ->orWhere('eposta', 'like', "%$a%");
            });
        }

        return response()->json([
            'ok'        => true,
            'basvurular'=> $q->limit(200)->get(),
            'sayilar'   => [
                'hepsi'   => Basvuru::count(),
                'yeni'    => Basvuru::where('durum', 'yeni')->count(),
                'arandi'  => Basvuru::where('durum', 'arandi')->count(),
                'deneme'  => Basvuru::where('durum', 'deneme')->count(),
                'musteri' => Basvuru::where('durum', 'musteri')->count(),
                'kayip'   => Basvuru::where('durum', 'kayip')->count(),
            ],
        ]);
    }

    /** Basvuru durumu / notu guncelle */
    public function basvuruGuncelle(Request $request, Basvuru $basvuru): JsonResponse
    {
        $veri = [];
        if ($request->has('durum')) {
            $veri['durum'] = $request->durum;
        }
        if ($request->has('mesaj')) {
            $veri['mesaj'] = $request->mesaj;
        }
        $basvuru->update($veri);

        return response()->json(['ok' => true, 'basvuru' => $basvuru]);
    }

    /**
     * Basvurudan hesap acar: ofis + yonetici kullanici olusturur,
     * gecici sifreyi geri dondurur.
     */
    public function hesapAc(Request $request, Basvuru $basvuru): JsonResponse
    {
        if (!$basvuru->eposta) {
            return response()->json([
                'ok'   => false,
                'hata' => 'Hesap acmak icin e-posta gerekli. Once basvuruyu duzenleyin.',
            ], 422);
        }

        if (User::where('eposta', $basvuru->eposta)->exists()) {
            return response()->json([
                'ok'   => false,
                'hata' => 'Bu e-posta ile zaten bir hesap var.',
            ], 422);
        }

        $ofis = Office::create([
            'ad'   => $basvuru->ofis ?: ($basvuru->ad . ' Emlak'),
            'plan' => $basvuru->paket ?: 'ofis',
        ]);

        $sifre = Str::upper(Str::random(4)) . random_int(100, 999);

        $kullanici = User::create([
            'office_id'  => $ofis->id,
            'rol'        => 'yonetici',
            'ad'         => $basvuru->ad,
            'telefon'    => $basvuru->telefon,
            'telefon_normal' => $basvuru->telefon,
            'eposta'     => $basvuru->eposta,
            'sifre_hash' => password_hash($sifre, PASSWORD_DEFAULT),
            'api_token'  => Str::random(32),
            'aktif'      => true,
        ]);

        $basvuru->update(['durum' => 'deneme']);

        return response()->json([
            'ok'        => true,
            'mesaj'     => 'Hesap acildi.',
            'ofis'      => $ofis->ad,
            'eposta'    => $kullanici->eposta,
            'sifre'     => $sifre,
        ], 201);
    }

    /** Ofis listesi (kullanici ve musteri sayilariyla) */
    public function ofisler(): JsonResponse
    {
        $ofisler = Office::latest()->limit(200)->get()->map(function ($o) {
            return [
                'id'        => $o->id,
                'ad'        => $o->ad,
                'plan'      => $o->plan,
                'kullanici' => User::where('office_id', $o->id)->count(),
                'lead'      => Lead::where('office_id', $o->id)->count(),
                'arama'     => Call::where('office_id', $o->id)->count(),
                'olusturma' => $o->created_at?->format('d.m.Y'),
            ];
        });

        return response()->json(['ok' => true, 'ofisler' => $ofisler]);
    }

    /** Kullanici listesi */
    public function kullanicilar(Request $request): JsonResponse
    {
        $q = User::where('rol', '!=', 'superadmin')->latest();

        if ($request->ara) {
            $a = $request->ara;
            $q->where(function ($s) use ($a) {
                $s->where('ad', 'like', "%$a%")->orWhere('eposta', 'like', "%$a%");
            });
        }

        $liste = $q->limit(300)->get()->map(function ($u) {
            return [
                'id'      => $u->id,
                'ad'      => $u->ad,
                'eposta'  => $u->eposta,
                'telefon' => $u->telefon,
                'rol'     => $u->rol,
                'aktif'   => (bool) $u->aktif,
                'ofis'    => Office::find($u->office_id)?->ad,
                'lead'    => Lead::where('atanan_user_id', $u->id)->count(),
                'olusturma' => $u->created_at?->format('d.m.Y'),
            ];
        });

        return response()->json(['ok' => true, 'kullanicilar' => $liste]);
    }

    /** Kullaniciyi aktif/pasif yap veya sifresini sifirla */
    public function kullaniciGuncelle(Request $request, User $user): JsonResponse
    {
        if ($user->rol === 'superadmin') {
            return response()->json(['ok' => false, 'hata' => 'Bu hesap degistirilemez.'], 403);
        }

        if ($request->has('aktif')) {
            $user->update(['aktif' => (bool) $request->aktif]);
        }

        if ($request->boolean('sifre_sifirla')) {
            $yeni = Str::upper(Str::random(4)) . random_int(100, 999);
            $user->update(['sifre_hash' => password_hash($yeni, PASSWORD_DEFAULT)]);
            return response()->json(['ok' => true, 'sifre' => $yeni]);
        }

        return response()->json(['ok' => true]);
    }
}
