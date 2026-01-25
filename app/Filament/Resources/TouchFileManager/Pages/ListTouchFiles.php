<?php

namespace App\Filament\Resources\TouchFileManager\Pages;

use App\Filament\Resources\TouchFileManager\TouchFileManagerResource;
use App\Models\TouchFile;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use App\Traits\HasTableSettings;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\CheckboxList;

class ListTouchFiles extends ListRecords
{
    use HasTableSettings;

    protected static string $resource = TouchFileManagerResource::class;

    public function getTableExtraAttributes(): array
    {
        return [
            'class' => 'touch-file-manager-container ' . ($this->view_type === 'grid' ? 'is-grid-view' : 'is-list-view'),
        ];
    }

    #[\Livewire\Attributes\Url]
    public ?string $parent_id = null;

    #[\Livewire\Attributes\Url]
    public string $view_type = 'grid';

    #[\Livewire\Attributes\Url]
    public ?string $iframe = null;

    public array $visibleColumns = [];

    public function mount(): void
    {
        parent::mount();
        $this->mountHasTableSettings();
    }

    protected function getTableSettingsKey(): string
    {
        return 'touchfile_list';
    }

    protected function getDefaultVisibleColumns(): array
    {
        return ['type', 'size', 'created_at', 'user']; // 4 default columns
    }

    protected function getTableColumnOptions(): array
    {
        return [
            'type' => __('file_manager.label.type'),
            'size' => __('file_manager.label.size'),
            'tags' => __('file_manager.label.tags'),
            'user' => __('file_manager.label.author'),
            'editor' => __('file_manager.label.last_editor'),
            'created_at' => __('file_manager.label.date'),
        ];
    }

    protected function applySettings(array $settings): void
    {
        $this->visibleColumns = $settings['visible_columns'] ?? [];
        if (isset($settings['view_type']) && in_array($settings['view_type'], ['grid', 'list'])) {
            $this->view_type = $settings['view_type'];
        }
    }

    protected function getTableSettingsFormSchema(): array
    {
        return [
            Radio::make('view_type')
                ->label(__('file_manager.label.default_view')) // Or some translation
                ->options([
                    'list' => __('file_manager.label.list_view'),
                    'grid' => __('file_manager.label.grid_view'),
                ])
                ->default($this->view_type)
                ->inline()
                ->required(),
            CheckboxList::make('visible_columns')
                ->label(__('table_settings.columns'))
                ->options($this->getTableColumnOptions())
                ->default($this->visibleColumns)
                ->required()
                ->columns(2),
        ];
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            static::getResource()::getUrl('index', ['view_type' => $this->view_type, 'iframe' => $this->iframe]) => static::getResource()::getBreadcrumb(),
        ];

        if ($this->parent_id) {
            $folder = TouchFile::find($this->parent_id);
            $trail = [];
            while ($folder) {
                array_unshift($trail, [
                    'url' => static::getResource()::getUrl('index', [
                        'parent_id' => $folder->id,
                        'view_type' => $this->view_type,
                        'iframe' => $this->iframe,
                    ]),
                    'label' => $folder->name,
                ]);
                $folder = $folder->parent;
            }

            foreach ($trail as $crumb) {
                $breadcrumbs[$crumb['url']] = $crumb['label'];
            }
        }

        $breadcrumbs[] = $this->getBreadcrumb();

        return $breadcrumbs;
    }

    protected function getHeaderActions(): array
    {
        $currentFolder = $this->parent_id ? TouchFile::find($this->parent_id) : null;
        $upUrl = null;

        if ($currentFolder) {
            $upParams = ['view_type' => $this->view_type, 'iframe' => $this->iframe];
            if ($currentFolder->parent_id) {
                $upParams['parent_id'] = $currentFolder->parent_id;
            }
            $upUrl = TouchFileManagerResource::getUrl('index', $upParams);
        }

        return [
            Action::make('up')
                ->label(__('file_manager.label.up'))
                ->tooltip(__('file_manager.label.up'))
                ->hiddenLabel()
                ->icon('heroicon-m-arrow-uturn-up')
                ->color('gray')
                ->visible((bool) $this->parent_id)
                ->url($upUrl)
                ->size('xs'),

            Action::make('sync')
                ->label(__('file_manager.label.sync_files'))
                ->tooltip(__('file_manager.label.sync_files'))
                ->hiddenLabel()
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->size('xs')
                ->visible(fn() => auth()->user()->can('sync', TouchFile::class))
                ->action(function () {
                    $disk = Storage::disk('attachments');

                    $parentId = $this->parent_id;
                    $basePath = null;
                    if ($parentId) {
                        $parentFolder = TouchFile::find($parentId);
                        if ($parentFolder) {
                            $basePath = $parentFolder->path;
                        }
                    }

                    $allFiles = $disk->allFiles($basePath);
                    $allDirectories = $disk->allDirectories($basePath);

                    $isExcluded = function ($path) {
                        $parts = explode('/', $path);
                        foreach ($parts as $part) {
                            if (in_array($part, ['thumbs', 'temp'])) {
                                return true;
                            }
                        }
                        return false;
                    };

                    $addedCount = 0;
                    $removedCount = 0;

                    // 1. Sync Directories & Validate Model Storage
                    usort($allDirectories, function ($a, $b) {
                        return strlen($a) - strlen($b);
                    });

                    foreach ($allDirectories as $dirPath) {
                        if ($isExcluded($dirPath))
                            continue;

                        // Validate if directory is allowed according to model associations
                        if (!$this->isAuthorizedPath($dirPath)) {
                            $disk->deleteDirectory($dirPath);
                            $removedCount++;
                            continue;
                        }

                        $existing = TouchFile::where('is_folder', true)
                            ->where('path', $dirPath)
                            ->exists();

                        if (!$existing) {
                            $name = basename($dirPath);
                            $parentPath = dirname($dirPath);
                            $foundParentId = null;

                            if ($parentPath !== '.') {
                                $parent = TouchFile::where('is_folder', true)->where('path', $parentPath)->first();
                                if ($parent) {
                                    $foundParentId = $parent->id;
                                }
                            }

                            TouchFile::create([
                                'user_id' => auth()->id(),
                                'name' => $name,
                                'path' => $dirPath,
                                'is_folder' => true,
                                'parent_id' => $foundParentId,
                            ]);

                            $addedCount++;
                        }
                    }

                    // 2. Sync Files & Validate Model Storage
                    foreach ($allFiles as $filePath) {
                        if ($isExcluded($filePath))
                            continue;

                        // Validate if file is allowed according to model associations
                        if (!$this->isAuthorizedPath($filePath)) {
                            $disk->delete($filePath);
                            $removedCount++;
                            continue;
                        }

                        $existing = TouchFile::where('is_folder', false)
                            ->where('path', $filePath)
                            ->exists();

                        if (!$existing) {
                            $name = basename($filePath);
                            $parentPath = dirname($filePath);
                            $foundParentId = null;

                            if ($parentPath !== '.') {
                                $parent = TouchFile::where('is_folder', true)->where('path', $parentPath)->first();
                                if ($parent) {
                                    $foundParentId = $parent->id;
                                }
                            }

                            $mimeType = $disk->mimeType($filePath);
                            $size = $disk->size($filePath);
                            $type = TouchFile::determineFileType($mimeType ?? '');

                            TouchFile::create([
                                'user_id' => auth()->id(),
                                'name' => $name,
                                'path' => $filePath,
                                'is_folder' => false,
                                'parent_id' => $foundParentId,
                                'mime_type' => $mimeType,
                                'size' => $size,
                                'type' => $type,
                            ]);

                            $addedCount++;
                        }
                    }

                    // 3. Cleanup orphaned records
                    $query = TouchFile::query();
                    if ($basePath) {
                        $query->where(function ($q) use ($basePath) {
                            $q->where('path', $basePath)
                                ->orWhere('path', 'like', $basePath . '/%');
                        });
                    }

                    foreach ((clone $query)->where('is_folder', false)->get() as $file) {
                        if (!$disk->exists($file->path)) {
                            $file->delete();
                            $removedCount++;
                        }
                    }

                    foreach ((clone $query)->where('is_folder', true)->get() as $folder) {
                        if (TouchFile::where('id', $folder->id)->doesntExist()) {
                            continue;
                        }

                        if (!$disk->exists($folder->path)) {
                            $folder->delete();
                            $removedCount++;
                        }
                    }

                    Notification::make()
                        ->title(__('file_manager.label.sync_notification.title'))
                        ->body(__('file_manager.label.sync_notification.body', ['added' => $addedCount, 'removed' => $removedCount]))
                        ->success()
                        ->send();
                }),

            Action::make('createFolder')
                ->label(__('file_manager.label.new_folder'))
                ->tooltip(__('file_manager.label.new_folder'))
                ->hiddenLabel()
                ->icon('heroicon-o-folder-plus')
                ->color('warning')
                ->size('xs')
                ->visible(fn() => auth()->user()->can('create', TouchFile::class))
                ->form(function () {
                    $parentId = $this->parent_id;
                    return [
                        Group::make()
                            ->schema([
                                Section::make(__('file_manager.label.create_folder_section'))
                                    ->schema([
                                        TextInput::make('name')
                                            ->label(__('file_manager.label.folder_name'))
                                            ->required()
                                            ->maxLength(255)
                                            ->rules([
                                                function () use ($parentId) {
                                                    return function (string $attribute, $value, $fail) use ($parentId) {
                                                        // Only restrict reserved names at the root level
                                                        if (!$parentId) {
                                                            $reservedNames = TouchFile::getReservedNames();
                                                            if (in_array(strtolower($value), $reservedNames)) {
                                                                $fail(__('file_manager.errors.reserved_name'));
                                                            }
                                                        }
                                                    };
                                                },
                                            ])
                                            ->extraInputAttributes([
                                                'style' => 'text-transform: lowercase',
                                                'x-on:input' => "\$el.value = \$el.value.toLowerCase().replace(/[çğışıöü]/g, c => ({'ç':'c','ğ':'g','ı':'i','ş':'s','ö':'o','ü':'u'}[c])).replace(/\s+/g, '-').replace(/[^a-z0-9\-_]/g, '').replace(/-+/g, '-'); \$el.dispatchEvent(new Event('input'))",
                                            ])
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn($state, callable $set) => $set('name', Str::slug($state)))
                                            ->placeholder(__('file_manager.label.folder_name_placeholder')),

                                        Hidden::make('parent_id')
                                            ->default($parentId)
                                            ->visible((bool) $parentId),

                                        SelectTree::make('parent_id')
                                            ->label(__('file_manager.label.parent_folder'))
                                            ->relationship('parent', 'name', 'parent_id', function ($query) {
                                                return $query->where('is_folder', true);
                                            }, function ($query) {
                                                return $query->where('is_folder', true);
                                            })
                                            ->enableBranchNode()
                                            ->searchable()
                                            ->placeholder(__('file_manager.label.root'))
                                            ->visible(!$parentId),

                                        Hidden::make('is_folder')
                                            ->default(true),
                                    ])
                                    ->columns(2),
                            ])
                            ->columnSpan(['lg' => 3]),
                    ];
                })
                ->action(function (array $data) {
                    $parentId = $data['parent_id'] ?? $this->parent_id;
                    $name = Str::slug($data['name']);
                    $data['name'] = $name;
                    $data['parent_id'] = $parentId;

                    $path = $name;
                    if ($parentId) {
                        $parent = TouchFile::find($parentId);
                        if ($parent) {
                            $path = $parent->full_path . '/' . $name;
                        }
                    }

                    $data['is_folder'] = true;
                    $data['path'] = $path;

                    $disk = Storage::disk('attachments');
                    if (!$disk->exists($path)) {
                        $disk->makeDirectory($path);
                    }

                    $data['user_id'] = auth()->id();
                    static::getResource()::getModel()::create($data);
                })
                ->successNotificationTitle(__('file_manager.label.folder_created')),

            CreateAction::make()
                ->label(__('file_manager.label.upload_files'))
                ->tooltip(__('file_manager.label.upload_files'))
                ->hiddenLabel()
                ->color('success')
                ->size('xs')
                ->icon('heroicon-o-arrow-up-tray')
                ->url(fn(): string => TouchFileManagerResource::getUrl('create', [
                    'parent_id' => $this->parent_id,
                    'view_type' => $this->view_type,
                    'iframe' => $this->iframe,
                ])),

            $this->getTableSettingsAction(),

            Action::make('toggleView')
                ->label($this->view_type === 'grid' ? __('file_manager.label.list_view') : __('file_manager.label.grid_view'))
                ->tooltip($this->view_type === 'grid' ? __('file_manager.label.list_view') : __('file_manager.label.grid_view'))
                ->hiddenLabel()
                ->icon($this->view_type === 'grid' ? 'heroicon-o-list-bullet' : 'heroicon-o-squares-2x2')
                ->extraAttributes(fn() => [
                    'style' => sprintf(
                        'background-color:%s;color:white;',
                        $this->view_type === 'grid' ? '#d08700' : '#0ea5e9'
                    ),
                ])
                ->size('xs')
                ->action(function () {
                    $newView = $this->view_type === 'grid' ? 'list' : 'grid';
                    // Save via trait method to ensure consistency
                    $this->saveTableSettings(array_merge(
                        ['visible_columns' => $this->visibleColumns],
                        ['view_type' => $newView]
                    ));
                    return redirect(static::getResource()::getUrl('index', [
                        'parent_id' => $this->parent_id,
                        'view_type' => $newView,
                        'iframe' => $this->iframe,
                    ]));
                }),
        ];
    }

    /**
     * Check if a given path is authorized by a model record
     */
    protected function isAuthorizedPath(?string $path): bool
    {
        if (!$path)
            return true;

        $modelConfigs = TouchFile::getDynamicModelAssociations();
        $parts = explode('/', str_replace('\\', '/', $path));
        $rootFolder = $parts[0] ?? null;

        if ($rootFolder && array_key_exists($rootFolder, $modelConfigs)) {
            $modelClass = $modelConfigs[$rootFolder];
            $recordId = $parts[1] ?? null;

            if ($recordId !== null) {
                // If the folder is 'temp', 'content-images', 'thumbs' etc, we skip model validation 
                // but usually those are inside the ID folder.
                // If the second part is NOT numeric (like 'temp' directly in root of model), it's unauthorized
                if (!is_numeric($recordId)) {
                    // Allow certain reserved names directly under model folder if needed, 
                    // but according to user request, everything under model folder must relate to a record.
                    // We already exclude 'temp' and 'thumbs' in the loop before calling this, 
                    // so if we are here, it's likely a record folder or unauthorized file.
                    return false;
                }

                // Check if record exists
                return $modelClass::where('id', $recordId)->exists();
            }
        }

        return true;
    }
}
