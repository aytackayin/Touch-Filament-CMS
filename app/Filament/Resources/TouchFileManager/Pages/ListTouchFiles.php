<?php

namespace App\Filament\Resources\TouchFileManager\Pages;

use App\Filament\Resources\TouchFileManager\TouchFileManagerResource;
use App\Filament\Resources\TouchFileManager\Schemas\TouchFileForm;
use App\Models\TouchFile;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Notifications\Notification;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
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
                ->label('Up')
                ->icon('heroicon-m-arrow-uturn-up')
                ->color('gray')
                ->visible((bool) $this->parent_id)
                ->url($upUrl)
                ->size('xs'),

            Action::make('sync')
                ->label('Sync Files')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->size('xs')
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
                        ->title('Sync Completed')
                        ->body("Added {$addedCount} items. Removed {$removedCount} orphaned items.")
                        ->success()
                        ->send();
                }),

            Action::make('createFolder')
                ->label('New Folder')
                ->icon('heroicon-o-folder-plus')
                ->color('warning')
                ->size('xs')
                ->form(function () {
                    $parentId = $this->parent_id;
                    return [
                        Group::make()
                            ->schema([
                                Section::make('Create New Folder')
                                    ->schema([
                                        TextInput::make('name')
                                            ->label('Folder Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->notIn(['thumbs', 'temp'])
                                            ->validationMessages([
                                                'not_in' => 'The name ":input" is reserved and cannot be used.',
                                            ])
                                            ->extraInputAttributes([
                                                'style' => 'text-transform: lowercase',
                                                'x-on:input' => "\$el.value = \$el.value.toLowerCase().replace(/[çğışıöü]/g, c => ({'ç':'c','ğ':'g','ı':'i','ş':'s','ö':'o','ü':'u'}[c])).replace(/\s+/g, '-').replace(/[^a-z0-9\-_]/g, '').replace(/-+/g, '-'); \$el.dispatchEvent(new Event('input'))",
                                            ])
                                            ->live(onBlur: true)
                                            ->afterStateUpdated(fn($state, callable $set) => $set('name', Str::slug($state)))
                                            ->placeholder('e.g., Documents'),

                                        Hidden::make('parent_id')
                                            ->default($parentId)
                                            ->visible((bool) $parentId),

                                        SelectTree::make('parent_id')
                                            ->label('Parent Folder')
                                            ->relationship('parent', 'name', 'parent_id', function ($query) {
                                                return $query->where('is_folder', true);
                                            }, function ($query) {
                                                return $query->where('is_folder', true);
                                            })
                                            ->enableBranchNode()
                                            ->searchable()
                                            ->placeholder('Root (attachments)')
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

                    static::getResource()::getModel()::create($data);
                })
                ->successNotificationTitle('Folder created successfully'),

            CreateAction::make()
                ->label('Upload Files')
                ->tooltip('Upload new files')
                ->color('success')
                ->size('xs')
                ->icon('heroicon-o-arrow-up-tray')
                ->url(fn(): string => TouchFileManagerResource::getUrl('create', [
                    'parent_id' => $this->parent_id,
                    'view_type' => $this->view_type,
                    'iframe' => $this->iframe,
                ])),

            Action::make('toggleView')
                ->label($this->view_type === 'grid' ? 'List View' : 'Grid View')
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
