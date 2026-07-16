<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = null;

        $auth = $request->header('Authorization', '');
        if (str_starts_with($auth, 'Bearer ')) {
            $token = substr($auth, 7);
        }

        if (!$token) {
            return response()->json(['ok' => false, 'hata' => 'Token gerekli'], 401);
        }

        $user = User::where('api_token', $token)->where('aktif', true)->first();

        if (!$user) {
            return response()->json(['ok' => false, 'hata' => 'Geçersiz token'], 401);
        }

        $request->setUserResolver(fn() => $user);

        return $next($request);
    }
}
