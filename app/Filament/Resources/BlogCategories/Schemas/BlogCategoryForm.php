<?php

namespace App\Filament\Resources\BlogCategories\Schemas;

use App\Models\BlogCategory;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\DateTimePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Components\Group;
use App\Models\Language;
use CodeWithDennis\FilamentSelectTree\SelectTree;

class BlogCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Group::make()
                    ->schema([
                        Section::make('')
                            ->schema([
                                TextInput::make('title')
                                    ->label(__('blog.label.title'))
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
                                    ->label(__('blog.label.slug'))
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function ($state, $set, $record) {
                                        $slug = BlogCategory::generateUniqueSlug($state, $record?->id);
                                        $set('slug', $slug);
                                    })
                                    ->unique(BlogCategory::class, 'slug', ignoreRecord: true),
                                Textarea::make('description')
                                    ->label(__('blog.label.description'))
                                    ->maxLength(65535)
                                    ->columnSpanFull(),
                                TagsInput::make('tags')
                                    ->label(__('blog.label.tags'))
                                    ->columnSpanFull(),
                                FileUpload::make('attachments')
                                    ->label(__('blog.label.attachments'))
                                    ->multiple()
                                    ->panelLayout('grid')
                                    ->disk('attachments')
                                    ->directory(function ($record) {
                                        if ($record) {
                                            return 'blog_categories/' . $record->id . '/images';
                                        }
                                        return 'blog_categories/temp';
                                    })
                                    ->image()
                                    ->imageEditor()
                                    ->enableReordering()
                                    ->columnSpanFull(),
                            ])->columns(2),
                    ])
                    ->columnSpan(['lg' => 2]),
                Group::make()
                    ->schema([
                        Section::make('')
                            ->schema([
                                SelectTree::make('parent_id')
                                    ->label(__('blog.label.parent_category'))
                                    ->relationship('parent', 'title', 'parent_id', function (Builder $query, ?BlogCategory $record) {
                                        if ($record) {
                                            $query->where('id', '!=', $record->id);
                                        }
                                        return $query;
                                    })
                                    ->enableBranchNode()
                                    ->searchable()
                                    ->live()
                                    ->default(request()->query('parent_id'))
                                    ->afterStateUpdated(function ($state, $set) {
                                        if ($state) {
                                            $parent = BlogCategory::find($state);
                                            if ($parent) {
                                                $set('language_id', $parent->language_id);
                                            }
                                        }
                                    }),
                                Select::make('language_id')
                                    ->label(__('blog.label.language'))
                                    ->relationship('language', 'name')
                                    ->required()
                                    // If parent_id exists (via request or state), prefer parent's language.
                                    // Otherwise default language.
                                    ->default(function () {
                                        if ($parentId = request()->query('parent_id')) {
                                            $parent = BlogCategory::find($parentId);
                                            if ($parent) {
                                                return $parent->language_id;
                                            }
                                        }
                                        return Language::where('is_default', true)->first()?->id;
                                    })
                                    ->live()
                                    ->disabled(fn($get) => filled($get('parent_id')))
                                    ->dehydrated()
                                    ->afterStateUpdated(function ($state, $set) {
                                        // Logic to update other fields if needed
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
