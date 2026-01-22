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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\HtmlString;
use Filament\Actions\Action;

use AytacKayin\FilamentSelectIcon\Forms\Components\SelectIcon;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class ManageSiteSettings extends SettingsPage
{
    use HasPageShield;
    protected static string $settings = GeneralSettings::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    public static function getNavigationGroup(): ?string
    {
        return __('settings.nav.group');
    }
    public static function getNavigationSort(): ?int
    {
        return 401;
    }

    public static function getNavigationLabel(): string
    {
        return __('settings.nav.label');
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
            ->label(__('settings.label.general'))
            ->icon(Heroicon::OutlinedCog6Tooth)
            ->schema([
                TextInput::make('site_title')
                    ->label(__('settings.label.site_title'))
                    ->required(),
                Textarea::make('site_description')
                    ->label(__('settings.label.site_description'))
                    ->rows(3),
                TagsInput::make('site_keywords')
                    ->label(__('settings.label.site_keywords')),
                TextInput::make('attachments_path')
                    ->label(__('settings.label.attachments_path'))
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
                    ->label($fData['label'] ?? __('settings.label.settings'))
                    ->helperText(__('settings.label.field_name') . ': ' . ($fData['field_name'] ?? '-'))
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
            ->label(__('settings.label.manage_settings'))
            ->icon(Heroicon::OutlinedRectangleStack)
            ->schema([
                Placeholder::make('desc')
                    ->label(__('settings.label.description'))
                    ->content(new HtmlString('<div style="font-size: 0.8rem; opacity: 0.6; line-height: 1.4;">' . __('settings.label.manage_desc') . '</div>')),
                Repeater::make('custom_settings')
                    ->label(__('settings.label.schema_label'))
                    ->addActionLabel(__('settings.label.add_tab'))
                    ->addAction(fn(Action $action) => $action->color('success')->icon(Heroicon::OutlinedFolderPlus))
                    ->collapseAllAction(fn(Action $action) => $action->size('xs')->extraAttributes(['style' => 'opacity: 0.6']))
                    ->expandAllAction(fn(Action $action) => $action->size('xs')->extraAttributes(['style' => 'opacity: 0.6']))
                    ->schema([
                        TextInput::make('tab_name')
                            ->label(new HtmlString('<span style="opacity: 0.7; font-weight: 500;">' . __('settings.label.tab_name') . '</span>'))
                            ->extraInputAttributes(['style' => 'opacity: 0.8;'])
                            ->required(),
                        SelectIcon::make('tab_icon')
                            ->label(new HtmlString('<span style="opacity: 0.7; font-weight: 500;">' . __('settings.label.tab_icon') . '</span>'))
                            ->extraInputAttributes(['style' => 'opacity: 0.8;']),





                        Repeater::make('fields')
                            ->label(__('settings.label.fields'))
                            ->addActionLabel(__('settings.label.add_field'))
                            ->addAction(fn(Action $action) => $action->color('success')->icon(Heroicon::OutlinedBars3))
                            ->schema([
                                TextInput::make('label')
                                    ->label(new HtmlString('<span style="opacity: 0.7; font-weight: 500;">' . __('settings.label.field_label') . '</span>'))
                                    ->extraInputAttributes(['style' => 'opacity: 0.8;'])
                                    ->required(),
                                TextInput::make('field_name')
                                    ->label(new HtmlString('<span style="opacity: 0.7; font-weight: 500;">' . __('settings.label.field_name') . '</span>'))
                                    ->extraInputAttributes(['style' => 'opacity: 0.8;'])
                                    ->required(),
                                Select::make('type')
                                    ->label(new HtmlString('<span style="opacity: 0.7; font-weight: 500;">' . __('settings.label.field_type') . '</span>'))
                                    ->extraInputAttributes(['style' => 'opacity: 0.8;'])
                                    ->options([
                                        'text' => __('settings.label.types.text'),
                                        'email' => __('settings.label.types.email'),
                                        'number' => __('settings.label.types.number'),
                                        'tel' => __('settings.label.types.tel'),
                                        'url' => __('settings.label.types.url'),
                                        'password' => __('settings.label.types.password'),
                                        'textarea' => __('settings.label.types.textarea'),
                                        'richtext' => __('settings.label.types.richtext'),
                                        'select' => __('settings.label.types.select'),
                                        'checkbox' => __('settings.label.types.checkbox'),
                                        'checkbox_list' => __('settings.label.types.checkbox_list'),
                                        'radio' => __('settings.label.types.radio'),
                                        'color' => __('settings.label.types.color'),
                                        'date' => __('settings.label.types.date'),
                                        'time' => __('settings.label.types.time'),
                                        'datetime' => __('settings.label.types.datetime'),
                                        'tags' => __('settings.label.types.tags'),
                                    ])
                                    ->default('text')
                                    ->required()
                                    ->live(),
                                KeyValue::make('options')
                                    ->label(new HtmlString('<span style="opacity: 0.7; font-weight: 500;">' . __('settings.label.options') . '</span>'))
                                    ->extraAttributes(['style' => 'opacity: 0.8;'])
                                    ->helperText(__('settings.label.options_helper'))
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
            'text' => TextInput::make($statePath)->extraInputAttributes(['style' => 'opacity: 0.8']),
            'email' => TextInput::make($statePath)->email()->extraInputAttributes(['style' => 'opacity: 0.8']),
            'number' => TextInput::make($statePath)->numeric()->extraInputAttributes(['style' => 'opacity: 0.8']),
            'tel' => TextInput::make($statePath)->tel()->extraInputAttributes(['style' => 'opacity: 0.8']),
            'url' => TextInput::make($statePath)->url()->extraInputAttributes(['style' => 'opacity: 0.8']),
            'password' => TextInput::make($statePath)->password()->revealable()->extraInputAttributes(['style' => 'opacity: 0.8']),
            'textarea' => Textarea::make($statePath)->rows(3)->extraInputAttributes(['style' => 'opacity: 0.8']),
            'select' => Select::make($statePath)
                ->options($options)
                ->searchable()
                ->native(false) // Daha iyi UI ve tip uyumu için
                ->in($optionKeys) // Validasyon hatasını önlemek için açık kural
                ->extraInputAttributes(['style' => 'opacity: 0.8']),
            'checkbox' => Toggle::make($statePath),
            'checkbox_list' => CheckboxList::make($statePath)
                ->options($options)
                ->in($optionKeys),
            'radio' => Radio::make($statePath)
                ->options($options)
                ->in($optionKeys),
            'color' => ColorPicker::make($statePath),
            'date' => DatePicker::make($statePath)->extraInputAttributes(['style' => 'opacity: 0.8']),
            'time' => TimePicker::make($statePath)->extraInputAttributes(['style' => 'opacity: 0.8']),
            'datetime' => DateTimePicker::make($statePath)->extraInputAttributes(['style' => 'opacity: 0.8']),
            'tags' => TagsInput::make($statePath)->extraInputAttributes(['style' => 'opacity: 0.8']),
            default => TextInput::make($statePath)->extraInputAttributes(['style' => 'opacity: 0.8']),
        };
    }

    protected function handleAttachmentsRename(string $oldPath, string $newPath): void
    {
        $disk = Storage::disk('public');
        try {
            if ($disk->exists($oldPath)) {
                $disk->move($oldPath, $newPath);
            }
            config(['filesystems.links' => [public_path($newPath) => storage_path("app/public/{$newPath}")]]);
            Artisan::call('storage:link');
        } catch (Exception $e) {
            Log::error("Path Rename Error: " . $e->getMessage());
        }
    }
}
