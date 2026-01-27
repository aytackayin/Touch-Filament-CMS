<?php

namespace App\Filament\Resources\TouchFileManager\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TagsInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\Str;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Illuminate\Database\Eloquent\Model;
use App\Models\TouchFile;

class TouchFileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make(__('file_manager.label.info_section'))
                            ->schema([
                                TextInput::make('name')
                                    ->label(__('file_manager.label.name'))
                                    ->required()
                                    ->maxLength(255)
                                    ->rules([
                                        function ($get) {
                                            return function (string $attribute, $value, $fail) use ($get) {
                                                if (!$get('parent_id')) {
                                                    $reservedNames = TouchFile::getReservedNames();
                                                    if (in_array(strtolower($value), $reservedNames)) {
                                                        $fail(__('file_manager.errors.reserved_name'));
                                                    }
                                                }
                                            };
                                        },
                                    ])
                                    ->extraInputAttributes(fn($record) => [
                                        'style' => 'text-transform: lowercase',
                                        'x-on:input' => $record?->is_folder
                                            ? "\$el.value = \$el.value.toLowerCase().replace(/[çğışıöü]/g, c => ({'ç':'c','ğ':'g','ı':'i','ş':'s','ö':'o','ü':'u'}[c])).replace(/\s+/g, '-').replace(/[^a-z0-9\-_]/g, '').replace(/-+/g, '-'); \$el.dispatchEvent(new Event('input'))"
                                            : "\$el.value = \$el.value.toLowerCase().replace(/[çğışıöü]/g, c => ({'ç':'c','ğ':'g','ı':'i','ş':'s','ö':'o','ü':'u'}[c])).replace(/\s+/g, '-').replace(/[^a-z0-9\-_.]/g, '').replace(/-+/g, '-'); \$el.dispatchEvent(new Event('input'))",
                                    ])
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, $set, $record) {
                                        if (!$state)
                                            return;
                                        if ($record?->is_folder) {
                                            $set('name', Str::slug($state));
                                        } else {
                                            $ext = pathinfo($state, PATHINFO_EXTENSION);
                                            $name = pathinfo($state, PATHINFO_FILENAME);
                                            $slugged = Str::slug($name);
                                            $result = $ext ? $slugged . '.' . $ext : $slugged;
                                            $set('name', strtolower($result));
                                        }
                                    })
                                    ->visible(fn($operation) => $operation === 'edit'),


                                FileUpload::make('files')
                                    ->label(__('file_manager.label.upload_files'))
                                    ->multiple()
                                    ->panelLayout('grid')
                                    ->disk('attachments')
                                    ->directory('temp')
                                    ->acceptedFileTypes([
                                        'image/*',
                                        'video/*',
                                        'application/pdf',
                                        'application/msword',
                                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                        'application/vnd.ms-excel',
                                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                        'application/vnd.ms-powerpoint',
                                        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                                        'application/zip',
                                        'application/x-zip-compressed',
                                        'multipart/x-zip',
                                        'application/x-rar-compressed',
                                        'application/vnd.rar',
                                        'application/x-7z-compressed',
                                        'text/plain',
                                    ])
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        null,
                                        '16:9',
                                        '9:16',
                                        '4:3',
                                        '3:4',
                                        '1:1',
                                    ])
                                    ->enableReordering()
                                    ->preserveFilenames()
                                    ->getUploadedFileNameForStorageUsing(
                                        fn(TemporaryUploadedFile $file): string =>
                                        (string) Str::of($file->getClientOriginalName())
                                            ->beforeLast('.')
                                            ->slug()
                                            ->append('.' . $file->getClientOriginalExtension())
                                    )
                                    ->maxSize(102400) // 100MB
                                    ->helperText(__('file_manager.label.upload_helper_text'))
                                    ->hidden(fn($operation) => $operation === 'edit')
                                    ->columnSpanFull(),

                                FileUpload::make('path')
                                    ->label(__('file_manager.label.file'))
                                    ->disk('attachments')
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        null,
                                        '16:9',
                                        '9:16',
                                        '4:3',
                                        '3:4',
                                        '1:1',
                                    ])
                                    ->preserveFilenames()
                                    ->acceptedFileTypes(function (?Model $record) {
                                        if (!$record) {
                                            return [];
                                        }

                                        $ext = strtolower(pathinfo($record->path, PATHINFO_EXTENSION));

                                        $types = [
                                            'jpg' => ['image/jpeg'],
                                            'jpeg' => ['image/jpeg'],
                                            'png' => ['image/png'],
                                            'gif' => ['image/gif'],
                                            'webp' => ['image/webp'],
                                            'svg' => ['image/svg+xml'],
                                            'mp4' => ['video/mp4'],
                                            'pdf' => ['application/pdf'],
                                            'doc' => ['application/msword'],
                                            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
                                            'xls' => ['application/vnd.ms-excel'],
                                            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
                                            'ppt' => ['application/vnd.ms-powerpoint'],
                                            'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation'],
                                            'zip' => ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip'],
                                            'rar' => ['application/x-rar-compressed', 'application/vnd.rar'],
                                            '7z' => ['application/x-7z-compressed'],
                                            'txt' => ['text/plain'],
                                        ];

                                        return $types[$ext] ?? [];
                                    })
                                    ->getUploadedFileNameForStorageUsing(
                                        function (TemporaryUploadedFile $file, ?Model $record): string {
                                            if ($record) {
                                                return $record->path;
                                            }

                                            return (string) Str::of($file->getClientOriginalName())
                                                ->beforeLast('.')
                                                ->slug()
                                                ->append('.' . $file->getClientOriginalExtension());
                                        }
                                    )
                                    ->hidden(fn($operation) => $operation === 'create')
                                    ->visible(fn($record) => $record && !$record->is_folder)
                                    ->columnSpanFull(),

                                Hidden::make('video_thumbnails_store')
                                    ->dehydrated(),

                                Placeholder::make('video_thumbnail_handler')
                                    ->hiddenLabel()
                                    ->view('filament.forms.components.video-thumbnail-handler'),

                                Hidden::make('is_folder')
                                    ->default(false)
                                    ->dehydrated(),

                                TagsInput::make('tags')
                                    ->label(__('file_manager.label.tags'))
                                    ->columnSpanFull(),

                                SelectTree::make('parent_id')
                                    ->label(__('file_manager.label.parent_folder'))
                                    ->relationship('parent', 'name', 'parent_id', function ($query) {
                                        return $query->where('is_folder', true);
                                    }, function ($query) {
                                        return $query->where('is_folder', true);
                                    })
                                    ->enableBranchNode()
                                    ->searchable()
                                    ->default(fn() => request('parent_id'))
                                    ->visible(function ($livewire, $operation) {
                                        if ($operation === 'edit')
                                            return true;
                                        // If parent_id is set in Livewire component (CreateTouchFile) or Request, hide it
                                        if (isset($livewire->parent_id) && $livewire->parent_id) {
                                            return false;
                                        }
                                        return !request()->filled('parent_id');
                                    })
                                    ->dehydrated()
                                    ->placeholder(__('file_manager.label.root')),

                                Textarea::make('alt')
                                    ->label(__('file_manager.label.description_alt'))
                                    ->placeholder(__('file_manager.label.alt_placeholder'))
                                    ->maxLength(255),
                            ])
                            ->columns(2),
                    ])
                    ->columnSpan(['lg' => 3]),
            ])
            ->columns(3);
    }

    public static function folderSchema(): Schema
    {
        return Schema::make()
            ->components([
                Section::make(__('file_manager.label.create_folder_section'))
                    ->schema([
                        TextInput::make('name')
                            ->label(__('file_manager.label.folder_name'))
                            ->required()
                            ->maxLength(255)
                            ->rules([
                                function ($get) {
                                    return function (string $attribute, $value, $fail) use ($get) {
                                        if (!$get('parent_id')) {
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

                        SelectTree::make('parent_id')
                            ->label(__('file_manager.label.parent_folder'))
                            ->relationship('parent', 'name', 'parent_id', function ($query) {
                                return $query->where('is_folder', true);
                            }, function ($query) {
                                return $query->where('is_folder', true);
                            })
                            ->enableBranchNode()
                            ->searchable()
                            ->placeholder(__('file_manager.label.root')),

                        Hidden::make('is_folder')
                            ->default(true)
                            ->dehydrated(),
                    ])
                    ->columns(2),
            ]);
    }
}
