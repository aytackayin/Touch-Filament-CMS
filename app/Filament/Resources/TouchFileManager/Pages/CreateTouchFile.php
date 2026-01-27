<?php

namespace App\Filament\Resources\TouchFileManager\Pages;

use App\Filament\Resources\TouchFileManager\TouchFileManagerResource;
use App\Models\TouchFile;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Exception;
use App\Settings\GeneralSettings;

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

        $manager = null;
        if (class_exists(ImageManager::class)) {
            $manager = new ImageManager(new Driver());
        }

        foreach ($files as $file) {
            $fileName = basename($file);
            $filePath = $file;

            $permanentPath = ($parentId ? $this->getParentPath($parentId) . '/' : '') . $fileName;

            // Ensure destination directory exists
            $permPathNormalized = str_replace('\\', '/', $permanentPath);
            $dir = pathinfo($permPathNormalized, PATHINFO_DIRNAME);

            if ($dir !== '.' && !$disk->exists($dir)) {
                $disk->makeDirectory($dir);
            }

            $disk->move($filePath, $permanentPath);

            $mimeType = $disk->mimeType($permanentPath);
            $size = $disk->size($permanentPath);
            $type = TouchFile::determineFileType($mimeType);

            // Handle image thumbnails
            if ($type === 'image' && $manager) {
                try {
                    $nameOnly = pathinfo($fileName, PATHINFO_FILENAME);
                    $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                    $dir = pathinfo(str_replace('\\', '/', $permanentPath), PATHINFO_DIRNAME);
                    $thumbsDir = $dir === '.' ? 'thumbs' : $dir . '/thumbs';

                    if (!$disk->exists($thumbsDir)) {
                        $disk->makeDirectory($thumbsDir);
                    }

                    $sizes = $this->getThumbnailSizes($parentId);
                    foreach ($sizes as $size) {
                        $thumbPath = $thumbsDir . '/' . $nameOnly . '_' . $size . '.' . $extension;
                        $image = $manager->read($disk->path($permanentPath));
                        $image->scale(width: (int) $size);
                        $image->save($disk->path($thumbPath));
                    }
                } catch (Exception $e) {
                    Log::error('Thumbnail generation failed: ' . $e->getMessage());
                }
            } elseif ($type === 'video') {
                foreach ($videoThumbnailsData as $thumbData) {
                    $thumbFilename = $thumbData['filename'] ?? '';
                    $nameNoExt = pathinfo($thumbFilename, PATHINFO_FILENAME);
                    $ext = pathinfo($thumbFilename, PATHINFO_EXTENSION);
                    $slugged = Str::slug($nameNoExt) . '.' . $ext;

                    if ($slugged === $fileName) {
                        $base64Data = $thumbData['thumbnail'] ?? null;
                        if ($base64Data && $manager) {
                            try {
                                $dir = pathinfo(str_replace('\\', '/', $permanentPath), PATHINFO_DIRNAME);
                                $thumbsDir = $dir === '.' ? 'thumbs' : $dir . '/thumbs';

                                if (!$disk->exists($thumbsDir)) {
                                    $disk->makeDirectory($thumbsDir);
                                }

                                $nameOnly = pathinfo($fileName, PATHINFO_FILENAME);
                                $imageData = $base64Data;
                                if (str_contains($imageData, 'data:image')) {
                                    $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
                                }
                                $imageData = str_replace(' ', '+', $imageData);
                                $decodedImage = base64_decode($imageData);

                                if ($decodedImage !== false) {
                                    $sizes = $this->getThumbnailSizes($parentId);
                                    foreach ($sizes as $size) {
                                        $thumbPath = $thumbsDir . '/' . $nameOnly . '_' . $size . '.jpg';
                                        $image = $manager->read($decodedImage);
                                        $image->scale(width: (int) $size);
                                        $disk->put($thumbPath, $image->toJpeg()->toString());
                                    }
                                }
                            } catch (Exception $e) {
                                Log::error('Video thumbnail save failed: ' . $e->getMessage());
                            }
                        }
                        break;
                    }
                }
            }

            // Create model record
            $uploadedFiles[] = TouchFile::create([
                'user_id' => auth()->id(),
                'name' => $fileName,
                'alt' => $data['alt'] ?? null,
                'tags' => $data['tags'] ?? null,
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

    protected function getThumbnailSizes(?string $parentId): array
    {
        $rootFolder = null;

        if ($parentId) {
            $parent = TouchFile::find($parentId);
            if ($parent) {
                $path = str_replace('\\', '/', $parent->full_path);
                $parts = explode('/', $path);
                $rootFolder = $parts[0] ?? null;
            }
        }

        // 1. Check Model Specific Config (e.g., blog.php)
        if ($rootFolder) {
            $modelSizes = config("{$rootFolder}.thumb_sizes");
            if (is_array($modelSizes) && !empty($modelSizes)) {
                return $modelSizes;
            }
        }

        // 2. Check Global Site Settings (Panel)
        $globalSettings = app(GeneralSettings::class)->thumbnail_sizes;
        if (is_array($globalSettings) && !empty($globalSettings)) {
            return $globalSettings;
        }

        // 3. Check FileManager Default Config
        $defaultConfig = config('touch-file-manager.thumb_sizes');
        if (is_array($defaultConfig) && !empty($defaultConfig)) {
            return $defaultConfig;
        }

        return [150];
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
