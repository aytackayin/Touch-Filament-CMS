<?php

namespace App\Filament\Resources\TouchFileManager\Pages;

use App\Filament\Resources\TouchFileManager\TouchFileManagerResource;
use App\Filament\Resources\TouchFileManager\Schemas\TouchFileForm;
use App\Models\TouchFile;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

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

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            static::getResource()::getUrl('index', ['view_type' => $this->view_type]) => static::getResource()::getBreadcrumb(),
        ];

        if ($this->parent_id) {
            $folder = TouchFile::find($this->parent_id);
            $trail = [];
            while ($folder) {
                array_unshift($trail, [
                    'url' => static::getResource()::getUrl('index', [
                        'parent_id' => $folder->id,
                        'view_type' => $this->view_type
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
            $upParams = ['view_type' => $this->view_type];
            if ($currentFolder->parent_id) {
                $upParams['parent_id'] = $currentFolder->parent_id;
            }
            $upUrl = TouchFileManagerResource::getUrl('index', $upParams);
        }

        return [
            Actions\Action::make('up')
                ->label('Up')
                ->icon('heroicon-o-arrow-uturn-left')
                ->color('gray')
                ->visible((bool) $this->parent_id)
                ->url($upUrl)
                ->size('xs'),

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
                                            ->placeholder('e.g., Documents'),

                                        Hidden::make('parent_id')
                                            ->default($parentId)
                                            ->visible((bool) $parentId),

                                        Select::make('parent_id')
                                            ->label('Parent Folder')
                                            ->options(function () {
                                                return TouchFile::where('is_folder', true)
                                                    ->orderBy('name')
                                                    ->get()
                                                    ->mapWithKeys(function ($folder) {
                                                        return [$folder->id => $folder->full_path];
                                                    });
                                            })
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

            Actions\CreateAction::make()
                ->label('Upload Files')
                ->tooltip('Upload new files')
                ->color('success')
                ->size('xs')
                ->icon('heroicon-o-arrow-up-tray')
                ->url(fn(): string => TouchFileManagerResource::getUrl('create', [
                    'parent_id' => $this->parent_id,
                    'view_type' => $this->view_type,
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
                    ]));
                }),
        ];
    }
}
