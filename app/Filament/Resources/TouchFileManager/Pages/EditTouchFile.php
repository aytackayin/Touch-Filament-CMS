<?php

namespace App\Filament\Resources\TouchFileManager\Pages;

use App\Filament\Resources\TouchFileManager\TouchFileManagerResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditTouchFile extends EditRecord
{
    protected static string $resource = TouchFileManagerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading(fn() => $this->record->is_folder ? 'Delete Folder' : 'Delete File')
                ->modalDescription(fn() => $this->record->is_folder
                    ? 'Are you sure you want to delete this folder? All files and subfolders inside will also be deleted.'
                    : 'Are you sure you want to delete this file?'),
        ];
    }

    public ?string $previousUrl = null;

    public function mount(int|string $record): void
    {
        parent::mount($record);
        $this->previousUrl = url()->previous();
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // 1. If path is removed (file deleted from upload), delete the record
        if (!$record->is_folder && empty($data['path'])) {
            $record->delete();
            $this->redirect($this->getResource()::getUrl('index'));
            return $record;
        }

        // 2. If file content changed (Image Editor or New Upload)
        // The data['path'] will differ from record->path
        if (!$record->is_folder && isset($data['path']) && $data['path'] !== $record->path) {
            $disk = \Illuminate\Support\Facades\Storage::disk('attachments');
            $newFilename = $data['path']; // This is the temp/root filename from Filament

            // Calculate Target Directory
            $targetDir = '.';
            $parentId = $data['parent_id'] ?? $record->parent_id; // Use new parent if changed, or old one

            if ($parentId) {
                $parent = \App\Models\TouchFile::find($parentId);
                if ($parent) {
                    $targetDir = $parent->full_path;
                }
            }

            // Normalized paths
            $targetPath = ($targetDir === '.' ? '' : $targetDir . '/') . basename($newFilename);
            $sourcePath = $newFilename; // Currently at root or temp

            // Only proceed if we strictly need to move (source != target)
            // But usually file upload puts it in root/temp with hashed name, so we definitely need to move/rename

            // A. Clean up OLD file and thumb
            if ($disk->exists($record->path)) {
                $disk->delete($record->path);
            }

            $oldThumbPath = $record->thumbnail_path;
            if ($oldThumbPath && $disk->exists($oldThumbPath)) {
                $disk->delete($oldThumbPath);
            }

            // B. Move NEW file to Target
            if ($sourcePath !== $targetPath) {
                // Ensure target dir exists
                $dirNormalized = str_replace('\\', '/', dirname($targetPath));
                if ($dirNormalized !== '.' && !$disk->exists($dirNormalized)) {
                    $disk->makeDirectory($dirNormalized);
                }

                if ($disk->exists($sourcePath)) {
                    $disk->move($sourcePath, $targetPath);
                }
            }

            // C. Generate NEW Thumbnail (if image)
            if (class_exists(\Intervention\Image\ImageManager::class) && str_starts_with($disk->mimeType($targetPath), 'image/')) {
                try {
                    $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());

                    $thumbsDir = str_replace('\\', '/', dirname($targetPath));
                    if ($thumbsDir === '.')
                        $thumbsDir = 'thumbs';
                    else
                        $thumbsDir .= '/thumbs';

                    if (!$disk->exists($thumbsDir)) {
                        $disk->makeDirectory($thumbsDir);
                    }

                    $thumbPath = $thumbsDir . '/' . basename($targetPath);
                    $fullPath = $disk->path($targetPath);
                    $thumbFullPath = $disk->path($thumbPath);

                    $image = $manager->read($fullPath);
                    $image->scale(width: 150);
                    $image->save($thumbFullPath);

                } catch (\Exception $e) {
                    \Log::error('Thumbnail generation failed on edit: ' . $e->getMessage());
                }
            }

            // D. Update Data for DB Update
            $data['path'] = $targetPath;
            $data['name'] = basename($targetPath);
            $data['size'] = $disk->size($targetPath);
            $data['mime_type'] = $disk->mimeType($targetPath);
        }

        $record->update($data);
        return $record;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Don't show files field for existing records
        unset($data['files']);
        return $data;
    }
}
