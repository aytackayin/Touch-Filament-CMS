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
use Illuminate\Support\Facades\Storage;

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
        return ' ';
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];

        // 1. Thumbnail or Icon + Name (Side-by-side)
        $imageUrl = '';
        if ($record->is_folder) {
            $imageUrl = url('/assets/icons/colorful-icons/folder.svg');
        } else {
            if ($record->thumbnail_path) {
                $imageUrl = Storage::disk('attachments')->url($record->thumbnail_path);
            } else {
                $isMedia = in_array($record->type, ['image', 'video'])
                    || Str::startsWith($record->mime_type ?? '', 'audio/');

                if ($isMedia) {
                    $imageUrl = url('/assets/icons/colorful-icons/file.svg');
                } else {
                    $ext = strtolower($record->extension);
                    $imageUrl = $ext
                        ? url("/assets/icons/colorful-icons/{$ext}.svg")
                        : url('/assets/icons/colorful-icons/file.svg');
                }
            }
        }

        $uniqueId = 'gs-name-' . $record->id;
        $details[] = new HtmlString('
            <style>
                #' . $uniqueId . ' { color: #030712 !important; opacity: 1 !important; }
                .dark #' . $uniqueId . ' { color: #ffffff !important; }
            </style>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 6px; margin-top: -10px;">
                <div style="width: 48px; height: 48px; border-radius: 8px; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <img src="' . $imageUrl . '" 
                         style="width: 100%; height: 100%; object-fit: ' . ($record->thumbnail_path ? 'cover' : 'contain; padding: 6px') . ';"
                         onerror="this.src=\'' . url('/assets/icons/colorful-icons/file.svg') . '\'">
                </div>
                <div style="display: flex; flex-direction: column;">
                    <span id="' . $uniqueId . '" style="font-weight: 600; font-size: 14px;">' . $record->name . '</span>
                </div>
            </div>
        ');

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
