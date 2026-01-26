<?php

namespace App\Traits;

use App\Models\UserPreference;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;

trait HasTableSettings
{
    public array $visibleColumns = [];

    public function mountHasTableSettings(): void
    {
        $this->loadTableSettings();
    }

    abstract protected function getTableSettingsKey(): string;
    abstract protected function getDefaultVisibleColumns(): array;
    abstract protected function getTableColumnOptions(): array;

    /**
     * Filament'in kendi iç kalıcılığını KAPATIYORUZ.
     */
    public function shouldPersistTableColumnDisplayStates(): bool
    {
        return false;
    }

    public function loadTableSettings(): void
    {
        if (!auth()->check()) {
            $this->visibleColumns = $this->getDefaultVisibleColumns();
            return;
        }

        $settings = UserPreference::getTableSettings($this->getTableSettingsKey());

        if ($settings && is_array($settings)) {
            $this->visibleColumns = $settings['visible_columns'] ?? $this->getDefaultVisibleColumns();

            if (isset($settings['view_type']) && property_exists($this, 'view_type')) {
                $this->view_type = $settings['view_type'];
            }
        } else {
            $this->visibleColumns = $this->getDefaultVisibleColumns();
        }
    }

    public function saveTableSettings(array $data): void
    {
        if (!auth()->check())
            return;

        $newVisibleColumns = $data['visible_columns'] ?? [];

        $saveData = [
            'visible_columns' => $newVisibleColumns,
            'view_type' => $data['view_type'] ?? (property_exists($this, 'view_type') ? $this->view_type : 'list'),
        ];

        // 1. Veritabanına Kaydet
        UserPreference::setTableSettings($this->getTableSettingsKey(), $saveData);

        // 2. KRİTİK: Filament'in seans belleğini zorla siliyoruz.
        // Filament v4'te seans anahtarı "tables.{md5(class)}_columns" formatındadır.
        $sessionKey = "tables." . md5(static::class) . "_columns";
        session()->forget($sessionKey);

        // Ek olarak varsa eski formatları da temizleyelim
        session()->forget("tables." . static::class . ".toggled_table_columns");

        // 3. State'i güncelle
        $this->visibleColumns = $newVisibleColumns;

        // 4. Sayfayı yenileyerek temiz bir başlangıç yapıyoruz.
        $this->redirect(request()->header('Referer'));
    }

    public function getTableSettingsAction(): Action
    {
        return Action::make('tableSettings')
            ->label(__('table_settings.label'))
            ->hiddenLabel()
            ->tooltip(__('table_settings.label'))
            ->icon('heroicon-o-cog-6-tooth')
            ->color('gray')
            ->size('xs')
            ->modalHeading(__('table_settings.modal_heading'))
            ->modalSubmitActionLabel(__('table_settings.save'))
            ->form(fn() => [
                Radio::make('view_type')
                    ->label(__('blog.label.default_view'))
                    ->options([
                        'list' => __('file_manager.label.list_view'),
                        'grid' => __('file_manager.label.grid_view'),
                    ])
                    ->default(property_exists($this, 'view_type') ? $this->view_type : 'list')
                    ->inline()
                    ->hidden(!property_exists($this, 'view_type')),
                CheckboxList::make('visible_columns')
                    ->label(__('table_settings.columns'))
                    ->options($this->getTableColumnOptions())
                    ->default($this->visibleColumns)

                    ->columns(2),
            ])
            ->action(fn(array $data) => $this->saveTableSettings($data));
    }
}
