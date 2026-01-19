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
    }
}

