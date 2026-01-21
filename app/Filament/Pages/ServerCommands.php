<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use UnitEnum;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class ServerCommands extends Page
{
    use HasPageShield;
    protected string $view = 'filament.pages.server-commands';

    public static function getNavigationLabel(): string
    {
        return __('server-commands.navigation_label');
    }

    public function getTitle(): string
    {
        return __('server-commands.title');
    }

    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedCpuChip;

    public static function getNavigationGroup(): ?string
    {
        return __('server-commands.navigation_group');
    }

    protected static ?int $navigationSort = 999;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                Action::make('optimizeClear')
                    ->label(__('server-commands.actions.optimize_clear'))
                    ->icon(Heroicon::Trash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function () {
                        Artisan::call('optimize:clear');
                        Notification::make()->title(__('server-commands.notifications.optimize_cleared'))->success()->send();
                    }),

                Action::make('cacheClear')
                    ->label(__('server-commands.actions.cache_clear'))
                    ->icon(Heroicon::ArchiveBoxXMark)
                    ->color('warning')
                    ->requiresConfirmation()
                    ->action(function () {
                        Artisan::call('cache:clear');
                        Notification::make()->title(__('server-commands.notifications.cache_cleared'))->success()->send();
                    }),

                Action::make('configClear')
                    ->label(__('server-commands.actions.config_clear'))
                    ->icon(Heroicon::Cog)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function () {
                        Artisan::call('config:clear');
                        Notification::make()->title(__('server-commands.notifications.config_cleared'))->success()->send();
                    }),

                Action::make('routeClear')
                    ->label(__('server-commands.actions.route_clear'))
                    ->icon(Heroicon::Map)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function () {
                        Artisan::call('route:clear');
                        Notification::make()->title(__('server-commands.notifications.route_cleared'))->success()->send();
                    }),

                Action::make('viewClear')
                    ->label(__('server-commands.actions.view_clear'))
                    ->icon(Heroicon::EyeSlash)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function () {
                        Artisan::call('view:clear');
                        Notification::make()->title(__('server-commands.notifications.view_cleared'))->success()->send();
                    }),
            ])
                ->label(__('server-commands.categories.cache_management'))
                ->icon(Heroicon::CommandLine)
                ->color('gray')
                ->button()
                ->tooltip(__('server-commands.categories.cache_management')),

            ActionGroup::make([
                Action::make('storageLink')
                    ->label(__('server-commands.actions.storage_link'))
                    ->icon(Heroicon::Link)
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function () {
                        Artisan::call('storage:link');
                        Notification::make()->title(__('server-commands.notifications.storage_linked'))->success()->send();
                    }),

                Action::make('queueRestart')
                    ->label(__('server-commands.actions.queue_restart'))
                    ->icon(Heroicon::ArrowPath)
                    ->color('gray')
                    ->requiresConfirmation()
                    ->action(function () {
                        Artisan::call('queue:restart');
                        Notification::make()->title(__('server-commands.notifications.queue_restarted'))->success()->send();
                    }),

                Action::make('about')
                    ->label(__('server-commands.actions.system_info'))
                    ->icon(Heroicon::InformationCircle)
                    ->color('gray')
                    ->action(function () {
                        Artisan::call('about');
                        $output = Artisan::output();

                        // ANSI renk kodlarını temizle
                        $output = preg_replace('/\x1b\[[0-9;]*m/', '', $output);

                        $lines = explode("\n", $output);
                        $formattedOutput = [];

                        foreach ($lines as $line) {
                            $line = trim($line);
                            if (empty($line))
                                continue;

                            // "Key ................. Value" yapısını yakala
                            if (preg_match('/^(.*?)\s\.+\s*(.*)$/', $line, $matches)) {
                                $key = trim($matches[1]);
                                $value = trim($matches[2]);

                                if (empty($value)) {
                                    // Kategori başlığı (Environment, Cache vb.)
                                    $formattedOutput[] = "<div style='font-weight: bold; color: rgba(var(--primary-600), 1); margin-top: 12px; border-bottom: 1px solid rgba(var(--gray-200), 0.5); padding-bottom: 4px; margin-bottom: 4px; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em;'>{$key}</div>";
                                } else {
                                    // Anahtar : Değer satırı
                                    $formattedOutput[] = "<div style='display: flex; flex-wrap: wrap; justify-content: flex-start; gap: 4px; line-height: 1.5; margin-bottom: 2px;'>
                                        <span style='font-weight: 700; color: rgba(var(--gray-900), 1); opacity: 1;'>{$key} :</span>
                                        <span style='font-size: 0.8rem; color: rgba(var(--gray-500), 1); font-weight: 400; word-break: break-all;'>{$value}</span>
                                    </div>";
                                }
                            }
                        }

                        Notification::make()
                            ->title(__('server-commands.notifications.system_info_title'))
                            ->body(new \Illuminate\Support\HtmlString("<div style='max-height: 60vh; overflow-y: auto; overflow-x: hidden; padding-right: 10px; scrollbar-width: thin; scrollbar-color: rgba(var(--gray-300), 0.5) transparent; word-break: break-word;'> " . implode('', $formattedOutput) . "</div>"))
                            ->send();
                    }),
            ])
                ->label(__('server-commands.categories.app_system'))
                ->icon(Heroicon::WrenchScrewdriver)
                ->color('gray')
                ->button()
                ->tooltip(__('server-commands.categories.app_system')),
        ];
    }
}
