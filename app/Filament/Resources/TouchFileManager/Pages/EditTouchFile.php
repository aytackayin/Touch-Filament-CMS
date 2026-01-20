<?php

namespace App\Filament\Resources\TouchFileManager\Pages;

use App\Filament\Resources\TouchFileManager\TouchFileManagerResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use App\Models\TouchFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Exception;

class EditTouchFile extends EditRecord
{
    protected static string $resource = TouchFileManagerResource::class;

    #[\Livewire\Attributes\Url]
    public ?string $parent_id = null;

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            static::getResource()::getUrl() => static::getResource()::getBreadcrumb(),
        ];

        // For editing, we usually want to show the path ending at the parent folder
        $parentId = $this->parent_id ?? ($this->record ? $this->record->parent_id : null);

        if ($parentId) {
            $folder = TouchFile::find($parentId);
            $trail = [];
            while ($folder) {
                array_unshift($trail, [
                    'url' => static::getResource()::getUrl('index', ['parent_id' => $folder->id]),
                    'label' => $folder->name,
                ]);
                $folder = $folder->parent;
            }

            foreach ($trail as $crumb) {
                $breadcrumbs[$crumb['url']] = $crumb['label'];
            }
        }

        $breadcrumbs[] = $this->getBreadcrumb();

        return $breadcrumbs;
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading(fn() => $this->record->is_folder ? 'Delete Folder' : 'Delete File')
                ->modalDescription(fn() => $this->record->is_folder
                    ? 'Are you sure you want to delete this folder? All files and subfolders inside will also be deleted.'
                    : 'Are you sure you want to delete this file?')
                ->successRedirectUrl(function () {
                    $parentId = $this->parent_id ?? ($this->record ? $this->record->parent_id : null);
                    return $parentId
                        ? $this->getResource()::getUrl('index', ['parent_id' => $parentId])
                        : $this->getResource()::getUrl('index');
                }),
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
        $parentId = $this->parent_id ?? ($this->record ? $this->record->parent_id : null);

        if ($parentId) {
            return $this->getResource()::getUrl('index', ['parent_id' => $parentId]);
        }

        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        // 1. If path is removed (file deleted from upload), delete the record
        if (!$record->is_folder && empty($data['path'])) {
            $parentId = $this->parent_id ?? $record->parent_id;
            $record->delete();

            $redirectUrl = $parentId
                ? $this->getResource()::getUrl('index', ['parent_id' => $parentId])
                : $this->getResource()::getUrl('index');

            $this->redirect($redirectUrl);
            return $record;
        }

        $disk = Storage::disk('attachments');

        // Determine effective new path (might be same as old if filename preserved)
        $newFilename = $data['path'] ?? $record->path;

        // Calculate Target Directory explicitly to ensure correctness
        $targetDir = '.';
        $parentId = $data['parent_id'] ?? $record->parent_id;

        if ($parentId) {
            $parent = TouchFile::find($parentId);
            if ($parent) {
                $targetDir = $parent->full_path;
            }
        }

        // Normalized target path
        $targetPath = ($targetDir === '.' ? '' : $targetDir . '/') . basename($newFilename);

        // Check triggers
        $pathChanged = isset($data['path']) && $data['path'] !== $record->path;

        // Also check if content was modified (e.g. crop/replace with same name)
        // We compare size, or loosely assume if we are saving and it's an image, we might want to refresh.
        // Size comparison is the most robust way to detect actual file change without content hashing.
        $contentChanged = false;
        if ($disk->exists($targetPath) && !$record->is_folder) {
            $currentSize = $disk->size($targetPath);
            if ($currentSize !== $record->size) {
                $contentChanged = true;
            }
        }

        // 2. Handle Cleanup / Move ONLY if path changed significantly
        // Note: If Filament's preserveFilenames pushed the file to $targetPath already, we don't need to move it to itself.
        if ($pathChanged && !$record->is_folder) {
            $sourcePath = $data['path']; // The temp or new path provided by form

            // A. Clean up OLD file and thumb if they are different from new path
            // (We don't want to delete the file we just uploaded if paths overlap via some race condition, but usually they differ)
            if ($record->path !== $targetPath && $disk->exists($record->path)) {
                $disk->delete($record->path);
            }

            $oldThumbPath = $record->thumbnail_path;
            if ($oldThumbPath && $disk->exists($oldThumbPath)) {
                $disk->delete($oldThumbPath);
            }

            // B. Move NEW file to Target if source != target
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
        }

        // 3. Regenerate Data & Thumbnails if Content OR Path Changed
        if (($pathChanged || $contentChanged) && !$record->is_folder && $disk->exists($targetPath)) {

            // Update Metadata
            $data['path'] = $targetPath;
            $data['name'] = basename($targetPath);
            $data['size'] = $disk->size($targetPath);
            $data['mime_type'] = $disk->mimeType($targetPath);

            // C. Generate NEW Thumbnail (if image)
            if (class_exists(ImageManager::class) && str_starts_with($disk->mimeType($targetPath), 'image/')) {
                try {
                    $manager = new ImageManager(new Driver());

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

                } catch (Exception $e) {
                    Log::error('Thumbnail generation failed on edit: ' . $e->getMessage());
                }
            }
            // D. Handle Video Thumbnail (from hidden field)
            elseif ($disk->mimeType($targetPath) && str_starts_with($disk->mimeType($targetPath), 'video/')) {
                $videoThumbnails = $data['video_thumbnails_store'] ?? null;
                if ($videoThumbnails) {
                    $videoThumbnailsData = json_decode($videoThumbnails, true);
                    if (is_array($videoThumbnailsData) && count($videoThumbnailsData) > 0) {

                        $sourcePath = $data['path'] ?? $targetPath;
                        $currentFileName = basename($sourcePath);

                        $thumbDataCandidate = null;

                        // 1. Try to find a specific match based on filename
                        foreach ($videoThumbnailsData as $thumbData) {
                            $thumbFilename = $thumbData['filename'] ?? '';
                            $nameNoExt = pathinfo($thumbFilename, PATHINFO_FILENAME);
                            $ext = pathinfo($thumbFilename, PATHINFO_EXTENSION);
                            $slugged = Str::slug($nameNoExt) . '.' . $ext;

                            // Loose matching
                            if ($slugged === $currentFileName || $thumbFilename === $currentFileName) {
                                $thumbDataCandidate = $thumbData;
                                break;
                            }
                        }

                        // 2. Fallback: If no strict filename match is found, use the LAST thumbnail in the array.
                        // This handles the case where the server-side filename (preserved old name) 
                        // differs from the client-side filename (new upload), causing a name mismatch.
                        // We assume the most recently generated thumbnail belongs to the current upload.
                        if (!$thumbDataCandidate && !empty($videoThumbnailsData)) {
                            $thumbDataCandidate = end($videoThumbnailsData);
                            // Reset array pointer just in case
                            reset($videoThumbnailsData);
                        }

                        if ($thumbDataCandidate) {
                            $base64Data = $thumbDataCandidate['thumbnail'] ?? null;
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
                                } catch (Exception $e) {
                                    Log::error('Video thumbnail save failed on edit: ' . $e->getMessage());
                                }
                            }
                        }
                    }
                }
            }
        }

        $data['edit_user_id'] = auth()->id();
        $record->update($data);
        return $record;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (isset($data['name'])) {
            if ($this->record->is_folder) {
                // For folders, just slugify the whole name
                $data['name'] = Str::slug($data['name']);
            } else {
                // For files, preserve extension
                $originalName = $data['name'];
                $extension = pathinfo($originalName, PATHINFO_EXTENSION);
                $nameNoExt = pathinfo($originalName, PATHINFO_FILENAME);

                $sluggedName = Str::slug($nameNoExt);

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
