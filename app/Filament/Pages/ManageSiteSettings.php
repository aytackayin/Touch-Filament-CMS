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
                        Select::make('tab_icon')
                            ->label('Sekme İkonu')
                            ->options(static::getIcons())
                            ->searchable()
                            ->prefixIcon(fn($state) => (blank($state) || str_starts_with($state, 'heroicon-')) ? $state : 'heroicon-' . $state)
                            ->native(false),
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

    public static function getIcons(): array
    {
        $icons = [
            'Academic Cap' => Heroicon::OutlinedAcademicCap,

            'Adjustments Horizontal' => Heroicon::OutlinedAdjustmentsHorizontal,
            'Adjustments Vertical' => Heroicon::OutlinedAdjustmentsVertical,

            'Archive Box' => Heroicon::OutlinedArchiveBox,
            'Archive Box Arrow Down' => Heroicon::OutlinedArchiveBoxArrowDown,
            'Archive Box X Mark' => Heroicon::OutlinedArchiveBoxXMark,

            'Arrow Down' => Heroicon::OutlinedArrowDown,
            'Arrow Down Circle' => Heroicon::OutlinedArrowDownCircle,
            'Arrow Down Left' => Heroicon::OutlinedArrowDownLeft,
            'Arrow Down On Square' => Heroicon::OutlinedArrowDownOnSquare,
            'Arrow Down On Square Stack' => Heroicon::OutlinedArrowDownOnSquareStack,
            'Arrow Down Right' => Heroicon::OutlinedArrowDownRight,
            'Arrow Down Tray' => Heroicon::OutlinedArrowDownTray,

            'Arrow Left' => Heroicon::OutlinedArrowLeft,
            'Arrow Left Circle' => Heroicon::OutlinedArrowLeftCircle,
            'Arrow Left End On Rectangle' => Heroicon::OutlinedArrowLeftEndOnRectangle,
            'Arrow Left Start On Rectangle' => Heroicon::OutlinedArrowLeftStartOnRectangle,

            'Arrow Long Down' => Heroicon::OutlinedArrowLongDown,
            'Arrow Long Left' => Heroicon::OutlinedArrowLongLeft,
            'Arrow Long Right' => Heroicon::OutlinedArrowLongRight,
            'Arrow Long Up' => Heroicon::OutlinedArrowLongUp,

            'Arrow Path' => Heroicon::OutlinedArrowPath,
            'Arrow Path Rounded Square' => Heroicon::OutlinedArrowPathRoundedSquare,

            'Arrow Right' => Heroicon::OutlinedArrowRight,
            'Arrow Right Circle' => Heroicon::OutlinedArrowRightCircle,
            'Arrow Right End On Rectangle' => Heroicon::OutlinedArrowRightEndOnRectangle,
            'Arrow Right Start On Rectangle' => Heroicon::OutlinedArrowRightStartOnRectangle,

            'Arrow Top Right On Square' => Heroicon::OutlinedArrowTopRightOnSquare,

            'Arrow Trending Down' => Heroicon::OutlinedArrowTrendingDown,
            'Arrow Trending Up' => Heroicon::OutlinedArrowTrendingUp,

            'Arrow Turn Down Left' => Heroicon::OutlinedArrowTurnDownLeft,
            'Arrow Turn Down Right' => Heroicon::OutlinedArrowTurnDownRight,
            'Arrow Turn Left Down' => Heroicon::OutlinedArrowTurnLeftDown,
            'Arrow Turn Left Up' => Heroicon::OutlinedArrowTurnLeftUp,
            'Arrow Turn Right Down' => Heroicon::OutlinedArrowTurnRightDown,
            'Arrow Turn Right Up' => Heroicon::OutlinedArrowTurnRightUp,
            'Arrow Turn Up Left' => Heroicon::OutlinedArrowTurnUpLeft,
            'Arrow Turn Up Right' => Heroicon::OutlinedArrowTurnUpRight,

            'Arrow Up' => Heroicon::OutlinedArrowUp,
            'Arrow Up Circle' => Heroicon::OutlinedArrowUpCircle,
            'Arrow Up Left' => Heroicon::OutlinedArrowUpLeft,
            'Arrow Up On Square' => Heroicon::OutlinedArrowUpOnSquare,
            'Arrow Up On Square Stack' => Heroicon::OutlinedArrowUpOnSquareStack,
            'Arrow Up Right' => Heroicon::OutlinedArrowUpRight,
            'Arrow Up Tray' => Heroicon::OutlinedArrowUpTray,

            'Arrow Uturn Down' => Heroicon::OutlinedArrowUturnDown,
            'Arrow Uturn Left' => Heroicon::OutlinedArrowUturnLeft,
            'Arrow Uturn Right' => Heroicon::OutlinedArrowUturnRight,
            'Arrow Uturn Up' => Heroicon::OutlinedArrowUturnUp,

            'Arrows Pointing In' => Heroicon::OutlinedArrowsPointingIn,
            'Arrows Pointing Out' => Heroicon::OutlinedArrowsPointingOut,
            'Arrows Right Left' => Heroicon::OutlinedArrowsRightLeft,
            'Arrows Up Down' => Heroicon::OutlinedArrowsUpDown,

            'At Symbol' => Heroicon::OutlinedAtSymbol,

            'Backspace' => Heroicon::OutlinedBackspace,
            'Backward' => Heroicon::OutlinedBackward,
            'Banknotes' => Heroicon::OutlinedBanknotes,

            'Bars 2' => Heroicon::OutlinedBars2,
            'Bars 3' => Heroicon::OutlinedBars3,
            'Bars 3 Bottom Left' => Heroicon::OutlinedBars3BottomLeft,
            'Bars 3 Bottom Right' => Heroicon::OutlinedBars3BottomRight,
            'Bars 3 Center Left' => Heroicon::OutlinedBars3CenterLeft,
            'Bars 4' => Heroicon::OutlinedBars4,
            'Bars Arrow Down' => Heroicon::OutlinedBarsArrowDown,
            'Bars Arrow Up' => Heroicon::OutlinedBarsArrowUp,

            'Battery 0' => Heroicon::OutlinedBattery0,
            'Battery 50' => Heroicon::OutlinedBattery50,
            'Battery 100' => Heroicon::OutlinedBattery100,

            'Beaker' => Heroicon::OutlinedBeaker,

            'Bell' => Heroicon::OutlinedBell,
            'Bell Alert' => Heroicon::OutlinedBellAlert,
            'Bell Slash' => Heroicon::OutlinedBellSlash,
            'Bell Snooze' => Heroicon::OutlinedBellSnooze,

            'Bolt' => Heroicon::OutlinedBolt,
            'Bolt Slash' => Heroicon::OutlinedBoltSlash,

            'Book Open' => Heroicon::OutlinedBookOpen,

            'Bookmark' => Heroicon::OutlinedBookmark,
            'Bookmark Slash' => Heroicon::OutlinedBookmarkSlash,
            'Bookmark Square' => Heroicon::OutlinedBookmarkSquare,

            'Briefcase' => Heroicon::OutlinedBriefcase,

            'Bug Ant' => Heroicon::OutlinedBugAnt,

            'Building Library' => Heroicon::OutlinedBuildingLibrary,
            'Building Office' => Heroicon::OutlinedBuildingOffice,
            'Building Office 2' => Heroicon::OutlinedBuildingOffice2,
            'Building Storefront' => Heroicon::OutlinedBuildingStorefront,

            'Cake' => Heroicon::OutlinedCake,
            'Calculator' => Heroicon::OutlinedCalculator,

            'Calendar' => Heroicon::OutlinedCalendar,
            'Calendar Date Range' => Heroicon::OutlinedCalendarDateRange,
            'Calendar Days' => Heroicon::OutlinedCalendarDays,

            'Camera' => Heroicon::OutlinedCamera,

            'Chart Bar' => Heroicon::OutlinedChartBar,
            'Chart Bar Square' => Heroicon::OutlinedChartBarSquare,
            'Chart Pie' => Heroicon::OutlinedChartPie,

            'Chat Bubble Bottom Center' => Heroicon::OutlinedChatBubbleBottomCenter,
            'Chat Bubble Bottom Center Text' => Heroicon::OutlinedChatBubbleBottomCenterText,
            'Chat Bubble Left' => Heroicon::OutlinedChatBubbleLeft,
            'Chat Bubble Left Ellipsis' => Heroicon::OutlinedChatBubbleLeftEllipsis,
            'Chat Bubble Left Right' => Heroicon::OutlinedChatBubbleLeftRight,
            'Chat Bubble Oval Left' => Heroicon::OutlinedChatBubbleOvalLeft,
            'Chat Bubble Oval Left Ellipsis' => Heroicon::OutlinedChatBubbleOvalLeftEllipsis,

            'Check' => Heroicon::OutlinedCheck,
            'Check Badge' => Heroicon::OutlinedCheckBadge,
            'Check Circle' => Heroicon::OutlinedCheckCircle,

            'Chevron Double Down' => Heroicon::OutlinedChevronDoubleDown,
            'Chevron Double Left' => Heroicon::OutlinedChevronDoubleLeft,
            'Chevron Double Right' => Heroicon::OutlinedChevronDoubleRight,
            'Chevron Double Up' => Heroicon::OutlinedChevronDoubleUp,

            'Chevron Down' => Heroicon::OutlinedChevronDown,
            'Chevron Left' => Heroicon::OutlinedChevronLeft,
            'Chevron Right' => Heroicon::OutlinedChevronRight,
            'Chevron Up' => Heroicon::OutlinedChevronUp,
            'Chevron Up Down' => Heroicon::OutlinedChevronUpDown,

            'Circle Stack' => Heroicon::OutlinedCircleStack,

            'Clipboard' => Heroicon::OutlinedClipboard,
            'Clipboard Document' => Heroicon::OutlinedClipboardDocument,
            'Clipboard Document Check' => Heroicon::OutlinedClipboardDocumentCheck,
            'Clipboard Document List' => Heroicon::OutlinedClipboardDocumentList,

            'Clock' => Heroicon::OutlinedClock,

            'Cloud' => Heroicon::OutlinedCloud,
            'Cloud Arrow Down' => Heroicon::OutlinedCloudArrowDown,
            'Cloud Arrow Up' => Heroicon::OutlinedCloudArrowUp,

            'Code Bracket' => Heroicon::OutlinedCodeBracket,
            'Code Bracket Square' => Heroicon::OutlinedCodeBracketSquare,

            'Cog' => Heroicon::OutlinedCog,
            'Cog 6 Tooth' => Heroicon::OutlinedCog6Tooth,
            'Cog 8 Tooth' => Heroicon::OutlinedCog8Tooth,

            'Command Line' => Heroicon::OutlinedCommandLine,
            'Computer Desktop' => Heroicon::OutlinedComputerDesktop,
            'Cpu Chip' => Heroicon::OutlinedCpuChip,

            'Credit Card' => Heroicon::OutlinedCreditCard,

            'Cube' => Heroicon::OutlinedCube,
            'Cube Transparent' => Heroicon::OutlinedCubeTransparent,

            'Device Phone Mobile' => Heroicon::OutlinedDevicePhoneMobile,
            'Device Tablet' => Heroicon::OutlinedDeviceTablet,

            'Document' => Heroicon::OutlinedDocument,
            'Document Arrow Down' => Heroicon::OutlinedDocumentArrowDown,
            'Document Arrow Up' => Heroicon::OutlinedDocumentArrowUp,
            'Document Chart Bar' => Heroicon::OutlinedDocumentChartBar,
            'Document Check' => Heroicon::OutlinedDocumentCheck,
            'Document Duplicate' => Heroicon::OutlinedDocumentDuplicate,
            'Document Magnifying Glass' => Heroicon::OutlinedDocumentMagnifyingGlass,
            'Document Minus' => Heroicon::OutlinedDocumentMinus,
            'Document Plus' => Heroicon::OutlinedDocumentPlus,
            'Document Text' => Heroicon::OutlinedDocumentText,

            'Ellipsis Horizontal' => Heroicon::OutlinedEllipsisHorizontal,
            'Ellipsis Horizontal Circle' => Heroicon::OutlinedEllipsisHorizontalCircle,
            'Ellipsis Vertical' => Heroicon::OutlinedEllipsisVertical,

            'Envelope' => Heroicon::OutlinedEnvelope,
            'Envelope Open' => Heroicon::OutlinedEnvelopeOpen,

            'Exclamation Circle' => Heroicon::OutlinedExclamationCircle,
            'Exclamation Triangle' => Heroicon::OutlinedExclamationTriangle,

            'Eye' => Heroicon::OutlinedEye,
            'Eye Dropper' => Heroicon::OutlinedEyeDropper,
            'Eye Slash' => Heroicon::OutlinedEyeSlash,

            'Face Frown' => Heroicon::OutlinedFaceFrown,
            'Face Smile' => Heroicon::OutlinedFaceSmile,

            'Film' => Heroicon::OutlinedFilm,

            'Finger Print' => Heroicon::OutlinedFingerPrint,

            'Fire' => Heroicon::OutlinedFire,

            'Flag' => Heroicon::OutlinedFlag,

            'Folder' => Heroicon::OutlinedFolder,
            'Folder Arrow Down' => Heroicon::OutlinedFolderArrowDown,
            'Folder Minus' => Heroicon::OutlinedFolderMinus,
            'Folder Open' => Heroicon::OutlinedFolderOpen,
            'Folder Plus' => Heroicon::OutlinedFolderPlus,

            'Forward' => Heroicon::OutlinedForward,

            'Funnel' => Heroicon::OutlinedFunnel,

            'Gif' => Heroicon::OutlinedGif,

            'Gift' => Heroicon::OutlinedGift,

            'Globe Alt' => Heroicon::OutlinedGlobeAlt,
            'Globe Europe Africa' => Heroicon::OutlinedGlobeEuropeAfrica,
            'Globe Asia Australia' => Heroicon::OutlinedGlobeAsiaAustralia,
            'Hand Raised' => Heroicon::OutlinedHandRaised,
            'Hand Thumb Down' => Heroicon::OutlinedHandThumbDown,
            'Hand Thumb Up' => Heroicon::OutlinedHandThumbUp,

            'Hashtag' => Heroicon::OutlinedHashtag,

            'Heart' => Heroicon::OutlinedHeart,

            'Home' => Heroicon::OutlinedHome,
            'Home Modern' => Heroicon::OutlinedHomeModern,

            'Identification' => Heroicon::OutlinedIdentification,

            'Inbox' => Heroicon::OutlinedInbox,
            'Inbox Arrow Down' => Heroicon::OutlinedInboxArrowDown,
            'Inbox Stack' => Heroicon::OutlinedInboxStack,

            'Information Circle' => Heroicon::OutlinedInformationCircle,

            'Key' => Heroicon::OutlinedKey,

            'Language' => Heroicon::OutlinedLanguage,

            'Lifebuoy' => Heroicon::OutlinedLifebuoy,

            'Light Bulb' => Heroicon::OutlinedLightBulb,

            'Link' => Heroicon::OutlinedLink,

            'List Bullet' => Heroicon::OutlinedListBullet,

            'Lock Closed' => Heroicon::OutlinedLockClosed,
            'Lock Open' => Heroicon::OutlinedLockOpen,

            'Magnifying Glass' => Heroicon::OutlinedMagnifyingGlass,
            'Magnifying Glass Circle' => Heroicon::OutlinedMagnifyingGlassCircle,
            'Magnifying Glass Minus' => Heroicon::OutlinedMagnifyingGlassMinus,
            'Magnifying Glass Plus' => Heroicon::OutlinedMagnifyingGlassPlus,

            'Map' => Heroicon::OutlinedMap,
            'Map Pin' => Heroicon::OutlinedMapPin,

            'Microphone' => Heroicon::OutlinedMicrophone,

            'Minus' => Heroicon::OutlinedMinus,
            'Minus Circle' => Heroicon::OutlinedMinusCircle,

            'Moon' => Heroicon::OutlinedMoon,

            'Musical Note' => Heroicon::OutlinedMusicalNote,

            'Newspaper' => Heroicon::OutlinedNewspaper,

            'No Symbol' => Heroicon::OutlinedNoSymbol,

            'Paint Brush' => Heroicon::OutlinedPaintBrush,

            'Paper Airplane' => Heroicon::OutlinedPaperAirplane,
            'Paper Clip' => Heroicon::OutlinedPaperClip,

            'Pause' => Heroicon::OutlinedPause,
            'Pause Circle' => Heroicon::OutlinedPauseCircle,

            'Pencil' => Heroicon::OutlinedPencil,
            'Pencil Square' => Heroicon::OutlinedPencilSquare,

            'Phone' => Heroicon::OutlinedPhone,
            'Phone Arrow Down Left' => Heroicon::OutlinedPhoneArrowDownLeft,
            'Phone Arrow Up Right' => Heroicon::OutlinedPhoneArrowUpRight,
            'Phone X Mark' => Heroicon::OutlinedPhoneXMark,

            'Photo' => Heroicon::OutlinedPhoto,

            'Play' => Heroicon::OutlinedPlay,
            'Play Circle' => Heroicon::OutlinedPlayCircle,

            'Plus' => Heroicon::OutlinedPlus,
            'Plus Circle' => Heroicon::OutlinedPlusCircle,

            'Power' => Heroicon::OutlinedPower,

            'Presentation Chart Bar' => Heroicon::OutlinedPresentationChartBar,
            'Presentation Chart Line' => Heroicon::OutlinedPresentationChartLine,

            'Printer' => Heroicon::OutlinedPrinter,

            'Puzzle Piece' => Heroicon::OutlinedPuzzlePiece,

            'Qr Code' => Heroicon::OutlinedQrCode,

            'Question Mark Circle' => Heroicon::OutlinedQuestionMarkCircle,

            'Queue List' => Heroicon::OutlinedQueueList,

            'Radio' => Heroicon::OutlinedRadio,

            'Receipt Percent' => Heroicon::OutlinedReceiptPercent,
            'Receipt Refund' => Heroicon::OutlinedReceiptRefund,

            'Rectangle Group' => Heroicon::OutlinedRectangleGroup,
            'Rectangle Stack' => Heroicon::OutlinedRectangleStack,

            'Rocket Launch' => Heroicon::OutlinedRocketLaunch,

            'Rss' => Heroicon::OutlinedRss,

            'Scale' => Heroicon::OutlinedScale,

            'Scissors' => Heroicon::OutlinedScissors,

            'Server' => Heroicon::OutlinedServer,

            'Share' => Heroicon::OutlinedShare,

            'Shield Check' => Heroicon::OutlinedShieldCheck,
            'Shield Exclamation' => Heroicon::OutlinedShieldExclamation,

            'Shopping Bag' => Heroicon::OutlinedShoppingBag,
            'Shopping Cart' => Heroicon::OutlinedShoppingCart,

            'Signal' => Heroicon::OutlinedSignal,

            'Sparkles' => Heroicon::OutlinedSparkles,

            'Speaker Wave' => Heroicon::OutlinedSpeakerWave,
            'Speaker X Mark' => Heroicon::OutlinedSpeakerXMark,

            'Square 2 Stack' => Heroicon::OutlinedSquare2Stack,

            'Squares 2x2' => Heroicon::OutlinedSquares2x2,

            'Star' => Heroicon::OutlinedStar,

            'Stop' => Heroicon::OutlinedStop,
            'Stop Circle' => Heroicon::OutlinedStopCircle,

            'Sun' => Heroicon::OutlinedSun,

            'Swatch' => Heroicon::OutlinedSwatch,

            'Table Cells' => Heroicon::OutlinedTableCells,

            'Tag' => Heroicon::OutlinedTag,

            'Ticket' => Heroicon::OutlinedTicket,

            'Trash' => Heroicon::OutlinedTrash,

            'Trophy' => Heroicon::OutlinedTrophy,

            'Truck' => Heroicon::OutlinedTruck,

            'Tv' => Heroicon::OutlinedTv,

            'User' => Heroicon::OutlinedUser,
            'User Circle' => Heroicon::OutlinedUserCircle,
            'User Group' => Heroicon::OutlinedUserGroup,
            'User Minus' => Heroicon::OutlinedUserMinus,
            'User Plus' => Heroicon::OutlinedUserPlus,

            'Users' => Heroicon::OutlinedUsers,

            'Variable' => Heroicon::OutlinedVariable,

            'Video Camera' => Heroicon::OutlinedVideoCamera,
            'Video Camera Slash' => Heroicon::OutlinedVideoCameraSlash,

            'View Columns' => Heroicon::OutlinedViewColumns,
            'Viewfinder Circle' => Heroicon::OutlinedViewfinderCircle,

            'Wallet' => Heroicon::OutlinedWallet,

            'Wifi' => Heroicon::OutlinedWifi,

            'Window' => Heroicon::OutlinedWindow,

            'Wrench' => Heroicon::OutlinedWrench,

            'X Circle' => Heroicon::OutlinedXCircle,
            'X Mark' => Heroicon::OutlinedXMark,
        ];


        ksort($icons);

        $mapped = [];
        foreach ($icons as $label => $icon) {
            $value = is_object($icon) ? $icon->value : $icon;

            // Filament 4 Heroicon enums don't include the 'heroicon-' prefix in their value.
            // We need to add it so Blade Icons can find the SVG in the correct set.
            if (!str_starts_with($value, 'heroicon-')) {
                $value = 'heroicon-' . $value;
            }

            $mapped[$value] = $label;
        }


        return $mapped;
    }
}
