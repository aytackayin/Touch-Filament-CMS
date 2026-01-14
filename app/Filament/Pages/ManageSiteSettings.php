<?php

namespace App\Filament\Pages;

use App\Settings\GeneralSettings;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TagsInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
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
    protected static ?int $navigationSort = 102;

    public static function getNavigationLabel(): string
    {
        return __('Site Ayarları');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                    Tabs::make('Settings')
                        ->tabs([
                                Tab::make('General')
                                    ->label(__('Genel'))
                                    ->icon(Heroicon::Cog)
                                    ->schema([
                                            TextInput::make('site_title')
                                                ->label(__('Site Başlığı'))
                                                ->required(),
                                            Textarea::make('site_description')
                                                ->label(__('Site Açıklaması'))
                                                ->rows(3),
                                            TagsInput::make('site_keywords')
                                                ->label(__('Anahtar Kelimeler')),
                                        ]),
                                Tab::make('Contact & Social')
                                    ->label(__('İletişim & Sosyal'))
                                    ->icon(Heroicon::AtSymbol)
                                    ->schema([
                                            TextInput::make('contact_email')
                                                ->label(__('İletişim E-postası'))
                                                ->email(),
                                        ]),
                                Tab::make('Services')
                                    ->label(__('Servisler'))
                                    ->icon(Heroicon::CpuChip)
                                    ->schema([
                                            TextInput::make('google_maps_api_key')
                                                ->label(__('Google Maps API Anahtarı')),
                                            TextInput::make('google_analytics_id')
                                                ->label(__('Google Analytics ID')),
                                        ]),
                            ])->columnSpan('full'),
                ]);
    }
}
