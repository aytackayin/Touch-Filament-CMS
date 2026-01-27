<?php

namespace App\Providers;

use Filament\Actions\Action;
use Filament\Tables\Columns\Column as TableColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use ReflectionMethod;

class FilamentAuthorizationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
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

            if (!$model) {
                return true;
            }

            $policy = Gate::getPolicyFor($model);
            if (!$policy) {
                return true;
            }

            $ability = $action->getName();

            // Map common action names to policy methods if needed
            $abilityMap = [
                'edit' => 'update',
                'create' => 'create',
                'delete' => 'delete',
            ];

            $ability = $abilityMap[$ability] ?? $ability;

            if (method_exists($policy, $ability)) {
                $reflection = new ReflectionMethod($policy, $ability);
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
                if (!$table) {
                    return true;
                }

                $model = $table->getModel();
                if (!$model) {
                    return true;
                }

                $policy = Gate::getPolicyFor($model);
                if (!$policy) {
                    return true;
                }

                $ability = $column->getName();
                if (method_exists($policy, $ability)) {
                    $reflection = new ReflectionMethod($policy, $ability);
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
                if (!$model) {
                    return true;
                }

                $policy = Gate::getPolicyFor($model);
                if (!$policy || !method_exists($policy, 'reorder')) {
                    return true;
                }

                return auth()->user()?->can('reorder', $model) ?? true;
            });
        });
    }
}
