<?php

namespace App\Filament\Admin\Resources\BlogCategories\Schemas;

use App\Models\BlogCategory;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class BlogCategoryForm
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
                                $slug = BlogCategory::generateUniqueSlug($state, $record?->id);
                                $set('slug', $slug);
                            }),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set, $record) {
                                $slug = BlogCategory::generateUniqueSlug($state, $record?->id);
                                $set('slug', $slug);
                            })
                            ->unique(BlogCategory::class, 'slug', ignoreRecord: true),
                        Textarea::make('description')
                            ->maxLength(65535)
                            ->columnSpanFull(),
                        FileUpload::make('attachments')
                            ->multiple()
                            ->disk('public')
                            ->directory(function ($record) {
                                if ($record) {
                                    return 'attachments/blog_categories/' . $record->id . '/images';
                                }
                                return 'attachments/blog_categories/temp';
                            })
                            ->image()
                            ->imageEditor()
                            ->columnSpanFull(),
                    ]),

                Section::make('Settings')
                    ->schema([
                        Select::make('parent_id')
                            ->relationship('parent', 'title', function (Builder $query, ?BlogCategory $record) {
                                if ($record) {
                                    $query->where('id', '!=', $record->id);
                                }
                            })
                            ->searchable()
                            ->preload()
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state) {
                                    $parent = BlogCategory::find($state);
                                    if ($parent) {
                                        $set('language_id', $parent->language_id);
                                    }
                                }
                            }),
                        Select::make('language_id')
                            ->relationship('language', 'name')
                            ->required()
                            ->default(function () {
                                return \App\Models\Language::where('is_default', true)->first()?->id;
                            })
                            ->live()
                            ->afterStateUpdated(function ($state, $set) {
                                // Logic to update other fields if needed
                            }),
                        TextInput::make('sort')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_published')
                            ->required(),
                        DateTimePicker::make('publish_start'),
                        DateTimePicker::make('publish_end'),
                    ]),
            ]);
    }
}
