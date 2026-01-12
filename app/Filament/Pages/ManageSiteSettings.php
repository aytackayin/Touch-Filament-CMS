<?php

namespace App\Filament\Pages;

use App\Settings\GeneralSettings;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TagsInput;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Pages\SettingsPage;
use Filament\Support\Icons\Heroicon;
use BackedEnum;

class ManageSiteSettings extends SettingsPage
{
    protected static string $settings = GeneralSettings::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    public static function getNavigationGroup(): ?string
    {
        return __('Genel Ayarlar');
    }

    public static function getNavigationLabel(): string
    {
        return __('Site Ayarları');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('General')
                    ->schema([
                        TextInput::make('site_title')
                            ->label('Site Title')
                            ->required(),
                        Textarea::make('site_description')
                            ->label('Site Description')
                            ->rows(3),
                        TagsInput::make('site_keywords')
                            ->label('Keywords'),
                    ]),
                Section::make('Contact & Social')
                    ->schema([
                        TextInput::make('contact_email')
                            ->label('Contact Email')
                            ->email(),
                    ]),
                Section::make('Services')
                    ->schema([
                        TextInput::make('google_maps_api_key')
                            ->label('Google Maps API Key'),
                        TextInput::make('google_analytics_id')
                            ->label('Google Analytics ID'),
                    ]),
            ]);
    }
}
