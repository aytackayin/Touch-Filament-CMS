<?php

namespace App\Filament\Admin\Resources\BlogCategories;

use App\Filament\Admin\Resources\BlogCategories\Pages\CreateBlogCategory;
use App\Filament\Admin\Resources\BlogCategories\Pages\EditBlogCategory;
use App\Filament\Admin\Resources\BlogCategories\Pages\ListBlogCategories;
use App\Filament\Admin\Resources\BlogCategories\Schemas\BlogCategoryForm;
use App\Filament\Admin\Resources\BlogCategories\Tables\BlogCategoriesTable;
use App\Models\BlogCategory;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

use BackedEnum;

class BlogCategoryResource extends Resource
{
    protected static ?string $model = BlogCategory::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getNavigationGroup(): ?string
    {
        return 'Blog';
    }

    public static function form(Schema $schema): Schema
    {
        return BlogCategoryForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlogCategoriesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlogCategories::route('/'),
            'create' => CreateBlogCategory::route('/create'),
            'edit' => EditBlogCategory::route('/{record}/edit'),
        ];
    }
}
