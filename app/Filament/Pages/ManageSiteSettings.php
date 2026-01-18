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
                            ->icon(Heroicon::OutlinedCog6Tooth)
                            ->schema([
                                TextInput::make('site_title')
                                    ->label(__('Site Başlığı'))
                                    ->required(),
                                Textarea::make('site_description')
                                    ->label(__('Site Açıklaması'))
                                    ->rows(3),
                                TagsInput::make('site_keywords')
                                    ->label(__('Anahtar Kelimeler')),
                                TextInput::make('attachments_path')
                                    ->label(__('Dosya Yolu (Disk Path)'))
                                    ->helperText('DİKKAT: Bu ayarı değiştirmek physical klasör adını değiştirir ve sembolik linkleri yeniden oluşturur.')
                                    ->default('attachments')
                                    ->required()
                                    ->extraInputAttributes([
                                        'style' => 'text-transform: lowercase',
                                        'x-on:input' => "\$el.value = \$el.value.toLowerCase().replace(/[çğışıöü]/g, c => ({'ç':'c','ğ':'g','ı':'i','ş':'s','ö':'o','ü':'u'}[c])).replace(/\s+/g, '-').replace(/[^a-z0-9\-_]/g, '').replace(/-+/g, '-'); \$el.dispatchEvent(new Event('input'))",
                                    ])
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn($state, callable $set) => $set('attachments_path', \Illuminate\Support\Str::slug($state))),
                            ]),
                        Tab::make('Dynamic Settings')
                            ->label(__('Dinamik Ayarlar'))
                            ->icon(Heroicon::OutlinedRectangleStack)
                            ->schema([
                                \Filament\Forms\Components\Repeater::make('custom_settings')
                                    ->label(__('Ayar Grupları'))
                                    ->schema([
                                        TextInput::make('tab_name')
                                            ->label('Grup (Sekme) Adı')
                                            ->required()
                                            ->columnSpanFull(),
                                        \Filament\Forms\Components\Repeater::make('fields')
                                            ->label('Bu Gruba Ait Ayarlar')
                                            ->schema([
                                                TextInput::make('label')
                                                    ->label('Ayar Adı')
                                                    ->required(),
                                                TextInput::make('value')
                                                    ->label('Değeri'),
                                            ])
                                            ->columns(2)
                                            ->collapsible()
                                            ->itemLabel(fn(array $state): ?string => $state['label'] ?? null)
                                            ->addActionLabel('Yeni Ayar Ekle')
                                            ->reorderableWithButtons(),
                                    ])
                                    ->itemLabel(fn(array $state): ?string => $state['tab_name'] ?? null)
                                    ->collapsed()
                                    ->collapsible()
                                    ->addActionLabel('Yeni Grup Ekle')
                                    ->reorderableWithButtons(),
                            ]),
                    ])->columnSpan('full'),
            ]);
    }

    public function save(): void
    {
        $settings = app(GeneralSettings::class);
        $oldPath = $settings->attachments_path;

        parent::save();

        // Reload settings to get new value
        try {
            $settings = app(GeneralSettings::class);
        } catch (\Throwable $e) {
        }

        $newPath = $settings->attachments_path;

        if ($oldPath !== $newPath && !empty($newPath)) {
            $this->handleAttachmentsRename($oldPath, $newPath);
        }
    }

    protected function handleAttachmentsRename(string $oldPath, string $newPath): void
    {
        $disk = \Illuminate\Support\Facades\Storage::disk('public'); // storage/app/public

        try {
            \Illuminate\Support\Facades\Log::info("Renaming attachments from [{$oldPath}] to [{$newPath}]");

            // 1. Rename physical directory
            if ($disk->exists($oldPath)) {
                $disk->move($oldPath, $newPath);
            } elseif (!$disk->exists($newPath)) {
                $disk->makeDirectory($newPath);
            }

            // 2. Remove OLD symlink
            if (!empty($oldPath)) {
                $publicOld = public_path($oldPath);
                $winPath = str_replace('/', '\\', $publicOld);

                \Illuminate\Support\Facades\Log::info("Cleaning up old link: {$winPath}");

                // PHP Native Attempts
                if (is_link($publicOld)) {
                    @unlink($publicOld);
                }

                if (is_dir($publicOld) && !is_link($publicOld)) {
                    @rmdir($publicOld);
                }

                // Windows Force Attempts
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    exec("cmd /c rmdir \"{$winPath}\"");
                    exec("cmd /c del /q \"{$winPath}\"");
                    if (is_dir($publicOld)) {
                        exec("cmd /c rmdir /s /q \"{$winPath}\"");
                    }
                } else {
                    exec("rm -rf \"{$publicOld}\"");
                }
            }

            // 3. Update runtime config to ensure artisan command uses NEW path
            config([
                'filesystems.links' => [
                    public_path($newPath) => storage_path("app/public/{$newPath}"),
                ]
            ]);

            // 4. Create NEW symlink
            \Illuminate\Support\Facades\Artisan::call('storage:link');

            \Filament\Notifications\Notification::make()
                ->title('Klasör taşındı ve linkler güncellendi.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            \Filament\Notifications\Notification::make()
                ->title('Klasör taşıma hatası: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}
