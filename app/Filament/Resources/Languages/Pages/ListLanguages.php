<?php

namespace App\Filament\Resources\Languages\Pages;

use App\Filament\Resources\Languages\LanguageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Traits\HasTableSettings;

class ListLanguages extends ListRecords
{
    use HasTableSettings;
    protected static string $resource = LanguageResource::class;

    public function mount(): void
    {
        parent::mount();
        $this->mountHasTableSettings();
    }

    protected function getTableSettingsKey(): string
    {
        return 'language_list';
    }

    protected function getDefaultVisibleColumns(): array
    {
        return ['code', 'direction', 'is_default', 'is_active'];
    }

    protected function getTableColumnOptions(): array
    {
        return [
            'code' => __('language.label.code'),
            'charset' => __('language.label.charset'),
            'direction' => __('language.label.direction'),
            'is_default' => __('language.label.is_default'),
            'is_active' => __('language.label.is_active'),
            'created_at' => __('language.label.created_at'),
            'updated_at' => __('language.label.updated_at'),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('')
                ->tooltip(__('filament-actions::create.single.modal.actions.create.label'))
                ->color('success')
                ->size('xs')
                ->icon('heroicon-m-language'),
            $this->getTableSettingsAction(),
        ];
    }
}
