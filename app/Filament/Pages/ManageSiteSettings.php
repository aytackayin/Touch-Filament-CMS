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
use Illuminate\Support\Str;

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
        $settings = app(GeneralSettings::class);
        $customSettings = $this->data['custom_settings'] ?? $settings->custom_settings ?? [];

        $tabs = [];

        // 1. Sabit Genel Sekmesi
        $tabs[] = Tab::make('General')
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
                    ->afterStateUpdated(fn($state, callable $set) => $set('attachments_path', Str::slug($state))),
            ]);

        // 2. Dinamik Olarak Oluşturulan Sekmeler (Sadece Değer Düzenleme)
        foreach ($customSettings as $index => $group) {
            if (empty($group['tab_name']))
                continue;

            $fields = $group['fields'] ?? [];

            $tabs[] = Tab::make('dynamic_view_' . $index)
                ->label($group['tab_name'])
                ->schema([
                    \Filament\Schemas\Components\Group::make()
                        ->statePath("custom_settings.{$index}.fields")
                        ->schema(function () use ($fields) {
                            $inputs = [];
                            foreach ($fields as $fIndex => $fData) {
                                $inputs[] = \Filament\Schemas\Components\Grid::make(3)->schema([
                                    TextInput::make("{$fIndex}.label")
                                        ->label('Ayar Adı')
                                        ->disabled()
                                        ->dehydrated(),
                                    TextInput::make("{$fIndex}.field_name")
                                        ->label('Sistem Anahtarı')
                                        ->disabled()
                                        ->dehydrated(),
                                    TextInput::make("{$fIndex}.value")
                                        ->label('Değeri')
                                        ->required(),
                                ]);
                            }
                            return $inputs;
                        })
                ]);
        }

        // 3. Dinamik Ayar Yönetimi (Grup/Ayar Tanımlama ve Silme)
        $tabs[] = Tab::make('Manage Dynamic Settings')
            ->label(__('Dinamik Ayar Yönetimi'))
            ->icon(Heroicon::OutlinedRectangleStack)
            ->schema([
                \Filament\Forms\Components\Placeholder::make('info_help')
                    ->content('Yeni sekmeler ve veri anahtarlarını buradan tanımlayın. Değerleri değiştirmek için yukarıda oluşan sekmeleri kullanın.'),
                \Filament\Forms\Components\Repeater::make('custom_settings')
                    ->label(__('Sekme Grupları ve Şema'))
                    ->schema([
                        TextInput::make('tab_name')
                            ->label('Grup (Sekme) Adı')
                            ->required()
                            ->columnSpanFull(),
                        \Filament\Forms\Components\Repeater::make('fields')
                            ->label('Bu Gruba Ait Ayar Tanımları')
                            ->schema([
                                TextInput::make('label')
                                    ->label('Görünen Ad')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn($state, callable $set) => $set('field_name', Str::slug($state, '_'))),
                                TextInput::make('field_name')
                                    ->label('Sistem Anahtarı (Config Field)')
                                    ->required()
                                    ->extraInputAttributes([
                                        'x-on:input' => "\$el.value = \$el.value.toLowerCase().replace(/\s+/g, '_').replace(/[^a-z0-9_]/g, '')",
                                    ]),
                                TextInput::make('value')
                                    ->label('Varsayılan Değer'),
                            ])
                            ->columns(3)
                            ->collapsible()
                            ->itemLabel(fn(array $state): ?string => $state['label'] ?? null)
                            ->addActionLabel('Yeni Ayar Tanımla'),
                    ])
                    ->itemLabel(fn(array $state): ?string => $state['tab_name'] ?? null)
                    ->collapsed()
                    ->collapsible()
                    ->addActionLabel('Yeni Grup/Sekme Ekle')
                    ->reorderableWithButtons()
                    ->live(),
            ]);

        return $schema->components([
            Tabs::make('Settings')
                ->tabs($tabs)
                ->columnSpan('full'),
        ]);
    }

    public function save(): void
    {
        // Temizlik: İsmi olmayan grupları filtrele
        if (isset($this->data['custom_settings']) && is_array($this->data['custom_settings'])) {
            $this->data['custom_settings'] = array_filter(
                $this->data['custom_settings'],
                fn($group) => !empty($group['tab_name'])
            );
            $this->data['custom_settings'] = array_values($this->data['custom_settings']);
        }

        $settings = app(GeneralSettings::class);
        $oldPath = $settings->attachments_path;

        parent::save();

        try {
            $settings = app(GeneralSettings::class);
        } catch (\Throwable $e) {
        }

        $newPath = $settings->attachments_path;

        if ($oldPath !== $newPath && !empty($newPath)) {
            $this->handleAttachmentsRename($oldPath, $newPath);
        }

        // Başarılı işlem sonrası yönlendirme
        $this->redirect('https://filament_cms.test/admin/manage-site-settings');
    }

    protected function handleAttachmentsRename(string $oldPath, string $newPath): void
    {
        $disk = \Illuminate\Support\Facades\Storage::disk('public');

        try {
            \Illuminate\Support\Facades\Log::info("Renaming attachments from [{$oldPath}] to [{$newPath}]");

            if ($disk->exists($oldPath)) {
                $disk->move($oldPath, $newPath);
            } elseif (!$disk->exists($newPath)) {
                $disk->makeDirectory($newPath);
            }

            if (!empty($oldPath)) {
                $publicOld = public_path($oldPath);
                $winPath = str_replace('/', '\\', $publicOld);

                if (is_link($publicOld)) {
                    @unlink($publicOld);
                }

                if (is_dir($publicOld) && !is_link($publicOld)) {
                    @rmdir($publicOld);
                }

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

            config([
                'filesystems.links' => [
                    public_path($newPath) => storage_path("app/public/{$newPath}"),
                ]
            ]);

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
