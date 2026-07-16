<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reminder;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReminderController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $reminders = Reminder::whereHas('lead', fn($q) => $q->where('office_id', $user->office_id))
            ->where('user_id', $user->id)
            ->where('durum', 'bekliyor')
            ->with('lead:id,ad_soyad,telefon')
            ->orderBy('zaman')
            ->get()
            ->map(fn($r) => array_merge($r->toArray(), ['gecikti' => now()->gt($r->zaman)]));

        return response()->json(['ok' => true, 'hatirlatmalar' => $reminders]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        $request->validate(['lead_id' => 'required|integer', 'zaman' => 'required|date']);

        $reminder = Reminder::create([
            'lead_id' => $request->lead_id,
            'user_id' => $user->id,
            'zaman'   => $request->zaman,
            'not_text'=> $request->not,
        ]);

        return response()->json(['ok' => true, 'hatirlatma' => $reminder], 201);
    }

    public function update(Request $request, Reminder $reminder): JsonResponse
    {
        $request->validate(['durum' => 'required|in:bekliyor,tamamlandi,ertelendi']);
        $reminder->update(['durum' => $request->durum]);
        return response()->json(['ok' => true]);
    }
}
