<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use App\Filament\Exports\UserExporter;
use App\Filament\Imports\UserImporter;
use Filament\Support\Icons\Heroicon;
use Filament\Resources\Pages\ListRecords;
use App\Traits\HasTableSettings;

class ListUsers extends ListRecords
{
    use HasTableSettings;
    protected static string $resource = UserResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountHasTableSettings();
    }

    protected function getTableSettingsKey(): string
    {
        return 'user_list';
    }

    protected function getDefaultVisibleColumns(): array
    {
        return ['email', 'roles', 'created_at'];
    }

    protected function getTableColumnOptions(): array
    {
        return [
            'avatar_url' => __('filament-breezy::default.fields.avatar'),
            'email' => __('user.label.email'),
            'roles' => __('user.label.roles'),
            'phone' => __('user.label.phone'),
            'created_at' => __('user.label.created_at'),
            'updated_at' => __('user.label.updated_at'),
        ];
    }

    protected function getHeaderActions(): array
    {
        $isSuperAdmin = auth()->user()?->hasRole('super_admin');

        return [
            ExportAction::make()
                ->label('')
                ->icon(Heroicon::OutlinedArrowUpOnSquareStack)
                ->tooltip(__('filament-actions::export.modal.actions.export.label'))
                ->color('gray')
                ->size('xs')
                ->exporter(UserExporter::class)
                ->visible($isSuperAdmin),
            ImportAction::make()
                ->label('')
                ->icon(Heroicon::OutlinedArrowDownOnSquareStack)
                ->tooltip(__('filament-actions::import.modal.actions.import.label'))
                ->color('gray')
                ->size('xs')
                ->importer(UserImporter::class)
                ->visible($isSuperAdmin),
            CreateAction::make()
                ->label('')
                ->tooltip(__('filament-actions::create.single.modal.actions.create.label'))
                ->color('success')
                ->size('xs')
                ->icon('heroicon-m-user-plus'),
            $this->getTableSettingsAction(),
        ];
    }
}
