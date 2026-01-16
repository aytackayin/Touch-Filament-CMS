<?php

namespace App\Filament\Resources\TouchFileManager\Pages;

use App\Filament\Resources\TouchFileManager\TouchFileManagerResource;
use App\Models\TouchFile;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateTouchFile extends CreateRecord
{
    protected static string $resource = TouchFileManagerResource::class;

    #[\Livewire\Attributes\Url]
    public ?string $parent_id = null;

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            static::getResource()::getUrl() => static::getResource()::getBreadcrumb(),
        ];

        $parentId = $this->parent_id;

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

    public ?string $previousUrl = null;

    public function mount(): void
    {
        parent::mount();
        $this->previousUrl = url()->previous();
    }

    protected function handleRecordCreation(array $data): TouchFile
    {
        // Handle file uploads
        if (!empty($data['files'])) {
            // Pass video thumbnails data if available from the form data
            $data['video_thumbnails'] = $data['video_thumbnails_store'] ?? null;

            $uploadedFiles = $this->handleFileUploads($data);

            // Return the last uploaded file as the created record
            return end($uploadedFiles);
        }

        // This shouldn't happen as we only use this page for file uploads
        // but just in case, create a record from the data
        return TouchFile::create($data);
    }

    protected function handleFileUploads(array $data): array
    {
        $files = $data['files'];
        $parentId = $data['parent_id'] ?? $this->parent_id;
        $videoThumbnails = $data['video_thumbnails'] ?? null;
        $videoThumbnailsData = [];

        if ($videoThumbnails) {
            $decoded = json_decode($videoThumbnails, true);
            if (is_array($decoded)) {
                $videoThumbnailsData = $decoded;
            }
        }

        $uploadedFiles = [];
        $disk = Storage::disk('attachments');

        // Initialize Intervention Image Manager
        $manager = null;
        if (class_exists(\Intervention\Image\ImageManager::class)) {
            $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
        }

        foreach ($files as $file) {
            // Get file information
            $fileName = basename($file);
            $filePath = $file; // This is the temp path

            // Move from temp to permanent location
            $permanentPath = ($parentId ? $this->getParentPath($parentId) . '/' : '') . $fileName;

            // Ensure directory exists
            $permPathNormalized = str_replace('\\', '/', $permanentPath);
            $dir = pathinfo($permPathNormalized, PATHINFO_DIRNAME);

            // Only create directory if it's not root and doesn't exist
            if ($dir !== '.' && !$disk->exists($dir)) {
                $disk->makeDirectory($dir);
            }

            // Move the file
            $disk->move($filePath, $permanentPath);

            // Get file info
            $mimeType = $disk->mimeType($permanentPath);
            $size = $disk->size($permanentPath);
            $type = TouchFile::determineFileType($mimeType);

            // Handle Thumbnails
            if ($type === 'image' && $manager) {
                try {
                    // Create thumbs directory if root, or ensure it exists if nested
                    $permPathNormalized = str_replace('\\', '/', $permanentPath);
                    $dir = pathinfo($permPathNormalized, PATHINFO_DIRNAME);

                    if ($dir === '.') {
                        $thumbsDir = 'thumbs';
                    } else {
                        $thumbsDir = $dir . '/thumbs';
                    }

                    if (!$disk->exists($thumbsDir)) {
                        $disk->makeDirectory($thumbsDir);
                    }

                    $thumbPath = $thumbsDir . '/' . $fileName;
                    $fullPath = $disk->path($permanentPath);
                    $thumbFullPath = $disk->path($thumbPath);

                    // Generate thumbnail
                    $image = $manager->read($fullPath);
                    $image->scale(width: 150);
                    $image->save($thumbFullPath);

                } catch (\Exception $e) {
                    \Log::error('Image thumbnail generation failed: ' . $e->getMessage());
                }
            } elseif ($type === 'video') {
                // Find matching video thumbnail
                $originalName = $fileName; // Since preserveFilenames() is true, this should match up mostly

                // We need to look up in the videoThumbnailsData for matching filename
                // video-thumbnail-handler JS uses file.name which is client side name.
                // Filament sanitizer slugifies the name. 
                // We will try to match loosely or use the logic from Blog model

                foreach ($videoThumbnailsData as $thumbData) {
                    $thumbFilename = $thumbData['filename'] ?? '';
                    // Check if our current file matches this thumbnail's source file
                    // We slugify the thumbFilename extensionless part to match Filament's naming
                    $nameNoExt = pathinfo($thumbFilename, PATHINFO_FILENAME);
                    $ext = pathinfo($thumbFilename, PATHINFO_EXTENSION);
                    $slugged = \Illuminate\Support\Str::slug($nameNoExt) . '.' . $ext;

                    if ($slugged === $fileName) {
                        // Found match
                        $base64Data = $thumbData['thumbnail'] ?? null;
                        if ($base64Data) {
                            try {
                                $permPathNormalized = str_replace('\\', '/', $permanentPath);
                                $dir = pathinfo($permPathNormalized, PATHINFO_DIRNAME);

                                if ($dir === '.') {
                                    $thumbsDir = 'thumbs';
                                } else {
                                    $thumbsDir = $dir . '/thumbs';
                                }

                                if (!$disk->exists($thumbsDir)) {
                                    $disk->makeDirectory($thumbsDir);
                                }

                                $thumbName = pathinfo($fileName, PATHINFO_FILENAME) . '.jpg';
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
                                \Log::error('Video thumbnail save failed: ' . $e->getMessage());
                            }
                        }
                        break;
                    }
                }
            }

            // Create database record
            $uploadedFiles[] = TouchFile::create([
                'name' => $fileName,
                'path' => $permanentPath,
                'type' => $type,
                'mime_type' => $mimeType,
                'size' => $size,
                'parent_id' => $parentId,
                'is_folder' => false,
            ]);
        }

        return $uploadedFiles;
    }

    protected function getParentPath(?int $parentId): string
    {
        if (!$parentId) {
            return '';
        }

        $parent = TouchFile::find($parentId);
        if (!$parent) {
            return '';
        }

        $path = $parent->name;
        while ($parent->parent) {
            $parent = $parent->parent;
            $path = $parent->name . '/' . $path;
        }

        return $path;
    }

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Files uploaded successfully';
    }
}
