<?php

namespace App\Http\Middleware;

use App\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class SetLanguage
{
    public function handle(Request $request, Closure $next)
    {
        $language = Language::where('is_default', true)
            ->where('is_active', true)
            ->first();

        if ($language) {
            // Laravel locale
            App::setLocale($language->code);

            // 🔴 KRİTİK KISIM
            Config::set('lang.direction', $language->direction);
            Config::set('lang.charset', $language->charset);
        }

        return $next($request);
    }
}
