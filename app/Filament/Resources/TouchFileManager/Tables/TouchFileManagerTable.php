<?php

namespace App\Filament\Resources\TouchFileManager\Tables;

use App\Filament\Resources\TouchFileManager\TouchFileManagerResource;
use App\Models\TouchFile;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Columns\Layout\Stack;
use Illuminate\Support\Facades\Storage;
use Filament\Notifications\Notification;
use Filament\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use ZipArchive;
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
                $parentId = ($livewire && property_exists($livewire, 'parent_id')) ? $livewire->parent_id : null;

                $columns = [
                    'touch_files.id',
                    'touch_files.user_id',
                    'touch_files.edit_user_id',
                    'touch_files.name',
                    'touch_files.alt',
                    'touch_files.path',
                    'touch_files.type',
                    'touch_files.mime_type',
                    'touch_files.size',
                    'touch_files.parent_id',
                    'touch_files.is_folder',
                    'touch_files.metadata',
                    'touch_files.tags',
                    'touch_files.created_at',
                    'touch_files.updated_at'
                ];

                if ($parentId) {
                    $mainQuery = TouchFile::query()->select($columns)
                        ->leftJoin('users as u', 'touch_files.user_id', '=', 'u.id')
                        ->leftJoin('users as e', 'touch_files.edit_user_id', '=', 'e.id')
                        ->addSelect('u.name as user_name', 'e.name as editor_name')
                        ->where('parent_id', $parentId);

                    $fakeRow = TouchFile::query()->selectRaw("0 as id, null as user_id, null as edit_user_id, 'Up' as name, null as alt, '' as path, 'folder' as type, null as mime_type, 0 as size, ? as parent_id, 1 as is_folder, null as metadata, null as tags, null as created_at, null as updated_at, null as user_name, null as editor_name", [$parentId]);

                    $unionQuery = $mainQuery->union($fakeRow);

                    return TouchFile::query()->fromSub($unionQuery, 'touch_files')->select('*')
                        ->orderByRaw('CASE WHEN id = 0 THEN 1 ELSE 0 END DESC')
                        ->orderBy('is_folder', 'desc')
                        ->orderBy('name', 'asc');
                }

                return TouchFile::query()->from((new TouchFile)->getTable())
                    ->select($columns)
                    ->leftJoin('users as u', 'touch_files.user_id', '=', 'u.id')
                    ->leftJoin('users as e', 'touch_files.edit_user_id', '=', 'e.id')
                    ->addSelect('u.name as user_name', 'e.name as editor_name')
                    ->whereNull('parent_id')
                    ->orderBy('is_folder', 'desc')
                    ->orderBy('name', 'asc');
            })
            ->defaultPaginationPageOption($table->getLivewire()->perPage ?? 10)
            ->striped()
            ->recordUrl(function ($record) use ($table): ?string {
                if (!$record)
                    return null;
                if ($record->id === 0) {
                    $currentParentId = $table->getLivewire()->parent_id;
                    if ($currentParentId) {
                        $currentFolder = TouchFile::find($currentParentId);
                        $targetId = $currentFolder ? $currentFolder->parent_id : null;
                        return TouchFileManagerResource::getUrl('index', ['parent_id' => $targetId, 'view_type' => $table->getLivewire()->view_type ?? 'grid']);
                    }
                    return null;
                }
                return $record->is_folder ? TouchFileManagerResource::getUrl('index', ['parent_id' => $record->id, 'view_type' => $table->getLivewire()->view_type ?? 'grid']) : null;
            })
            ->columns($isGrid ? [
                Stack::make([
                    ViewColumn::make('details')
                        ->view('filament.tables.columns.touchfilemanager-grid')
                        ->searchable(['name', 'type', 'alt', 'tags', 'user_name', 'editor_name', 'created_at']),
                ])->space(0),
            ] : [
                ImageColumn::make('thumbnail_preview')->label('')->disk('attachments')->state(fn(TouchFile $record) => $record->thumbnail_path)->width(60)->height(60)
                    ->defaultImageUrl(function ($record) {
                        if (!$record)
                            return null;
                        $iconConfig = config('touch-file-manager.icon_paths');
                        $base = $iconConfig['base'] ?? '/assets/icons/colorful-icons/';
                        if ($record->id === 0)
                            return url($base . 'open-folder.svg');
                        if ($record->is_folder)
                            return url($base . ($iconConfig['folder'] ?? 'folder.svg'));
                        if (in_array($record->type, ['image', 'video']) || str_starts_with($record->mime_type ?? '', 'audio/'))
                            return url($base . ($iconConfig['file'] ?? 'file.svg'));
                        $ext = strtolower($record->extension);
                        return $ext ? url($base . "{$ext}.svg") : url($base . ($iconConfig['file'] ?? 'file.svg'));
                    })
                    ->extraImgAttributes(['class' => 'object-cover object-center rounded-lg', 'style' => 'width: 60px; height: 60px; border-radius: 10px;']),

                TextColumn::make('name')->label(__('file_manager.label.name'))->searchable(['name', 'alt'])->weight('bold')->wrap()
                    ->color(fn($record) => $record?->is_folder ? 'warning' : null)
                    ->formatStateUsing(fn(string $state, $record) => $record?->id === 0 ? __('file_manager.label.up') : $state)
                    ->description(function ($record) {
                        if (!$record || $record->id === 0)
                            return null;

                        return !empty($record->alt) ? $record->alt : null;
                    }),

                TextColumn::make('type_size')->label(__('file_manager.label.type_size'))
                    ->state(fn($record) => $record?->id === 0 ? '' : $record->type)
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'image' => 'success', 'video' => 'info', 'document' => 'primary', 'archive' => 'warning', 'spreadsheet' => 'success', 'presentation' => 'danger', default => 'gray',
                    })
                    ->searchable(query: function (Builder $query, string $search) {
                        $search = strtolower($search);
                        $typeMap = [
                            'resim' => 'image',
                            'video' => 'video',
                            'belge' => 'document',
                            'arşiv' => 'archive',
                            'arsiv' => 'archive',
                            'e-tablo' => 'spreadsheet',
                            'etablo' => 'spreadsheet',
                            'sunum' => 'presentation',
                            'diğer' => 'other',
                            'diger' => 'other',
                        ];

                        $dbType = $typeMap[$search] ?? null;

                        return $query->where(function ($q) use ($search, $dbType) {
                            $q->where('path', 'like', "%{$search}%"); // search by extension (part of path)
                            if ($dbType) {
                                $q->orWhere('type', '=', $dbType);
                            } else {
                                $q->orWhere('type', 'like', "%{$search}%");
                            }
                        });
                    })
                    ->formatStateUsing(fn(string $state, $record) => $record?->id === 0 ? '' : __('file_manager.label.types.' . $state))
                    ->description(function ($record) {
                        if (!$record || $record->id === 0 || $record->is_folder)
                            return null;
                        return strtoupper($record->extension) . ' - ' . $record->human_size;
                    })
                    ->toggleable(isToggledHiddenByDefault: fn($livewire) => !in_array('type_size', $livewire->visibleColumns ?? []) || ($livewire->view_type ?? 'list') === 'grid')
                    ->extraAttributes(fn($record) => $record?->id === 0 ? ['style' => 'display: none !important;'] : []),

                TextColumn::make('tags')->label(__('file_manager.label.tags'))->badge()->separator(',')->searchable()->wrap()
                    ->toggleable(isToggledHiddenByDefault: fn($livewire) => !in_array('tags', $livewire->visibleColumns ?? [])),
                TextColumn::make('user.name')->label(__('file_manager.label.author'))->sortable()
                    ->searchable(query: fn(Builder $query, string $search) => $query->where('user_name', 'like', "%{$search}%"))
                    ->toggleable(isToggledHiddenByDefault: fn($livewire) => !in_array('user', $livewire->visibleColumns ?? []))
                    ->formatStateUsing(fn($state, $record) => $record?->id === 0 ? '' : $state),
                TextColumn::make('editor.name')->label(__('file_manager.label.last_editor'))->sortable()
                    ->searchable(query: fn(Builder $query, string $search) => $query->where('editor_name', 'like', "%{$search}%"))
                    ->toggleable(isToggledHiddenByDefault: fn($livewire) => !in_array('editor', $livewire->visibleColumns ?? []))
                    ->formatStateUsing(fn($state, $record) => $record?->id === 0 ? '' : $state),
                TextColumn::make('created_at')->label(__('file_manager.label.date'))->date()->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: fn($livewire) => !in_array('created_at', $livewire->visibleColumns ?? []))
                    ->placeholder(''),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('file_manager.label.file_type'))
                    ->options([
                        'image' => (string) __('file_manager.label.plural_types.images'),
                        'video' => (string) __('file_manager.label.plural_types.videos'),
                        'document' => (string) __('file_manager.label.plural_types.documents'),
                        'archive' => (string) __('file_manager.label.plural_types.archives'),
                        'spreadsheet' => (string) __('file_manager.label.plural_types.spreadsheets'),
                        'presentation' => (string) __('file_manager.label.plural_types.presentations'),
                        'other' => (string) __('file_manager.label.plural_types.others'),
                    ])
                    ->multiple(),

                SelectFilter::make('user_id')
                    ->label(__('file_manager.label.author'))
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('is_folder')
                    ->label(__('file_manager.label.type'))
                    ->options([
                        '1' => (string) __('file_manager.label.plural_types.folders'),
                        '0' => (string) __('file_manager.label.plural_types.files'),
                    ]),

                SelectFilter::make('parent_id')
                    ->label(__('file_manager.label.parent_folder'))
                    ->options(fn() => TouchFile::where('is_folder', true)
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->map(fn($n) => (string) $n)
                        ->toArray())
                    ->searchable(),
            ])
            ->actions([
                Action::make('download')->icon('heroicon-o-arrow-down-tray')->label('')->color('success')->tooltip(__('file_manager.action.download'))
                    ->hidden(fn($record) => $record && ($record->is_folder || $record->id === 0))
                    ->url(fn($record) => $record ? Storage::disk('attachments')->url($record->path) : null)->openUrlInNewTab(),

                Action::make('view')->icon('heroicon-o-eye')->label('')->color('gray')->tooltip(__('file_manager.action.view'))
                    ->hidden(fn($record) => !$record || !in_array($record->type, ['image', 'video']) || $record->id === 0)
                    ->modalContent(fn($record) => $record ? view('filament.modals.touchfilemanager-preview', ['record' => $record, 'url' => Storage::disk('attachments')->url($record->path)]) : null)
                    ->modalWidth('5xl')->modalSubmitAction(false)->modalCancelActionLabel(__('filament-actions::view.single.modal.actions.close.label')),

                Action::make('edit')->url(fn($record) => TouchFileManagerResource::getUrl('edit', ['record' => $record, 'parent_id' => $record->parent_id, 'view_type' => $table->getLivewire()->view_type ?? 'grid']))
                    ->icon('heroicon-o-pencil-square')->label('')->tooltip(__('filament-actions::edit.single.label'))->color('warning')->hidden(fn($record) => $record?->id === 0),

                Action::make('copy_url')->label('')->tooltip(__('file_manager.action.copy_url'))->icon('heroicon-o-clipboard')->color('info')->action(null)
                    ->hidden(fn($record) => !$record || $record->is_folder || $record->id === 0)
                    ->extraAttributes(fn($record) => [
                        'x-data' => '{}',
                        'x-on:click.prevent.stop' => "navigator.clipboard.writeText('" . e(Storage::disk('attachments')->url($record->path)) . "'); \$dispatch('notify', { title: '" . __('file_manager.action.url_copied') . "', body: '" . e(Storage::disk('attachments')->url($record->path)) . "' });",
                    ]),

                DeleteAction::make()->label('')->icon('heroicon-o-trash')->color('danger')->tooltip(__('filament-actions::delete.single.label'))->requiresConfirmation()
                    ->modalHeading(fn($record) => $record?->is_folder ? __('file_manager.delete.folder_title') : __('file_manager.delete.file_title'))
                    ->modalDescription(fn($record) => $record?->is_folder ? __('file_manager.delete.folder_description') : __('file_manager.delete.file_description'))
                    ->action(fn($record) => $record->delete())->hidden(fn($record) => $record?->id === 0),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->label(__('filament-actions::delete.multiple.label'))->icon('heroicon-o-trash')->color('danger')->requiresConfirmation()
                        ->modalHeading(__('file_manager.delete.bulk_title'))->modalDescription(__('file_manager.delete.bulk_description'))
                        ->action(fn($records) => $records->filter(fn($record) => auth()->user()->can('delete', $record))->each->delete()),

                    BulkAction::make('download_selected')->label(__('file_manager.action.download_selected'))->icon('heroicon-o-arrow-down-tray')->action(function (Collection $records) {
                        $zipName = 'files-' . now()->timestamp . '.zip';
                        $zipPath = storage_path('app/' . $zipName);
                        $zip = new ZipArchive();
                        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                            Notification::make()->title(__('file_manager.errors.zip_error'))->danger()->send();
                            return;
                        }
                        $addRecursively = function ($record, $zip, $relativePath) use (&$addRecursively) {
                            $disk = Storage::disk('attachments');
                            $fullPath = $record->path;
                            if ($record->is_folder) {
                                $folderName = $relativePath . $record->name;
                                $zip->addEmptyDir($folderName);
                                $children = TouchFile::where('parent_id', $record->id)->get();
                                foreach ($children as $child)
                                    $addRecursively($child, $zip, $folderName . '/');
                            } else {
                                if ($disk->exists($fullPath)) {
                                    $realPath = $disk->path($fullPath);
                                    $fileName = $record->name . ($record->extension ? '.' . $record->extension : '');
                                    $zip->addFile($realPath, $relativePath . $fileName);
                                }
                            }
                        };
                        foreach ($records as $record) {
                            if ($record->id === 0)
                                continue;
                            $addRecursively($record, $zip, '');
                        }
                        $zip->close();
                        return response()->download($zipPath)->deleteFileAfterSend();
                    })->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
