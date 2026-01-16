<?php

namespace App\Filament\Resources\TouchFileManager\Tables;

use App\Filament\Resources\TouchFileManager\TouchFileManagerResource;
use App\Models\TouchFile;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Support\Enums\IconSize;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

use Illuminate\Database\Eloquent\Builder;

class TouchFileManagerTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) use ($table) {
                $livewire = $table->getLivewire();
                if ($livewire && property_exists($livewire, 'parent_id')) {
                    if ($livewire->parent_id) {
                        $query->where('parent_id', $livewire->parent_id);
                    } else {
                        $query->whereNull('parent_id');
                    }
                }
                return $query;
            })
            ->striped()
            ->recordUrl(
                fn(TouchFile $record): ?string => $record->is_folder
                ? TouchFileManagerResource::getUrl('index', ['parent_id' => $record->id])
                : null
            )
            ->columns([
                ImageColumn::make('thumbnail_preview')
                    ->label('Preview')
                    ->disk('attachments')
                    ->state(fn(TouchFile $record) => $record->thumbnail_path)
                    ->height(40)
                    ->width(60)
                    ->defaultImageUrl(fn(TouchFile $record) => $record->is_folder
                        ? url('/images/icons/folder.png')
                        : url('/images/icons/file.png'))
                    ->toggleable(),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->weight('medium')
                    ->description(fn(TouchFile $record) => $record->is_folder ? 'Folder' : $record->mime_type)
                    //->icon(fn(TouchFile $record) => $record->is_folder ? 'heroicon-o-folder' : null)
                    ->color(fn(TouchFile $record) => $record->is_folder ? 'warning' : null),

                TextColumn::make('type')
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
                    ->formatStateUsing(fn(string $state): string => ucfirst($state))
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('human_size')
                    ->label('Size')
                    ->sortable(query: function ($query, string $direction) {
                        return $query->orderBy('size', $direction);
                    })
                    ->alignEnd(),

                TextColumn::make('parent.name')
                    ->label('Parent Folder')
                    ->default('Root')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name', 'asc')
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
                    ->hidden(fn(TouchFile $record) => $record->is_folder)
                    ->url(fn(TouchFile $record) => Storage::disk('attachments')->url($record->path))
                    ->openUrlInNewTab(),

                Action::make('view')
                    ->icon('heroicon-o-eye')
                    ->label('')
                    ->tooltip('View')
                    ->hidden(fn(TouchFile $record) => !in_array($record->type, ['image', 'video']))
                    ->modalContent(fn(TouchFile $record) => view('filament.modals.file-preview', [
                        'record' => $record,
                        'url' => Storage::disk('attachments')->url($record->path),
                    ]))
                    ->modalWidth('5xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                Action::make('edit')
                    ->url(fn(TouchFile $record): string => TouchFileManagerResource::getUrl('edit', ['record' => $record]))
                    ->icon('heroicon-o-pencil-square')
                    ->label('')
                    ->tooltip('Edit'),

                DeleteAction::make()
                    ->label('')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->tooltip('Delete')
                    ->requiresConfirmation()
                    ->modalHeading(fn(TouchFile $record) => $record->is_folder ? 'Delete Folder' : 'Delete File')
                    ->modalDescription(fn(TouchFile $record) => $record->is_folder
                        ? 'Are you sure you want to delete this folder? All files and subfolders inside will also be deleted.'
                        : 'Are you sure you want to delete this file?')
                    ->action(fn($record) => $record->delete()),
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
