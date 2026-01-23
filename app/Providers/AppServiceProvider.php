<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Filament\View\PanelsRenderHook;
use App\Models\Blog;
use App\Models\BlogCategory;
use App\Models\Language;
use App\Observers\BlogObserver;
use App\Observers\BlogCategoryObserver;
use App\Settings\GeneralSettings;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Blog::observe(BlogObserver::class);
        BlogCategory::observe(BlogCategoryObserver::class);

        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $languages = Language::where('is_active', 1)->get();

            $switch
                ->renderHook(PanelsRenderHook::USER_MENU_BEFORE)
                ->circular()
                ->locales(
                    $languages
                        ->pluck('code')
                        ->map(fn($code) => strtolower(explode('_', $code)[0]))
                        ->unique()
                        ->values()
                        ->toArray()
                )
                ->labels(
                    $languages
                        ->mapWithKeys(fn($lang) => [
                            strtolower(explode('_', $lang->code)[0])
                            => "{$lang->name}"
                        ])
                        ->toArray()
                );
        });


        try {
            /** @var GeneralSettings $settings */
            $settings = app(GeneralSettings::class);

            config([
                'site.title' => $settings->site_title,
                'site.description' => $settings->site_description,
                'site.keywords' => implode(', ', $settings->site_keywords ?? []),
                'site.contact_email' => $settings->contact_email,
                'services.google_maps.key' => $settings->google_maps_api_key,
                'services.google_analytics.id' => $settings->google_analytics_id,
            ]);

            // Dynamic Attachments Configuration
            $attachPath = $settings->attachments_path ?: 'attachments';
            config([
                'filesystems.disks.attachments.root' => storage_path("app/public/{$attachPath}"),
                'filesystems.disks.attachments.url' => rtrim(env('APP_URL'), '/') . "/{$attachPath}",
                'filesystems.links' => [
                    public_path($attachPath) => storage_path("app/public/{$attachPath}"),
                ],
            ]);

            // Optional: Override mail from address if set
            if ($settings->contact_email) {
                config(['mail.from.address' => $settings->contact_email]);
            }

            // Livewire Force HTTPS / URL Fix
            if (str_starts_with(config('app.url'), 'https')) {
                \Illuminate\Support\Facades\URL::forceScheme('https');
            }

            config(['livewire.asset_base_url' => config('app.url')]);
            config(['livewire.app_url' => config('app.url')]);

        } catch (\Throwable $e) {
            // Settings might not be migrated yet
        }
    }
}
