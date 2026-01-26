<?php

namespace App\Traits;

use App\Models\UserPreference;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;

trait HasTableSettings
{
    public array $visibleColumns = [];
    public $userPreferredPerPage = null;

    public function mountHasTableSettings(): void
    {
        $this->loadTableSettings();
    }

    abstract protected function getTableSettingsKey(): string;
    abstract protected function getDefaultVisibleColumns(): array;
    abstract protected function getTableColumnOptions(): array;

    protected function getDefaultPerPage(): int|string
    {
        return $this->getTable()->getDefaultPaginationPageOption() ?? 10;
    }

    protected function getPerPageOptions(): array
    {
        return $this->getTable()->getPaginationPageOptions() ?? [5, 10, 25, 50];
    }

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
            $savedColumns = $settings['visible_columns'] ?? $this->getDefaultVisibleColumns();

            // Sadece şu anki seçeneklerde (options) var olan anahtarları kabul et.
            // Bu sayede kodda yapılan sütun değişiklikleri validation hatalarına yol açmaz.
            $availableOptions = array_keys($this->getTableColumnOptions());
            $this->visibleColumns = array_intersect($savedColumns, $availableOptions);

            if (isset($settings['view_type']) && property_exists($this, 'view_type')) {
                $this->view_type = $settings['view_type'];
            }

            $this->userPreferredPerPage = $settings['per_page'] ?? $this->getDefaultPerPage();
        } else {
            $this->visibleColumns = $this->getDefaultVisibleColumns();
            $this->userPreferredPerPage = $this->getDefaultPerPage();
        }

        // Filament'in iç değişkenini, dışarıdan (URL/Session) bir değer gelmemişse güncelle.
        if (property_exists($this, 'tableRecordsPerPage')) {
            $this->tableRecordsPerPage = $this->userPreferredPerPage;
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
            'per_page' => $data['per_page'] ?? $this->getDefaultPerPage(),
        ];

        // 1. Veritabanına Kaydet
        UserPreference::setTableSettings($this->getTableSettingsKey(), $saveData);

        // 2. KRİTİK: Filament'in seans belleğini zorla siliyoruz (v4 uyumlu).
        $sessionKey = "tables." . md5(static::class) . "_columns";
        session()->forget($sessionKey);
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
                    ->label(__('table_settings.view_type'))
                    ->options([
                        'list' => __('file_manager.label.list_view'),
                        'grid' => __('file_manager.label.grid_view'),
                    ])
                    ->default(property_exists($this, 'view_type') ? $this->view_type : 'list')
                    ->inline()
                    ->hidden(!property_exists($this, 'view_type')),
                Radio::make('per_page')
                    ->label(__('table_settings.per_page'))
                    ->options(function () {
                        $options = $this->getPerPageOptions();
                        $labels = array_map(fn($opt) => $opt === 'all' ? (__('table_settings.all') ?? 'Hepsi') : $opt, $options);
                        return array_combine($options, $labels);
                    })
                    ->default($this->userPreferredPerPage)
                    ->inline(),
                CheckboxList::make('visible_columns')
                    ->label(__('table_settings.columns'))
                    ->options($this->getTableColumnOptions())
                    ->default($this->visibleColumns)
                    ->columns(2),
            ])
            ->action(fn(array $data) => $this->saveTableSettings($data));
    }
}
