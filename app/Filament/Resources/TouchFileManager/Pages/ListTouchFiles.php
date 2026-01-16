<?php

namespace App\Filament\Resources\TouchFileManager\Pages;

use App\Filament\Resources\TouchFileManager\TouchFileManagerResource;
use App\Filament\Resources\TouchFileManager\Schemas\TouchFileForm;
use App\Models\TouchFile;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;

class ListTouchFiles extends ListRecords
{
    protected static string $resource = TouchFileManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('createFolder')
                ->label('New Folder')
                ->icon('heroicon-o-folder-plus')
                ->color('warning')
                ->size('xs')
                ->form(TouchFileForm::folderSchema()->getComponents())
                ->action(function (array $data) {
                    $parentId = $data['parent_id'] ?? null;
                    // Slugify folder name
                    $name = \Illuminate\Support\Str::slug($data['name']);
                    $data['name'] = $name;

                    // Calculate Path
                    $path = $name;
                    if ($parentId) {
                        $parent = TouchFile::find($parentId);
                        if ($parent) {
                            // Use recursive relationship or stored path
                            // full_path attribute relies on parent relationship
                            $path = $parent->full_path . '/' . $name;
                        }
                    }

                    $data['is_folder'] = true;
                    $data['type'] = null;
                    $data['mime_type'] = null;
                    $data['size'] = null;
                    $data['path'] = $path;

                    // Create physical folder
                    $disk = \Illuminate\Support\Facades\Storage::disk('attachments');
                    if (!$disk->exists($path)) {
                        $disk->makeDirectory($path);
                    }

                    static::getResource()::getModel()::create($data);
                })
                ->successNotificationTitle('Folder created successfully'),

            Actions\CreateAction::make()
                ->label('Upload Files')
                ->tooltip('Upload new files')
                ->color('success')
                ->size('xs')
                ->icon('heroicon-o-arrow-up-tray'),
        ];
    }
}
