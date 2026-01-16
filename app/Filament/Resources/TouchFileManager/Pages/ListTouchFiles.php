<?php

namespace App\Filament\Resources\TouchFileManager\Pages;

use App\Filament\Resources\TouchFileManager\TouchFileManagerResource;
use App\Filament\Resources\TouchFileManager\Schemas\TouchFileForm;
use App\Models\TouchFile;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;

class ListTouchFiles extends ListRecords
{
    protected static string $resource = TouchFileManagerResource::class;

    #[\Livewire\Attributes\Url]
    public ?string $parent_id = null;

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            static::getResource()::getUrl() => static::getResource()::getBreadcrumb(),
        ];

        if ($this->parent_id) {
            $folder = TouchFile::find($this->parent_id);
            $trail = [];
            while ($folder) {
                array_unshift($trail, [
                    'url' => static::getResource()::getUrl('index', ['parent_id' => $folder->id]),
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
            $upParams = [];
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
                        \Filament\Schemas\Components\Group::make()
                            ->schema([
                                \Filament\Schemas\Components\Section::make('Create New Folder')
                                    ->schema([
                                        \Filament\Forms\Components\TextInput::make('name')
                                            ->label('Folder Name')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('e.g., Documents'),

                                        \Filament\Forms\Components\Hidden::make('parent_id')
                                            ->default($parentId)
                                            ->visible((bool) $parentId),

                                        \Filament\Forms\Components\Select::make('parent_id')
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

                                        \Filament\Forms\Components\Hidden::make('is_folder')
                                            ->default(true),
                                    ])
                                    ->columns(2),
                            ])
                            ->columnSpan(['lg' => 3]),
                    ];
                })
                ->action(function (array $data) {
                    $parentId = $data['parent_id'] ?? $this->parent_id;
                    $name = \Illuminate\Support\Str::slug($data['name']);
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

                    $disk = \Illuminate\Support\Facades\Storage::disk('attachments');
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
                ])),
        ];
    }
}
