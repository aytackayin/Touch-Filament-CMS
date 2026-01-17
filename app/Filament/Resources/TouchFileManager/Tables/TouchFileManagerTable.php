<?php

namespace App\Filament\Resources\TouchFileManager\Tables;

use App\Filament\Resources\TouchFileManager\TouchFileManagerResource;
use App\Models\TouchFile;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Hidden;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Columns\Layout\Stack;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Builder;

class TouchFileManagerTable
{
    public static function configure(Table $table): Table
    {
        $livewire = $table->getLivewire();
        $isGrid = ($livewire && property_exists($livewire, 'view_type'))
            ? $livewire->view_type === 'grid'
            : true;

        return $table
            ->when(
                $isGrid,
                fn(Table $table) => $table
                    ->contentGrid([
                        'md' => 2,
                        'xl' => 4,
                        '2xl' => 5,
                    ])
                    ->extraAttributes([
                        'class' => 'touch-file-manager-grid',
                    ])
            )
            ->modifyQueryUsing(function (Builder $query) use ($table) {
                $livewire = $table->getLivewire();

                // Determine Parent ID safely
                $parentId = null;
                if ($livewire && property_exists($livewire, 'parent_id')) {
                    $parentId = $livewire->parent_id;
                }

                // If we are inside a folder, inject the "Up" item
                if ($parentId) {
                    $columns = [
                        'id',
                        'name',
                        'alt',
                        'path',
                        'type',
                        'mime_type',
                        'size',
                        'parent_id',
                        'is_folder',
                        'metadata',
                        'created_at',
                        'updated_at'
                    ];

                    // Part 1: Real Files (Inner Query)
                    // We specifically select columns to match the Union structure
                    $query->select($columns)->where('parent_id', $parentId);

                    // Part 2: Fake "Up" Record
                    // We use the model's query to generate SQL, but careful with bindings
                    $fakeRow = TouchFile::query()
                        ->selectRaw("
                            0 as id, 
                            'Up' as name,
                            null as alt,
                            '' as path, 
                            'folder' as type, 
                            null as mime_type, 
                            0 as size, 
                            ? as parent_id, 
                            1 as is_folder, 
                            null as metadata, 
                            null as created_at, 
                            null as updated_at
                        ", [$parentId]);

                    // Union
                    $unionQuery = $query->union($fakeRow);

                    // Wrap in a parent query with alias 'touch_files'
                    // This creates: SELECT * FROM ( ... union ... ) as touch_files
                    // This satisfies "items.id" references in default sorts because the table alias matches.
                    return TouchFile::query()
                        ->fromSub($unionQuery, 'touch_files')
                        ->select('*')
                        ->orderByRaw('CASE WHEN id = 0 THEN 1 ELSE 0 END DESC')
                        ->orderBy('is_folder', 'desc')
                        ->orderBy('name', 'asc');
                }

                // Default: Root handling (no parent_id)
                return $query
                    ->whereNull('parent_id')
                    ->orderBy('is_folder', 'desc')
                    ->orderBy('name', 'asc');
            })
            ->striped()
            ->recordUrl(
                function ($record) use ($table): ?string {
                    if (!$record)
                        return null;

                    if ($record->id === 0) {
                        // "Up" navigation logic
                        $currentParentId = $table->getLivewire()->parent_id;
                        if ($currentParentId) {
                            $currentFolder = TouchFile::find($currentParentId);
                            $targetId = $currentFolder ? $currentFolder->parent_id : null;

                            return TouchFileManagerResource::getUrl('index', [
                                'parent_id' => $targetId,
                                'view_type' => $table->getLivewire()->view_type ?? 'grid',
                            ]);
                        }
                        return null;
                    }

                    return $record->is_folder
                        ? TouchFileManagerResource::getUrl('index', [
                            'parent_id' => $record->id,
                            'view_type' => $table->getLivewire()->view_type ?? 'grid',
                        ])
                        : null;
                }
            )
            ->columns($isGrid ? [
                Stack::make([
                    ViewColumn::make('details')
                        ->view('filament.tables.columns.touchfilemanager-grid')->searchable(['name', 'type', 'alt']),
                ])->space(0),
            ] : [
                ImageColumn::make('thumbnail_preview')
                    ->label('')
                    ->disk('attachments')
                    ->state(fn(TouchFile $record) => $record->thumbnail_path)
                    ->width(60)
                    ->height(60)
                    ->defaultImageUrl(function ($record) {
                        if (!$record)
                            return null;

                        // Fake "Up" Record
                        if ($record->id === 0) {
                            return url('/assets/icons/colorful-icons/open-folder.svg');
                        }

                        // Folder
                        if ($record->is_folder) {
                            return url('/assets/icons/colorful-icons/folder.svg');
                        }

                        // Check exclusions: Image, Video, Audio
                        // "Music" usually implies audio mime types.
                        $isMedia = in_array($record->type, ['image', 'video'])
                            || str_starts_with($record->mime_type ?? '', 'audio/');

                        if ($isMedia) {
                            return url('/assets/icons/colorful-icons/file.svg');
                        }

                        // For other files, use extension-based icon
                        // Ex: zip -> zip.svg
                        $ext = strtolower($record->extension);
                        if ($ext) {
                            return url("/assets/icons/colorful-icons/{$ext}.svg");
                        }

                        // Fallback
                        return url('/assets/icons/colorful-icons/file.svg');
                    })
                    ->extraImgAttributes(['class' => 'object-cover object-center rounded-lg', 'style' => 'width: 60px; height: 60px; border-radius: 10px;']),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable(['name', 'alt'])
                    ->weight('bold')
                    ->wrap()
                    ->color(fn($record) => $record?->is_folder ? 'warning' : null)
                    ->formatStateUsing(fn(string $state, $record) => $record?->id === 0 ? 'Up' : $state)
                    ->description(function ($record) {
                        if (!$record || $record->id === 0)
                            return null;

                        $desc = [];
                        if (!empty($record->alt)) {
                            $desc[] = $record->alt;
                        }

                        if (!$record->is_folder) {
                            $desc[] = $record->human_size;
                        }

                        return implode(' • ', $desc);
                    }),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'image' => 'success',
                        'video' => 'info',
                        'document' => 'primary',
                        'archive' => 'warning',
                        'spreadsheet' => 'success',
                        'presentation' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state, $record) => $record?->id === 0 ? '' : ucfirst($state))
                    ->extraAttributes(fn($record) => $record?->id === 0 ? ['style' => 'display: none !important;'] : []),

                TextColumn::make('created_at')
                    ->label('Date')
                    ->date()
                    ->sortable()
                    ->toggleable()
                    ->placeholder(''),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('File Type')
                    ->options([
                        'image' => 'Images',
                        'video' => 'Videos',
                        'document' => 'Documents',
                        'archive' => 'Archives',
                        'spreadsheet' => 'Spreadsheets',
                        'presentation' => 'Presentations',
                        'other' => 'Other',
                    ])
                    ->multiple(),

                SelectFilter::make('is_folder')
                    ->label('Type')
                    ->options([
                        '1' => 'Folders',
                        '0' => 'Files',
                    ]),

                SelectFilter::make('parent_id')
                    ->label('Parent Folder')
                    ->options(function () {
                        return TouchFile::where('is_folder', true)
                            ->orderBy('name')
                            ->get()
                            ->mapWithKeys(function ($folder) {
                                return [$folder->id => $folder->full_path];
                            });
                    })
                    ->searchable(),
            ])
            ->recordActions([
                Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->label('')
                    ->tooltip('Download')
                    ->hidden(fn($record) => $record && ($record->is_folder || $record->id === 0))
                    ->url(fn($record) => $record ? Storage::disk('attachments')->url($record->path) : null)
                    ->openUrlInNewTab(),

                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->label('')
                    ->tooltip('View')
                    ->hidden(fn($record) => !$record || !in_array($record->type, ['image', 'video']) || $record->id === 0)
                    ->modalContent(fn($record) => $record ? view('filament.modals.touchfilemanager-preview', [
                        'record' => $record,
                        'url' => Storage::disk('attachments')->url($record->path),
                    ]) : null)
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                Action::make('edit')
                    ->url(fn($record): string => TouchFileManagerResource::getUrl('edit', [
                        'record' => $record,
                        'parent_id' => $record->parent_id
                    ]))
                    ->icon('heroicon-o-pencil-square')
                    ->label('')
                    ->tooltip('Edit')
                    ->hidden(fn($record) => $record?->id === 0),

                DeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->tooltip('Delete')
                    ->requiresConfirmation()
                    ->modalHeading(fn($record) => $record?->is_folder ? 'Delete Folder' : 'Delete File')
                    ->modalDescription(fn($record) => $record?->is_folder
                        ? 'Are you sure you want to delete this folder? All files and subfolders inside will also be deleted.'
                        : 'Are you sure you want to delete this file?')
                    ->action(fn($record) => $record->delete())
                    ->hidden(fn($record) => $record?->id === 0),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Delete selected')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Selected Items')
                        ->modalDescription('Are you sure you want to delete the selected items? Folders will be deleted with all their contents.')
                        ->action(fn($records) => $records->each->delete()),
                ]),
            ]);
    }
}
