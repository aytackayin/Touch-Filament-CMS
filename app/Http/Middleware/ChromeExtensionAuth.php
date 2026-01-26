<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

class ChromeExtensionAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-API-KEY');

        if (!$token) {
            return response()->json(['message' => 'API anahtarı bulunamadı.'], 401);
        }

        $user = User::where('chrome_token', $token)->first();

        if (!$user) {
            return response()->json(['message' => 'Geçersiz API anahtarı.'], 401);
        }

        // Security: Check if user has permission to use the extension via Shield
        if (!$user->can('AccessChromeExtension')) {
            return response()->json(['message' => 'Bu eklentiyi kullanma yetkiniz bulunmamaktadır.'], 403);
        }

        auth()->login($user);

        return $next($request);
    }
}
