<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class DeviceController extends Controller
{
    /**
     * Cihaz kaydı / güncelleme.
     * Uygulama açılışında ve push token değiştiğinde çağrılır.
     */
    public function register(Request $request): JsonResponse
    {
        $user = $request->user();

        $request->validate([
            'platform'   => 'required|in:android,ios',
            'push_token' => 'nullable|string|max:500',
            'izinler'    => 'nullable|array',
        ]);

        // Aynı push_token varsa güncelle, yoksa yeni kayıt
        $device = Device::updateOrCreate(
            [
                'user_id'  => $user->id,
                'platform' => $request->platform,
                'push_token' => $request->push_token,
            ],
            [
                'izinler_json' => $request->izinler,
                'son_gorulme'  => now(),
            ]
        );

        return response()->json([
            'ok'        => true,
            'device_id' => $device->id,
        ]);
    }

    /**
     * Cihazın son görülme zamanını günceller (heartbeat).
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $user = $request->user();

        Device::where('user_id', $user->id)
            ->where('platform', $request->platform)
            ->update(['son_gorulme' => now()]);

        return response()->json(['ok' => true]);
    }

    /**
     * Çıkış yaparken cihazın push token'ını temizler.
     */
    public function unregister(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($request->push_token) {
            Device::where('user_id', $user->id)
                ->where('push_token', $request->push_token)
                ->delete();
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Test bildirimi gönderir (cihaz kurulumunu doğrulamak için).
     */
    public function test(Request $request, \App\Services\PushService $push): JsonResponse
    {
        $user = $request->user();
        $push->sendToUser(
            $user,
            'CallPilot Test',
            'Bildirimler çalışıyor! ✓',
            ['tip' => 'test']
        );

        return response()->json(['ok' => true, 'mesaj' => 'Test bildirimi gönderildi']);
    }
}
