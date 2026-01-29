<?php

namespace App\Filament\Resources\Blogs\Schemas;

use App\Models\Blog;
use App\Models\BlogCategory;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TagsInput;
use Filament\Schemas\Schema;
use App\Models\Language;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Aytackayin\Tinymce\Forms\Components\TinyEditor;
use CodeWithDennis\FilamentSelectTree\SelectTree;

class BlogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                TextInput::make('title')
                                    ->label(__('blog.label.title'))
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, $set, $record) {
                                        $set('slug', Blog::generateUniqueSlug($state, $record?->id));
                                    }),
                                TextInput::make('slug')
                                    ->label(__('blog.label.slug'))
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, $set, $record) {
                                        $set('slug', Blog::generateUniqueSlug($state, $record?->id));
                                    })
                                    ->unique(Blog::class, 'slug', ignoreRecord: true),
                                TinyEditor::make('content')
                                    ->label(__('blog.label.content'))
                                    ->fileAttachmentsDisk('attachments')
                                    ->fileAttachmentsDirectory(fn() => Blog::getStorageFolder() . '/temp/' . (auth()->id() ?? 'guest') . '/content-images')
                                    ->fileAttachmentsVisibility('public')
                                    ->columnSpanFull()
                                    ->required(),
                                TagsInput::make('tags')
                                    ->label(__('blog.label.tags'))
                                    ->columnSpanFull(),
                                FileUpload::make('attachments')
                                    ->label(__('blog.label.attachments'))
                                    ->multiple()
                                    ->panelLayout('grid')
                                    ->disk('attachments')
                                    ->directory(Blog::getStorageFolder() . '/temp')
                                    ->acceptedFileTypes(config('blog.accepted_file_types'))
                                    ->helperText(function () {
                                        $types = config('blog.accepted_file_types', []);
                                        $labels = collect($types)->map(function ($mime) {
                                            $key = match (true) {
                                                str_starts_with($mime, 'image/') => 'image',
                                                str_starts_with($mime, 'video/') => 'video',
                                                str_starts_with($mime, 'audio/') => 'audio',
                                                $mime === 'application/pdf' => 'pdf',
                                                in_array($mime, ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']) => 'word',
                                                in_array($mime, ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']) => 'excel',
                                                in_array($mime, ['application/vnd.ms-powerpoint', 'application/vnd.openxmlformats-officedocument.presentationml.presentation']) => 'powerpoint',
                                                in_array($mime, ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip', 'application/x-rar-compressed', 'application/vnd.rar', 'application/x-7z-compressed']) => 'archive',
                                                $mime === 'text/plain' => 'text',
                                                $mime === 'application/json' => 'json',
                                                $mime === 'text/csv' => 'csv',
                                                in_array($mime, ['text/xml', 'application/xml']) => 'xml',
                                                default => null,
                                            };
                                            return $key ? __('blog.label.types.' . $key) : null;
                                        })->filter()->unique()->values()->implode(', ');

                                        return __('blog.label.supported_formats') . ': ' . $labels;
                                    })
                                    ->imageEditor()
                                    ->enableReordering()
                                    ->preserveFilenames()
                                    ->getUploadedFileNameForStorageUsing(
                                        fn(TemporaryUploadedFile $file): string =>
                                        (string) str($file->getClientOriginalName())
                                            ->beforeLast('.')
                                            ->slug()
                                            ->append('.' . $file->getClientOriginalExtension())
                                    )
                                    ->columnSpanFull(),
                                Hidden::make('video_thumbnails_store')
                                    ->dehydrated(),
                                Placeholder::make('video_thumbnail_handler')
                                    ->hiddenLabel()
                                    ->view('filament.resources.blog.forms.components.video-thumbnail-handler'),
                            ])->columns(2),
                    ])
                    ->columnSpan(['lg' => 2]),
                Group::make()
                    ->schema([
                        Section::make()
                            ->schema([
                                Select::make('language_id')
                                    ->label(__('blog.label.language'))
                                    ->relationship('language', 'name')
                                    ->required()
                                    ->default(function () {
                                        if ($categoryId = request()->query('category_id')) {
                                            $category = BlogCategory::find($categoryId);
                                            if ($category) {
                                                return $category->language_id;
                                            }
                                        }
                                        return Language::where('is_default', true)->first()?->id;
                                    })
                                    ->live()
                                    ->afterStateUpdated(fn($set) => $set('categories', [])),
                                SelectTree::make('categories')
                                    ->label(__('blog.label.categories'))
                                    ->relationship('categories', 'title', 'parent_id', function ($query, $get) {
                                        $languageId = $get('language_id');
                                        if ($languageId) {
                                            $query->where('language_id', $languageId);
                                        } else {
                                            $query->whereRaw('1 = 0');
                                        }
                                        return $query;
                                    })
                                    ->enableBranchNode()
                                    ->searchable()
                                    ->live()
                                    ->default(function () {
                                        if ($categoryId = request()->query('category_id')) {
                                            return [(int) $categoryId];
                                        }
                                        return [];
                                    })
                                    ->disabled(fn($get) => blank($get('language_id')))
                                    ->afterStateUpdated(function ($state, $set) {
                                        if (!empty($state)) {
                                            $firstCatId = is_array($state) ? $state[0] : $state;
                                            $category = BlogCategory::find($firstCatId);
                                            if ($category) {
                                                $set('language_id', $category->language_id);
                                            }
                                        }
                                    }),
                                Toggle::make('is_published')
                                    ->label(__('blog.label.is_published'))
                                    ->required()
                                    ->default(true),
                                DateTimePicker::make('publish_start')
                                    ->label(__('blog.label.publish_start')),
                                DateTimePicker::make('publish_end')
                                    ->label(__('blog.label.publish_end')),
                            ]),
                    ])
                    ->columnSpan(['lg' => 1]),
            ])->columns(3);
    }
}
