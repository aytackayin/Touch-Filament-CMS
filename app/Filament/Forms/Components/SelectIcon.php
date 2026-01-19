<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;

use Filament\Forms\Components\Concerns\HasOptions;

class SelectIcon extends Field
{
    use HasOptions;

    protected string $view = 'filament.forms.components.select-icon';

    protected function setUp(): void
    {
        parent::setUp();

        $this->options($this->getIconOptions());
    }

    protected function getIconOptions(): array
    {
        $icons = config('select-icon.icons', []);
        ksort($icons);

        $mapped = [];
        foreach ($icons as $label => $icon) {
            $value = is_object($icon) ? $icon->value : $icon;

            if (!str_starts_with($value, 'heroicon-')) {
                $value = 'heroicon-' . $value;
            }

            $mapped[$value] = "<div style='display: flex; align-items: center; gap: 8px; white-space: nowrap;'> <div style='width: 20px; height: 20px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;'>" . svg($value)->style('width: 20px; height: 20px;')->toHtml() . "</div> <span style='line-height: 1;'>{$label}</span></div>";
        }

        return $mapped;
    }

}

