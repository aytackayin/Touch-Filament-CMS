<?php

namespace App\Filament\Admin\Resources\Blogs\Schemas;

use App\Models\Blog;
use App\Models\BlogCategory;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class BlogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Content')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (string $operation, $state, $set, $record) {
                                if ($operation !== 'create') {
                                    return;
                                }
                                $set('slug', Blog::generateUniqueSlug($state, $record?->id));
                            }),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set, $record) {
                                $set('slug', Blog::generateUniqueSlug($state, $record?->id));
                            })
                            ->unique(Blog::class, 'slug', ignoreRecord: true),
                        RichEditor::make('content')
                            ->required()
                            ->columnSpanFull(),
                        FileUpload::make('attachments')
                            ->multiple()
                            ->disk('public')
                            ->directory(function ($record) {
                                if ($record) {
                                    return 'attachments/blogs/' . $record->id . '/images';
                                }
                                return 'attachments/blogs/temp';
                            })
                            ->image()
                            ->imageEditor()
                            ->columnSpanFull(),
                    ]),

                Section::make('Settings')
                    ->schema([
                        TextInput::make('sort')
                            ->numeric()
                            ->default(0),
                        Select::make('language_id')
                            ->relationship('language', 'name')
                            ->required()
                            ->default(function () {
                                if ($categoryId = request()->query('category_id')) {
                                    $category = BlogCategory::find($categoryId);
                                    if ($category) {
                                        return $category->language_id;
                                    }
                                }
                                return \App\Models\Language::where('is_default', true)->first()?->id;
                            })
                            ->live()
                            ->afterStateUpdated(fn($set) => $set('categories', [])),
                        Select::make('categories')
                            ->relationship('categories', 'title', function ($query, $get) {
                                $languageId = $get('language_id');
                                if ($languageId) {
                                    $query->where('language_id', $languageId);
                                } else {
                                    $query->whereRaw('1 = 0');
                                }
                            })
                            ->multiple()
                            ->preload()
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
                            ->required()
                            ->default(true),
                        DateTimePicker::make('publish_start'),
                        DateTimePicker::make('publish_end'),
                    ]),
            ]);
    }
}
