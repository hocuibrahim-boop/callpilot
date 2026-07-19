<?php

namespace App\Http\Controllers\Api;

use App\Support\Phone;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadNote;
use App\Models\StageHistory;
use App\Models\Reminder;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class LeadController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = Lead::where('office_id', $user->office_id)
            ->with(['atananUser:id,ad', 'calls' => fn($q) => $q->latest()->limit(1)]);

        if ($request->asama) {
            $query->where('asama', $request->asama);
        }
        if ($request->q) {
            $q = $request->q;
            $query->where(function ($sq) use ($q) {
                $sq->where('ad_soyad', 'like', "%$q%")
                   ->orWhere('telefon', 'like', "%$q%")
                   ->orWhere('bolge', 'like', "%$q%");
            });
        }

        $leads = $query->latest()->paginate(30);
        return response()->json(['ok' => true, 'data' => $leads]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $dogrulama = validator($request->all(), [
            'ad_soyad'   => 'required|string|max:191',
            'talep_tipi' => 'required|string|max:40',
            'bolge'      => 'required|string|max:120',
        ]);

        if ($dogrulama->fails()) {
            return response()->json([
                'ok'   => false,
                'hata' => $dogrulama->errors()->first(),
            ], 422);
        }

        $telefon = $request->telefon ?? null;
        $telNorm = $telefon ? Phone::normalize($telefon) : null;

        try {
            $lead = Lead::create([
                'office_id'       => $user->office_id,
                'atanan_user_id'  => $user->id,
                'ad_soyad'        => $request->ad_soyad,
                'telefon'         => $telefon,
                'telefon_normal'  => $telNorm,
                'talep_tipi'      => $request->talep_tipi,
                'gayrimenkul_turu'=> $request->gayrimenkul_turu,
                'bolge'           => $request->bolge,
                'butce'           => $request->butce,
                'oncelik'         => $request->oncelik ?? 'orta',
                'asama'           => 'yeni',
                'kaynak'          => $request->kaynak ?? 'manuel',
            ]);

            // Not varsa ekle
            if ($request->not) {
                LeadNote::create([
                    'lead_id' => $lead->id,
                    'user_id' => $user->id,
                    'metin'   => $request->not,
                ]);
            }

            // Hatirlatma varsa ekle
            if ($request->hatirlat) {
                Reminder::create([
                    'lead_id' => $lead->id,
                    'user_id' => $user->id,
                    'zaman'   => $request->hatirlat,
                ]);
            }

            // Bu numaranin bekleyen aramalarini karta bagla
            if ($telNorm) {
                \App\Models\Call::where('office_id', $user->office_id)
                    ->where('telefon_normal', $telNorm)
                    ->whereNull('lead_id')
                    ->update([
                        'lead_id'      => $lead->id,
                        'kayit_durumu' => 'karta_islendi',
                    ]);
            }

            return response()->json(['ok' => true, 'lead' => $lead], 201);

        } catch (\Throwable $e) {
            \Log::error('Lead olusturma hatasi', [
                'mesaj' => $e->getMessage(),
                'satir' => $e->getFile() . ':' . $e->getLine(),
            ]);
            return response()->json([
                'ok'   => false,
                'hata' => 'Kayit yapilamadi: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, Lead $lead): JsonResponse
    {
        $user = $request->user();
        if ($lead->office_id !== $user->office_id) {
            return response()->json(['ok' => false, 'hata' => 'Yetkisiz'], 403);
        }

        $timeline = collect()
            ->merge($lead->calls->map(fn($c) => ['tip' => 'arama', 'zaman' => $c->created_at, 'veri' => $c]))
            ->merge($lead->notes->map(fn($n) => ['tip' => 'not', 'zaman' => $n->created_at, 'veri' => $n]))
            ->merge($lead->stageHistory->map(fn($s) => ['tip' => 'asama', 'zaman' => $s->created_at, 'veri' => $s]))
            ->sortByDesc('zaman')->values();

        return response()->json([
            'ok'       => true,
            'lead'     => $lead->load('atananUser:id,ad'),
            'timeline' => $timeline,
            'bekleyen_hatirlatmalar' => $lead->reminders()->where('durum', 'bekliyor')->get(),
        ]);
    }

    public function updateAsama(Request $request, Lead $lead): JsonResponse
    {
        $user = $request->user();
        if ($lead->office_id !== $user->office_id) {
            return response()->json(['ok' => false, 'hata' => 'Yetkisiz'], 403);
        }

        $request->validate(['asama' => 'required|string']);
        $eskiAsama = $lead->asama;

        $lead->update(['asama' => $request->asama]);

        StageHistory::create([
            'lead_id'    => $lead->id,
            'eski_asama' => $eskiAsama,
            'yeni_asama' => $request->asama,
            'user_id'    => $user->id,
        ]);

        return response()->json(['ok' => true]);
    }

    public function addNote(Request $request, Lead $lead): JsonResponse
    {
        $user = $request->user();
        if ($lead->office_id !== $user->office_id) {
            return response()->json(['ok' => false, 'hata' => 'Yetkisiz'], 403);
        }

        $note = LeadNote::create([
            'lead_id'  => $lead->id,
            'user_id'  => $user->id,
            'metin'    => $request->metin,
            'ses_url'  => $request->ses_url,
        ]);

        return response()->json(['ok' => true, 'not' => $note], 201);
    }

    public function whatsapp(Request $request, Lead $lead): JsonResponse
    {
        $user = $request->user();
        if ($lead->office_id !== $user->office_id) {
            return response()->json(['ok' => false, 'hata' => 'Yetkisiz'], 403);
        }

        $mesaj = $request->mesaj ?? '';
        $link  = 'https://wa.me/' . ltrim($lead->telefon_normal, '+') . '?text=' . urlencode($mesaj);

        Activity::create([
            'office_id' => $user->office_id,
            'user_id'   => $user->id,
            'lead_id'   => $lead->id,
            'tip'       => 'whatsapp',
            'veri_json' => ['mesaj' => $mesaj, 'link' => $link],
        ]);

        return response()->json(['ok' => true, 'link' => $link]);
    }
}
