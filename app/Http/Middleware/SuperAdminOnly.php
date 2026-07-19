<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminOnly
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || $user->rol !== 'superadmin') {
            return response()->json([
                'ok'   => false,
                'hata' => 'Bu alana erisim yetkiniz yok.',
            ], 403);
        }

        return $next($request);
    }
}
