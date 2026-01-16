<?php

namespace App\Filament\Resources\TouchFileManager\Schemas;

use App\Models\TouchFile;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

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
                                    ->visible(fn($operation) => $operation === 'edit'),

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
                                    ->helperText('Select a parent folder or leave empty for root directory'),

                                FileUpload::make('files')
                                    ->label('Upload Files')
                                    ->multiple()
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
                                        'application/x-rar-compressed',
                                        'application/x-7z-compressed',
                                        'text/plain',
                                    ])
                                    ->imageEditor()
                                    ->imageEditorAspectRatios([
                                        null,
                                        '16:9',
                                        '4:3',
                                        '1:1',
                                    ])
                                    ->enableReordering()
                                    ->preserveFilenames()
                                    ->getUploadedFileNameForStorageUsing(
                                        fn(TemporaryUploadedFile $file): string =>
                                        (string) str($file->getClientOriginalName())
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
                                        '4:3',
                                        '1:1',
                                    ])
                                    ->preserveFilenames()
                                    ->getUploadedFileNameForStorageUsing(
                                        fn(TemporaryUploadedFile $file): string =>
                                        (string) str($file->getClientOriginalName())
                                            ->beforeLast('.')
                                            ->slug()
                                            ->append('.' . $file->getClientOriginalExtension())
                                    )
                                    ->hidden(fn($operation) => $operation === 'create')
                                    ->visible(fn($record) => $record && !$record->is_folder)
                                    ->columnSpanFull(),

                                Hidden::make('video_thumbnails_store')
                                    ->dehydrated(),

                                \Filament\Forms\Components\Placeholder::make('video_thumbnail_handler')
                                    ->hiddenLabel()
                                    ->view('filament.forms.components.video-thumbnail-handler'),

                                Hidden::make('is_folder')
                                    ->default(false)
                                    ->dehydrated(),
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
                            ->placeholder('e.g., Documents, Images, Videos'),

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
                            ->helperText('Select a parent folder or leave empty for root directory'),

                        Hidden::make('is_folder')
                            ->default(true)
                            ->dehydrated(),
                    ])
                    ->columns(2),
            ]);
    }
}
