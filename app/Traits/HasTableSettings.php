<?php

namespace App\Traits;

use App\Models\UserPreference;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Illuminate\Support\Facades\Cookie;

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

    protected function getTableSettingsCookieName(): string
    {
        return 'table_settings_' . $this->getTableSettingsKey();
    }

    protected function getTableSettingsFormSchema(): array
    {
        return [
            CheckboxList::make('visible_columns')
                ->label(__('table_settings.columns'))
                ->options($this->getTableColumnOptions())
                ->default($this->visibleColumns)
                ->required()
                ->columns(2),
        ];
    }

    public function loadTableSettings(): void
    {
        // 1. Cookie (Priority: High)
        $cookieVal = request()->cookie($this->getTableSettingsCookieName());
        if ($cookieVal) {
            $decoded = json_decode($cookieVal, true);
            if (is_array($decoded)) {
                $this->applySettings($decoded);
                return;
            }
        }

        // 2. DB (User Preference) (Priority: Medium)
        if (auth()->check()) {
            $dbVal = UserPreference::getTableSettings($this->getTableSettingsKey());
            if ($dbVal && is_array($dbVal)) {
                $this->applySettings($dbVal);
                // Sync to cookie for consistency
                Cookie::queue($this->getTableSettingsCookieName(), json_encode($dbVal), 60 * 24 * 365);
                return;
            }
        }

        // 3. Default (Priority: Low)
        $this->applySettings(['visible_columns' => $this->getDefaultVisibleColumns()]);
    }

    protected function applySettings(array $settings): void
    {
        $this->visibleColumns = $settings['visible_columns'] ?? [];
    }

    public function saveTableSettings(array $data): void
    {
        $this->applySettings($data);

        // Save to DB
        if (auth()->check()) {
            UserPreference::setTableSettings($this->getTableSettingsKey(), $data);
        }

        // Save to Cookie
        Cookie::queue($this->getTableSettingsCookieName(), json_encode($data), 60 * 24 * 365);

        $this->dispatch('table-settings-updated');
    }

    public function getTableSettingsAction(): Action
    {
        return Action::make('tableSettings')
            ->label(__('table_settings.label'))
            ->icon('heroicon-o-cog-6-tooth')
            ->modalHeading(__('table_settings.modal_heading'))
            ->modalSubmitActionLabel(__('table_settings.save'))
            ->form($this->getTableSettingsFormSchema())
            ->action(function (array $data) {
                $this->saveTableSettings($data);
                return redirect(request()->header('Referer'));
            });
    }
}
