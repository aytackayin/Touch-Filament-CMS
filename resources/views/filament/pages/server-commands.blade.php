<x-filament-panels::page>
    <div class="grid gap-4 md:grid-cols-2">
        <x-filament::section heading="Artisan Komutları">
            <div class="space-y-4">
                <div class="flex flex-col border-b border-gray-100 dark:border-gray-800 pb-2">
                    <strong class="text-sm font-bold">Optimize Clear</strong>
                    <span class="text-xs text-gray-500">Tüm önbelleğe alınmış dosyaları (config, routes, views vb.)
                        temizler.</span>
                </div>
                <div class="flex flex-col border-b border-gray-100 dark:border-gray-800 pb-2">
                    <strong class="text-sm font-bold">Cache Clear</strong>
                    <span class="text-xs text-gray-500">Uygulama önbelleğini (Cache) temizler.</span>
                </div>
                <div class="flex flex-col border-b border-gray-100 dark:border-gray-800 pb-2">
                    <strong class="text-sm font-bold">Config Clear</strong>
                    <span class="text-xs text-gray-500">Yapılandırma dosyası önbelleğini temizler.</span>
                </div>
                <div class="flex flex-col border-b border-gray-100 dark:border-gray-800 pb-2">
                    <strong class="text-sm font-bold">Route Clear</strong>
                    <span class="text-xs text-gray-500">Rota önbelleğini temizler.</span>
                </div>
                <div class="flex flex-col border-b border-gray-100 dark:border-gray-800 pb-2">
                    <strong class="text-sm font-bold">View Clear</strong>
                    <span class="text-xs text-gray-500">Derlenmiş görünüm (Blade) dosyalarını temizler.</span>
                </div>
                <div class="flex flex-col">
                    <strong class="text-sm font-bold">Storage Link</strong>
                    <span class="text-xs text-gray-500">Dosya erişimi için sembolik link (public storage)
                        oluşturur.</span>
                </div>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>