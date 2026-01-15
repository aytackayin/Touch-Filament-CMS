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

class TouchFileManagerResource extends Resource
{
    protected static ?string $model = TouchFile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?string $navigationLabel = 'Touch File Manager';

    protected static ?string $modelLabel = 'File/Folder';

    protected static ?string $pluralModelLabel = 'Files & Folders';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'mime_type'];
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
            $details['Type'] = ucfirst($record->type ?? 'Unknown');
            $details['Size'] = $record->human_size;
        }

        if ($record->parent) {
            $details['Location'] = $record->parent->full_path;
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
