# Filament SelectIcon Plugin

A custom Filament form component that provides a beautiful, grid-based icon picker with a built-in search functionality and custom tooltips.

![SelectIcon Preview](https://raw.githubusercontent.com/aytackayin/filament-select-icon/main/preview.png)

## Features

- 🚀 **Grid Layout**: Displays icons in a clean, responsive grid.
- 🔍 **Searchable**: Built-in search functionality to quickly find icons.
- 🎨 **Custom Tooltips**: Modern, rounded, and shadowed tooltips that appear on hover.
- 🌓 **Dark Mode Support**: Fully compatible with Filament's light and dark themes.
- ⚙️ **Configurable**: Easily customize the list of available icons via a config file.
- 📦 **No External Dependencies**: Self-contained CSS and Alpine.js logic.

## Installation

### 1. Copy the Component
Copy the following files to your project:

- `app/Filament/Forms/Components/SelectIcon.php`
- `resources/views/filament/forms/components/select-icon.blade.php`

### 2. Configuration
Create a `config/select-icon.php` file and add your supported Heroicons:

```php
<?php

use Filament\Support\Icons\Heroicon;

return [
    'icons' => [
        'Academic Cap' => Heroicon::OutlinedAcademicCap,
        'Home' => Heroicon::OutlinedHome,
        // Add more icons here...
    ]
];
```

## Usage

Simply use the `SelectIcon` component in your Filament form schema:

```php
use App\Filament\Forms\Components\SelectIcon;

public function form(Form $form): Form
{
    return $form
        ->schema([
            SelectIcon::make('icon')
                ->label('Select an Icon'),
        ]);
}
```

## Customization

### Tooltip Width
You can adjust the tooltip width or behavior in the `select-icon.blade.php` file within the `<style>` block:

```css
.select-icon-tooltip {
    width: 100px !important; /* Fixed width */
    white-space: normal !important; /* Allow wrapping */
}
```

## Credits

Developed by [Aytaç Kayın](https://github.com/aytackayin/).

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
