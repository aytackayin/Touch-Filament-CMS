<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;

use Filament\View\PanelsRenderHook;

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
        \App\Models\Blog::observe(\App\Observers\BlogObserver::class);
        \App\Models\BlogCategory::observe(\App\Observers\BlogCategoryObserver::class);

        LanguageSwitch::configureUsing(function (LanguageSwitch $switch) {
            $languages = \App\Models\Language::where('is_active', 1)->get();

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
            /** @var \App\Settings\GeneralSettings $settings */
            $settings = app(\App\Settings\GeneralSettings::class);

            config([
                'site.title' => $settings->site_title,
                'site.description' => $settings->site_description,
                'site.keywords' => implode(', ', $settings->site_keywords ?? []),
                'site.contact_email' => $settings->contact_email,
                'services.google_maps.key' => $settings->google_maps_api_key,
                'services.google_analytics.id' => $settings->google_analytics_id,
            ]);

            // Optional: Override mail from address if set
            if ($settings->contact_email) {
                config(['mail.from.address' => $settings->contact_email]);
            }

        } catch (\Throwable $e) {
            // Settings might not be migrated yet
        }
    }
}
