<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Support\Facades\FilamentView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Symfony\Component\HttpFoundation\Response;

class FilamentIframeMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Eğer URL'de iframe parametresi varsa (örn: ?iframe=1)
        if ($request->has('iframe')) {

            // 1. Filament'in tüm layout bileşenlerini gizlemek için CSS enjekte et
            FilamentView::registerRenderHook(
                'panels::head.end',
                fn(): string => Blade::render('
        <style>
            .fi-sidebar, .fi-topbar, .fi-sidebar-header, .fi-main-ctn > aside, .fi-topbar-placeholder { display: none !important; }
            .fi-main-ctn { margin-left: 0 !important; padding-left: 0 !important; }
            main { margin-top: 0 !important; padding-top: 0 !important; }
        </style>
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                // Sayfadaki tüm linkleri bul ve sonuna iframe=1 ekle
                document.addEventListener("click", function(e) {
                    let anchor = e.target.closest("a");
                    if (anchor && anchor.href && !anchor.href.includes("iframe=1") && anchor.href.includes(window.location.origin)) {
                        let url = new URL(anchor.href);
                        url.searchParams.set("iframe", "1");
                        anchor.href = url.toString();
                    }
                }, true);
            });
        </script>
    ')
            );

            // 2. Linklerin (Klasör tıklamaları, düzenle vs.) iframe içinde kalmasını sağla
            // Mevcut URL'deki iframe=1 parametresini tüm linklere otomatik ekler
            app('url')->defaults(['iframe' => 1]);
        }

        return $next($request);
    }
}