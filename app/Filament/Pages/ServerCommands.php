<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Filament\Support\Icons\Heroicon;
use BackedEnum;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class ServerCommands extends Page
{
    use HasPageShield;
    protected string $view = 'filament.pages.server-commands';

    protected static ?string $navigationLabel = 'Server Commands';
    protected static BackedEnum|string|null $navigationIcon = Heroicon::OutlinedCpuChip;
    protected static \UnitEnum|string|null $navigationGroup = 'System';
    protected static ?int $navigationSort = 999;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('optimizeClear')
                ->label('Optimize Clear')
                ->icon(Heroicon::Trash)
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    Artisan::call('optimize:clear');
                    Notification::make()->title('Sistem optimizasyonu temizlendi')->success()->send();
                }),

            Action::make('cacheClear')
                ->label('Cache Clear')
                ->icon(Heroicon::ArchiveBoxXMark)
                ->color('warning')
                ->requiresConfirmation()
                ->action(function () {
                    Artisan::call('cache:clear');
                    Notification::make()->title('Uygulama önbelleği temizlendi')->success()->send();
                }),

            Action::make('configClear')
                ->label('Config Clear')
                ->icon(Heroicon::Cog)
                ->color('gray')
                ->requiresConfirmation()
                ->action(function () {
                    Artisan::call('config:clear');
                    Notification::make()->title('Yapılandırma önbelleği temizlendi')->success()->send();
                }),

            Action::make('routeClear')
                ->label('Route Clear')
                ->icon(Heroicon::Map)
                ->color('gray')
                ->requiresConfirmation()
                ->action(function () {
                    Artisan::call('route:clear');
                    Notification::make()->title('Rota önbelleği temizlendi')->success()->send();
                }),

            Action::make('viewClear')
                ->label('View Clear')
                ->icon(Heroicon::EyeSlash)
                ->color('gray')
                ->requiresConfirmation()
                ->action(function () {
                    Artisan::call('view:clear');
                    Notification::make()->title('Görünüm önbelleği temizlendi')->success()->send();
                }),

            Action::make('storageLink')
                ->label('Storage Link')
                ->icon(Heroicon::Link)
                ->color('success')
                ->requiresConfirmation()
                ->action(function () {
                    Artisan::call('storage:link');
                    Notification::make()->title('Sembolik linkler oluşturuldu')->success()->send();
                }),
        ];
    }
}
