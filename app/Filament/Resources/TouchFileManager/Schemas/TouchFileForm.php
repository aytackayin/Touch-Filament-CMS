<?php

namespace App\Filament\Resources\TouchFileManager\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\Str;
use CodeWithDennis\FilamentSelectTree\SelectTree;
use Illuminate\Database\Eloquent\Model;

class TouchFileForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('File/Folder Information')
                            ->schema([
                                TextInput::make('name')
                                    ->label('Name')
                                    ->required()
                                    ->maxLength(255)
                                    ->notIn(['thumbs', 'temp'])
                                    ->validationMessages([
                                        'not_in' => 'The name ":input" is reserved and cannot be used.',
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
                                    ->label('Upload Files')
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
                                    ->helperText('Supported: Images, Videos, Documents (PDF, Word, Excel, PowerPoint), Archives (ZIP, RAR, 7Z), Text files. Max: 100MB per file')
                                    ->hidden(fn($operation) => $operation === 'edit')
                                    ->columnSpanFull(),

                                FileUpload::make('path')
                                    ->label('File')
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

                                \Filament\Forms\Components\TagsInput::make('tags')
                                    ->columnSpanFull(),

                                SelectTree::make('parent_id')
                                    ->label('Parent Folder')
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
                                    ->placeholder('Root (attachments)')
                                    ->helperText('Select a parent folder or leave empty for root directory'),

                                Textarea::make('alt')
                                    ->label('Description (Alt)')
                                    ->placeholder('File description...')
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
                Section::make('Create New Folder')
                    ->schema([
                        TextInput::make('name')
                            ->label('Folder Name')
                            ->required()
                            ->maxLength(255)
                            ->notIn(['thumbs', 'temp'])
                            ->validationMessages([
                                'not_in' => 'The name ":input" is reserved and cannot be used.',
                                'slug' => 'Only slug-friendly characters are allowed.',
                            ])
                            ->extraInputAttributes([
                                'style' => 'text-transform: lowercase',
                                'x-on:input' => "\$el.value = \$el.value.toLowerCase().replace(/[çğışıöü]/g, c => ({'ç':'c','ğ':'g','ı':'i','ş':'s','ö':'o','ü':'u'}[c])).replace(/\s+/g, '-').replace(/[^a-z0-9\-_]/g, '').replace(/-+/g, '-'); \$el.dispatchEvent(new Event('input'))",
                            ])
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn($state, callable $set) => $set('name', Str::slug($state)))
                            ->placeholder('e.g., Documents, Images, Videos'),

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
                            ->helperText('Select a parent folder or leave empty for root directory'),

                        Hidden::make('is_folder')
                            ->default(true)
                            ->dehydrated(),
                    ])
                    ->columns(2),
            ]);
    }
}
