<?php

namespace App\Filament\Pages;

use App\Settings\GeneralSettings;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Placeholder;
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
        // Sayfa state'ini stabilize etmek için veriyi alıyoruz.
        // live() olmayan alanlar sayesinde yazarken refresh sorunu yaşanmaz.
        $customSettings = $this->data['custom_settings'] ?? app(GeneralSettings::class)->custom_settings ?? [];

        // ÖNEMLİ: Repeater bazen UUID keyler kullanır. Dynamic Tab'ların veri yolu (path) kararlı olsun diye
        // burada veriyi sadece değerler (values) olarak alıp 0, 1, 2 gibi indexlere zorluyoruz.
        // Böylece Inputlar her zaman "tab_values.0.0" gibi erişilir.
        $customSettings = array_values($customSettings);

        $tabs = [];

        // 1. Genel Sekmesi
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
                    ->default('attachments')
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn($state, callable $set) => $set('attachments_path', Str::slug($state))),
            ]);

        // 2. Dinamik sekmeler (Sadece değer düzenleme - Refresh yapmaz)
        foreach ($customSettings as $index => $group) {
            if (empty($group['tab_name']))
                continue;

            $fields = [];
            // Bu alanda sadece değerleri gösteriyoruz. live() YOK, bu yüzden yazarken donmaz.
            // ÖNEMLİ: Fields dizisini de normalize et (0, 1, 2...)
            $groupFields = array_values($group['fields'] ?? []);

            foreach ($groupFields as $fIndex => $fData) {
                // Ekranda görünen kutu. Veriyi 'tab_values' dizisinden yöneteceğiz.
                $fields[] = TextInput::make("tab_values.{$index}.{$fIndex}")
                    ->label($fData['label'] ?? 'Ayar')
                    ->helperText('Sistem Anahtarı: ' . ($fData['field_name'] ?? '-'))
                    ->key("custom_input_{$index}_{$fIndex}");
            }

            if (!empty($fields)) {
                $tabs[] = Tab::make('tab_view_' . $index)
                    ->label($group['tab_name'])
                    ->schema($fields);
            }
        }

        // 3. Dinamik Ayar Yönetimi (Mutfak)
        $tabs[] = Tab::make('ManageSettings')
            ->label(__('Dinamik Ayar Yönetimi'))
            ->icon(Heroicon::OutlinedRectangleStack)
            ->schema([
                Placeholder::make('desc')
                    ->content('Buradan yeni sekme ve ayar yapısını oluşturun. Değerleri ise yukarıdaki ilgili sekmeden girebilirsiniz.'),
                Repeater::make('custom_settings')
                    ->label('Sekme ve Ayar Şeması')
                    ->schema([
                        TextInput::make('tab_name')
                            ->label('Sekme Adı')
                            ->required()
                            ->live(onBlur: true),
                        Repeater::make('fields')
                            ->label('Ayarlar')
                            ->schema([
                                TextInput::make('label')
                                    ->label('Görünen Ad')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn($state, callable $set) => $set('field_name', Str::slug($state, '_'))),
                                TextInput::make('field_name')
                                    ->label('Sistem Anahtarı')
                                    ->required(),
                                Hidden::make('value'), // Değeri korumak için gizli alan
                            ])
                            ->columns(2)
                            ->itemLabel(fn(array $state): ?string => $state['label'] ?? null),
                    ])
                    ->itemLabel(fn(array $state): ?string => $state['tab_name'] ?? null)
                    ->collapsible()
                    ->collapsed()
                    ->live(onBlur: true), // Sadece bu repeater yapı değişince yukarıya haber vermesi için live çalışır.
            ]);

        return $schema->components([
            Tabs::make('Settings')
                ->tabs($tabs)
                ->columnSpan('full'),
        ]);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $customSettings = $data['custom_settings'] ?? [];
        $tabValues = [];
        // Veritabanından gelen veriler numerik dizidir.
        foreach ($customSettings as $i => $group) {
            foreach ($group['fields'] ?? [] as $j => $field) {
                // Değerleri index bazlı saklıyoruz: [groupIndex][fieldIndex]
                $tabValues[$i][$j] = $field['value'] ?? null;
            }
        }
        $data['tab_values'] = $tabValues;

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $repeaterSettings = $data['custom_settings'] ?? [];
        $tabValues = $data['tab_values'] ?? [];

        \Illuminate\Support\Facades\Log::info('--- SAVE START ---');
        \Illuminate\Support\Facades\Log::info('RAW custom_settings:', $repeaterSettings);
        \Illuminate\Support\Facades\Log::info('RAW tab_values:', $tabValues);

        // 1. REPEATER tarafını normalize et (Keyleri temizle, sadece sıralı liste yap)
        $groups = array_values($repeaterSettings);

        // 2. INPUT tarafını normalize et (Keyleri temizle, sadece sıralı liste yap)
        // Yeni eklenenler UUID, eskiler Int gelebilir. Hepsini 0,1,2 silsilesine çeviriyoruz.
        $normalizedTabValues = array_values($tabValues);

        \Illuminate\Support\Facades\Log::info('Normalized Tab Values:', $normalizedTabValues);

        $normalizedResult = [];

        foreach ($groups as $gIndex => $group) {
            // Tab adı olmayan (boş) satırları atla
            if (empty($group['tab_name']))
                continue;

            // Grup için bir değer dizisi var mı? (Sıra numarası ile kontrol)
            $groupValues = $normalizedTabValues[$gIndex] ?? [];
            // Bu değer dizisini de normalize et (içindeki field keylerini temizle)
            if (is_array($groupValues)) {
                $groupValues = array_values($groupValues);
            }

            $cleanGroup = $group;
            $cleanGroup['fields'] = []; // Temiz bir başlangıç

            if (isset($group['fields']) && is_array($group['fields'])) {
                // Repeater fields normalize et
                $fields = array_values($group['fields']);

                foreach ($fields as $fIndex => $field) {
                    $oldVal = $field['value'] ?? 'NULL';

                    // Eşleşen değer var mı? (Sıra numarası ile)
                    if (array_key_exists($fIndex, $groupValues)) {
                        $newVal = $groupValues[$fIndex];
                        $field['value'] = $newVal;
                        \Illuminate\Support\Facades\Log::info("Group [$gIndex] Field [$fIndex] ('{$field['label']}') UPDATE: $oldVal -> $newVal");
                    } else {
                        \Illuminate\Support\Facades\Log::warning("Group [$gIndex] Field [$fIndex] ('{$field['label']}') NO MATCH FOUND in tab_values.");
                    }
                    $cleanGroup['fields'][] = $field;
                }
            }

            $normalizedResult[] = $cleanGroup;
        }

        \Illuminate\Support\Facades\Log::info('FINAL custom_settings:', $normalizedResult);
        \Illuminate\Support\Facades\Log::info('--- SAVE END ---');

        $data['custom_settings'] = $normalizedResult;
        unset($data['tab_values']);

        return $data;
    }

    public function save(): void
    {
        $settings = app(GeneralSettings::class);
        $oldPath = $settings->attachments_path;

        parent::save();

        // Path değişimi kontrolü
        $newPath = app(GeneralSettings::class)->attachments_path;
        if ($oldPath !== $newPath && !empty($newPath)) {
            $this->handleAttachmentsRename($oldPath, $newPath);
        }

        // Başarılı işlem sonrası net redirect
        $this->redirect(static::getUrl());
    }

    protected function handleAttachmentsRename(string $oldPath, string $newPath): void
    {
        $disk = \Illuminate\Support\Facades\Storage::disk('public');
        try {
            if ($disk->exists($oldPath)) {
                $disk->move($oldPath, $newPath);
            }
            config(['filesystems.links' => [public_path($newPath) => storage_path("app/public/{$newPath}")]]);
            \Illuminate\Support\Facades\Artisan::call('storage:link');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Path Rename Error: " . $e->getMessage());
        }
    }
}
