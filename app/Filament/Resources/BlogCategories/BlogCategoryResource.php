<?php

namespace App\Filament\Resources\BlogCategories;

use App\Filament\Resources\BlogCategories\Pages\CreateBlogCategory;
use App\Filament\Resources\BlogCategories\Pages\EditBlogCategory;
use App\Filament\Resources\BlogCategories\Pages\ListBlogCategories;
use App\Filament\Resources\BlogCategories\Schemas\BlogCategoryForm;
use App\Filament\Resources\BlogCategories\Tables\BlogCategoriesTable;
use App\Models\BlogCategory;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use BackedEnum;

class BlogCategoryResource extends Resource
{
    protected static ?string $model = BlogCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'description'];
    }
    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return '📁 ' . $record->title;
    }
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $description = strip_tags($record->description ?? '');

        // İlk cümleyi al
        $firstSentence = Str::of($description)
            ->explode('.')
            ->first();

        return [
            'Açıklama' => new HtmlString('<span style="font-size: 12px; line-height: 1;">' . Str::limit($firstSentence, 120) . '</span>'),
        ];
    }
    public static function getNavigationGroup(): ?string
    {
        return 'Blog';
    }
    protected static ?int $navigationSort = 1;
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
