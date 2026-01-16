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
            // D. Handle Video Thumbnail (from hidden field)
            elseif ($disk->mimeType($targetPath) && str_starts_with($disk->mimeType($targetPath), 'video/')) {
                $videoThumbnails = $data['video_thumbnails_store'] ?? null;
                if ($videoThumbnails) {
                    $videoThumbnailsData = json_decode($videoThumbnails, true);
                    if (is_array($videoThumbnailsData)) {
                        // The temp filename that created the thumb might effectively be $newFilename (sourcePath)
                        // Filenames might be slightly different due to Filament sanitization.
                        // We'll try to match by loose slug or original name logic.

                        $currentFileName = basename($sourcePath); // Name uploaded to temp

                        foreach ($videoThumbnailsData as $thumbData) {
                            $thumbFilename = $thumbData['filename'] ?? '';
                            // Match logic primarily based on CreateTouchFile
                            $nameNoExt = pathinfo($thumbFilename, PATHINFO_FILENAME);
                            $ext = pathinfo($thumbFilename, PATHINFO_EXTENSION);
                            $slugged = \Illuminate\Support\Str::slug($nameNoExt) . '.' . $ext;

                            // Check match against the temp filename uploaded
                            if ($slugged === $currentFileName) {
                                $base64Data = $thumbData['thumbnail'] ?? null;
                                if ($base64Data) {
                                    try {
                                        $thumbsDir = str_replace('\\', '/', dirname($targetPath));
                                        if ($thumbsDir === '.')
                                            $thumbsDir = 'thumbs';
                                        else
                                            $thumbsDir .= '/thumbs';

                                        if (!$disk->exists($thumbsDir)) {
                                            $disk->makeDirectory($thumbsDir);
                                        }

                                        $thumbName = pathinfo(basename($targetPath), PATHINFO_FILENAME) . '.jpg';
                                        $thumbPath = $thumbsDir . '/' . $thumbName;

                                        // Clean base64
                                        $imageData = $base64Data;
                                        if (str_contains($imageData, 'data:image')) {
                                            $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
                                        }
                                        $imageData = str_replace(' ', '+', $imageData);
                                        $decodedImage = base64_decode($imageData);

                                        if ($decodedImage !== false) {
                                            $disk->put($thumbPath, $decodedImage);
                                        }
                                    } catch (\Exception $e) {
                                        \Log::error('Video thumbnail save failed on edit: ' . $e->getMessage());
                                    }
                                }
                                break;
                            }
                        }
                    }
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

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['name'])) {
            if ($this->record->is_folder) {
                // For folders, just slugify the whole name
                $data['name'] = \Illuminate\Support\Str::slug($data['name']);
            } else {
                // For files, preserve extension
                $originalName = $data['name'];
                $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                $nameNoExt = pathinfo($originalName, PATHINFO_FILENAME);

                $sluggedName = \Illuminate\Support\Str::slug($nameNoExt);

                if ($extension) {
                    $data['name'] = $sluggedName . '.' . $extension;
                } else {
                    // Try to get extension from original record if user removed it?
                    // Or trust the user input (maybe they want no extension)
                    // Let's check original record extension just in case
                    $oldExt = pathinfo($this->record->name, PATHINFO_EXTENSION);
                    if ($oldExt) {
                        $data['name'] = $sluggedName . '.' . $oldExt;
                    } else {
                        $data['name'] = $sluggedName;
                    }
                }
            }
        }

        return $data;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Don't show files field for existing records
        unset($data['files']);
        return $data;
    }
}
