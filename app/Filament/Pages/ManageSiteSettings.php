<?php

namespace App\Filament\Pages;

use App\Settings\GeneralSettings;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\ColorPicker;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Pages\SettingsPage;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use Illuminate\Support\Str;

use AytacKayin\FilamentSelectIcon\Forms\Components\SelectIcon;

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
        // Dinamik sekmeleri oluştururken HER ZAMAN veritabanındaki kayıtlı yapıyı kullanıyoruz.
        // Bu, Select bileşenlerinin options verisinin her zaman dolu olmasını sağlar.
        $savedSettings = app(GeneralSettings::class)->custom_settings ?? [];
        $savedSettings = array_values($savedSettings);

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
        foreach ($savedSettings as $index => $group) {
            if (empty($group['tab_name']))
                continue;

            $fields = [];
            // Bu alanda sadece değerleri gösteriyoruz. live() YOK, bu yüzden yazarken donmaz.
            // ÖNEMLİ: Fields dizisini de normalize et (0, 1, 2...)
            $groupFields = array_values($group['fields'] ?? []);

            foreach ($groupFields as $fIndex => $fData) {
                // Ekranda görünen kutu. Veriyi 'tab_values' dizisinden yöneteceğiz.
                $component = $this->getComponentByType($fData, "tab_values.{$index}.{$fIndex}")
                    ->label($fData['label'] ?? 'Ayar')
                    ->helperText('Sistem Anahtarı: ' . ($fData['field_name'] ?? '-'))
                    ->key("custom_input_{$index}_{$fIndex}");

                $fields[] = $component;
            }

            if (!empty($fields)) {
                $tabIcon = $group['tab_icon'] ?? null;
                if ($tabIcon && !str_starts_with($tabIcon, 'heroicon-')) {
                    $tabIcon = 'heroicon-' . $tabIcon;
                }

                $tabs[] = Tab::make('tab_view_' . $index)
                    ->label($group['tab_name'])
                    ->icon($tabIcon)
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
                            ->required(),
                        SelectIcon::make('tab_icon')
                            ->label('Sekme İkonu'),





                        Repeater::make('fields')
                            ->label('Ayarlar')
                            ->schema([
                                TextInput::make('label')
                                    ->label('Görünen Ad')
                                    ->required(),
                                TextInput::make('field_name')
                                    ->label('Sistem Anahtarı')
                                    ->required(),
                                Select::make('type')
                                    ->label('Veri Tipi')
                                    ->options([
                                        'text' => 'Metin (Text)',
                                        'email' => 'E-Posta',
                                        'number' => 'Sayı (Number)',
                                        'tel' => 'Telefon',
                                        'url' => 'URL (Link)',
                                        'password' => 'Şifre',
                                        'textarea' => 'Geniş Metin (Textarea)',
                                        'richtext' => 'Zengin Metin (Rich Editor)',
                                        'select' => 'Seçim Kutusu (Select)',
                                        'checkbox' => 'Onay Kutusu (Tek)',
                                        'checkbox_list' => 'Onay Listesi (Çoklu)',
                                        'radio' => 'Radyo Buton',
                                        'color' => 'Renk Seçici (Color Picker)',
                                        'date' => 'Tarih',
                                        'time' => 'Saat',
                                        'datetime' => 'Tarih ve Saat',
                                        'tags' => 'Etiketler (Tags)',
                                    ])
                                    ->default('text')
                                    ->required()
                                    ->live(),
                                KeyValue::make('options')
                                    ->label('Seçenekler (Key => Label)')
                                    ->helperText('Sadece Select, Radio ve Checkbox List için gereklidir.')
                                    ->visible(fn($get) => in_array($get('type'), ['select', 'radio', 'checkbox_list'])),
                                Hidden::make('value'), // Değeri korumak için gizli alan
                            ])
                            ->columns(2)
                            ->itemLabel(fn(array $state): ?string => $state['label'] ?? null),
                    ])
                    ->itemLabel(fn(array $state): ?string => $state['tab_name'] ?? null)
                    ->collapsible()
                    ->collapsed(),
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
            // Ensure tab_icon has the prefix for validation and display
            if (isset($group['tab_icon']) && !str_starts_with($group['tab_icon'], 'heroicon-')) {
                $customSettings[$i]['tab_icon'] = 'heroicon-' . $group['tab_icon'];
            }

            foreach ($group['fields'] ?? [] as $j => $field) {

                // Değerleri index bazlı saklıyoruz: [groupIndex][fieldIndex]
                $tabValues[$i][$j] = $field['value'] ?? null;
            }
        }
        $data['custom_settings'] = $customSettings;
        $data['tab_values'] = $tabValues;


        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $repeaterSettings = $data['custom_settings'] ?? [];
        $tabValues = $data['tab_values'] ?? [];

        // 1. REPEATER tarafını normalize et (Keyleri temizle, sadece sıralı liste yap)
        $groups = array_values($repeaterSettings);

        // 2. INPUT tarafını normalize et (Keyleri temizle, sadece sıralı liste yap)
        // Yeni eklenenler UUID, eskiler Int gelebilir. Hepsini 0,1,2 silsilesine çeviriyoruz.
        $normalizedTabValues = array_values($tabValues);

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
                    // Eşleşen değer var mı? (Sıra numarası ile)
                    if (array_key_exists($fIndex, $groupValues)) {
                        $newVal = $groupValues[$fIndex];
                        $field['value'] = $newVal;
                    }
                    $cleanGroup['fields'][] = $field;
                }
            }

            $normalizedResult[] = $cleanGroup;
        }

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

    protected function getComponentByType(array $data, string $statePath)
    {
        $type = $data['type'] ?? 'text';
        $options = $data['options'] ?? [];

        // Seçeneklerin anahtarlarını al (Validasyon için)
        $optionKeys = array_keys($options);

        return match ($type) {
            'text' => TextInput::make($statePath),
            'email' => TextInput::make($statePath)->email(),
            'number' => TextInput::make($statePath)->numeric(),
            'tel' => TextInput::make($statePath)->tel(),
            'url' => TextInput::make($statePath)->url(),
            'password' => TextInput::make($statePath)->password()->revealable(),
            'textarea' => Textarea::make($statePath)->rows(3),
            'select' => Select::make($statePath)
                ->options($options)
                ->searchable()
                ->native(false) // Daha iyi UI ve tip uyumu için
                ->in($optionKeys), // Validasyon hatasını önlemek için açık kural
            'checkbox' => Toggle::make($statePath),
            'checkbox_list' => CheckboxList::make($statePath)
                ->options($options)
                ->in($optionKeys),
            'radio' => Radio::make($statePath)
                ->options($options)
                ->in($optionKeys),
            'color' => ColorPicker::make($statePath),
            'date' => DatePicker::make($statePath),
            'time' => TimePicker::make($statePath),
            'datetime' => DateTimePicker::make($statePath),
            'tags' => TagsInput::make($statePath),
            default => TextInput::make($statePath),
        };
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
