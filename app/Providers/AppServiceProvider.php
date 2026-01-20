<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use BezhanSalleh\LanguageSwitch\LanguageSwitch;
use Filament\View\PanelsRenderHook;
use Filament\Actions\Action;
use Filament\Tables\Columns\Column as TableColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
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

        // Global Policy Auto-Resolver for Actions
        $autoAuthorize = function ($action, $record = null) {
            $model = $record ? (is_string($record) ? $record : get_class($record)) : null;

            if (!$model) {
                if (method_exists($action, 'getTable') && $action->getTable()) {
                    $model = $action->getTable()->getModel();
                } elseif (method_exists($action, 'getModel')) {
                    $model = $action->getModel();
                }
            }

            if (!$model)
                return true;

            $policy = Gate::getPolicyFor($model);
            if (!$policy)
                return true;

            $ability = $action->getName();

            // Map common action names to policy methods if needed
            $abilityMap = [
                'edit' => 'update',
                'create' => 'create',
                'delete' => 'delete',
            ];

            $ability = $abilityMap[$ability] ?? $ability;

            if (method_exists($policy, $ability)) {
                $reflection = new \ReflectionMethod($policy, $ability);
                $numParams = $reflection->getNumberOfParameters();

                // If policy method expects a model instance (2nd param) but we don't have one,
                // we skip the check to avoid ArgumentCountError.
                if ($numParams > 1 && !$record && !is_object($record)) {
                    return true;
                }

                return auth()->user()?->can($ability, $record ?? $model) ?? true;
            }

            return true;
        };

        Action::configureUsing(fn(Action $action) => $action->visible(fn($record = null) => $autoAuthorize($action, $record)));

        // Global Policy Auto-Resolver for Columns (especially IconColumn with actions)
        TableColumn::configureUsing(function (TableColumn $column) {
            $column->visible(function ($record = null) use ($column) {
                $table = $column->getTable();
                if (!$table)
                    return true;

                $model = $table->getModel();
                if (!$model)
                    return true;

                $policy = Gate::getPolicyFor($model);
                if (!$policy)
                    return true;

                $ability = $column->getName();
                if (method_exists($policy, $ability)) {
                    $reflection = new \ReflectionMethod($policy, $ability);
                    $numParams = $reflection->getNumberOfParameters();

                    if ($numParams > 1 && !$record && !is_object($record)) {
                        return true;
                    }

                    return auth()->user()?->can($ability, $record ?? $model) ?? true;
                }

                return true;
            });
        });

        // Global Policy Auto-Resolver for Table Reordering
        Table::configureUsing(function (Table $table) {
            $table->authorizeReorder(function () use ($table) {
                $model = $table->getModel();
                if (!$model)
                    return true;

                $policy = Gate::getPolicyFor($model);
                if (!$policy || !method_exists($policy, 'reorder'))
                    return true;

                return auth()->user()?->can('reorder', $model) ?? true;
            });
        });

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

        } catch (\Throwable $e) {
            // Settings might not be migrated yet
        }
    }
}
