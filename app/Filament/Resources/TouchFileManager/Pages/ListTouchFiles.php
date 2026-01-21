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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use CodeWithDennis\FilamentSelectTree\SelectTree;

class ListTouchFiles extends ListRecords
{
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
                ->icon('heroicon-m-arrow-uturn-up')
                ->color('gray')
                ->visible((bool) $this->parent_id)
                ->url($upUrl)
                ->size('xs'),

            Action::make('sync')
                ->label(__('file_manager.label.sync_files'))
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->size('xs')
                ->visible(fn() => auth()->user()->can('sync', TouchFile::class))
                ->action(function () {
                    $disk = Storage::disk('attachments');
                    $allFiles = $disk->allFiles();
                    $allDirectories = $disk->allDirectories();

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

                    // 1. Process Directories (Add Missing)
                    usort($allDirectories, function ($a, $b) {
                        return strlen($a) - strlen($b);
                    });

                    foreach ($allDirectories as $dirPath) {
                        if ($isExcluded($dirPath))
                            continue;

                        $existing = TouchFile::where('is_folder', true)
                            ->where('path', $dirPath)
                            ->exists();

                        if (!$existing) {
                            $name = basename($dirPath);
                            $parentPath = dirname($dirPath);
                            $parentId = null;

                            if ($parentPath !== '.') {
                                $parent = TouchFile::where('is_folder', true)->where('path', $parentPath)->first();
                                if ($parent) {
                                    $parentId = $parent->id;
                                }
                            }

                            TouchFile::create([
                                'user_id' => auth()->id(),
                                'name' => $name,
                                'path' => $dirPath,
                                'is_folder' => true,
                                'parent_id' => $parentId,
                            ]);

                            $addedCount++;
                        }
                    }

                    // 2. Process Files (Add Missing)
                    foreach ($allFiles as $filePath) {
                        if ($isExcluded($filePath))
                            continue;

                        $existing = TouchFile::where('is_folder', false)
                            ->where('path', $filePath)
                            ->exists();

                        if (!$existing) {
                            $name = basename($filePath);
                            $parentPath = dirname($filePath);
                            $parentId = null;

                            if ($parentPath !== '.') {
                                $parent = TouchFile::where('is_folder', true)->where('path', $parentPath)->first();
                                if ($parent) {
                                    $parentId = $parent->id;
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
                                'parent_id' => $parentId,
                                'mime_type' => $mimeType,
                                'size' => $size,
                                'type' => $type,
                            ]);

                            $addedCount++;
                        }
                    }

                    // 3. Cleanup Orphaned Records
                    // Check Files First
                    $files = TouchFile::where('is_folder', false)->get();
                    foreach ($files as $file) {
                        if (!$disk->exists($file->path)) {
                            $file->delete();
                            $removedCount++;
                        }
                    }

                    // Check Folders Next
                    $folders = TouchFile::where('is_folder', true)->get();
                    foreach ($folders as $folder) {
                        // Skip if already deleted (by parent recursive delete)
                        if (TouchFile::where('id', $folder->id)->doesntExist()) {
                            continue;
                        }

                        if (!$disk->exists($folder->path)) {
                            $folder->delete(); // This triggers recursive delete of children if any remain
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
                                            ->notIn(['thumbs', 'temp'])
                                            ->validationMessages([
                                                'not_in' => __('file_manager.errors.reserved_name'),
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
                ->tooltip(__('file_manager.label.upload_new_files'))
                ->color('success')
                ->size('xs')
                ->icon('heroicon-o-arrow-up-tray')
                ->url(fn(): string => TouchFileManagerResource::getUrl('create', [
                    'parent_id' => $this->parent_id,
                    'view_type' => $this->view_type,
                    'iframe' => $this->iframe,
                ])),

            Action::make('toggleView')
                ->label($this->view_type === 'grid' ? __('file_manager.label.list_view') : __('file_manager.label.grid_view'))
                ->icon($this->view_type === 'grid' ? 'heroicon-o-list-bullet' : 'heroicon-o-squares-2x2')
                ->color('gray')
                ->size('xs')
                ->action(function () {
                    $newView = $this->view_type === 'grid' ? 'list' : 'grid';

                    return redirect(static::getResource()::getUrl('index', [
                        'parent_id' => $this->parent_id,
                        'view_type' => $newView,
                        'iframe' => $this->iframe,
                    ]));
                }),
        ];
    }
}
