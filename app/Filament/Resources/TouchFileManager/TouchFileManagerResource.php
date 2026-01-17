<?php

namespace App\Filament\Resources\TouchFileManager;

use App\Filament\Resources\TouchFileManager\Pages\CreateTouchFile;
use App\Filament\Resources\TouchFileManager\Pages\EditTouchFile;
use App\Filament\Resources\TouchFileManager\Pages\ListTouchFiles;
use App\Filament\Resources\TouchFileManager\Schemas\TouchFileForm;
use App\Filament\Resources\TouchFileManager\Tables\TouchFileManagerTable;
use App\Models\TouchFile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;

class TouchFileManagerResource extends Resource
{
    protected static ?string $model = TouchFile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?string $navigationLabel = 'Touch File Manager';

    protected static ?string $modelLabel = 'File/Folder';

    protected static ?string $pluralModelLabel = 'Files & Folders';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'tags'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        $icon = $record->is_folder ? '📁' : '📄';
        return $icon . ' ' . $record->name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];

        if (!$record->is_folder) {
            if ($record->alt) {
                $description = strip_tags($record->alt);
                $firstSentence = Str::of($description)->explode('.')->first();
                $details[] = new HtmlString('<span style="font-size: 12px; line-height: 1;">' . Str::limit($firstSentence, 120) . '</span>');
            }

            if ($record->tags && is_array($record->tags)) {
                $details[] = new HtmlString('<span style="font-size: 12px; line-height: 1;">' . implode(', ', $record->tags) . '</span>');
            }

            $details[] = new HtmlString('<span style="font-size: 12px; line-height: 1;">' . ucfirst($record->type ?? 'Unknown') . ' (' . $record->human_size . ')</span>');
        }

        if ($record->parent) {
            $details[] = new HtmlString('<span style="font-size: 12px; line-height: 1;">' . $record->parent->full_path . '</span>');
        }

        return $details;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'File Management';
    }

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return TouchFileForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TouchFileManagerTable::configure($table);
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
            'index' => ListTouchFiles::route('/'),
            'create' => CreateTouchFile::route('/create'),
            'edit' => EditTouchFile::route('/{record}/edit'),
        ];
    }

    public static function canCreate(): bool
    {
        return true;
    }
}
