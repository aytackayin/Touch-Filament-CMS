<?php

namespace App\Filament\Resources\Blogs;

use App\Filament\Resources\Blogs\Pages\CreateBlog;
use App\Filament\Resources\Blogs\Pages\EditBlog;
use App\Filament\Resources\Blogs\Pages\ListBlogs;
use App\Filament\Resources\Blogs\Schemas\BlogForm;
use App\Filament\Resources\Blogs\Tables\BlogsTable;
use App\Models\Blog;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

use BackedEnum;

class BlogResource extends Resource
{
    protected static ?string $model = Blog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'content'];
    }
    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return '✍️ ' . $record->title;
    }
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $description = strip_tags($record->content ?? '');

        // İlk cümleyi al
        $firstSentence = Str::of($description)
            ->explode('.')
            ->first();

        return [
            'İçerik' => Str::limit($firstSentence, 120),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Blog';
    }
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return BlogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlogsTable::configure($table);
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
            'index' => ListBlogs::route('/'),
            'create' => CreateBlog::route('/create'),
            'edit' => EditBlog::route('/{record}/edit'),
        ];
    }
}
