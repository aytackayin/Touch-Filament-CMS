<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Exception;
use App\Settings\GeneralSettings;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class TouchFile extends Model
{
    use HasFactory;

    /**
     * Get names of all models in the App\Models namespace
     */
    public static function getAllModelFolderNames(): array
    {
        $names = [];
        $modelPath = app_path('Models');

        if (is_dir($modelPath)) {
            foreach (scandir($modelPath) as $file) {
                if (str_ends_with($file, '.php')) {
                    $names[] = Str::lower(substr($file, 0, -4));
                }
            }
        }

        return array_unique($names);
    }

    public static function getDynamicModelAssociations(): array
    {
        $associations = [];
        $modelPath = app_path('Models');

        if (is_dir($modelPath)) {
            foreach (scandir($modelPath) as $file) {
                if (str_ends_with($file, '.php')) {
                    $className = 'App\\Models\\' . substr($file, 0, -4);
                    if (class_exists($className)) {
                        $traits = class_uses_recursive($className);
                        if (isset($traits['App\\Traits\\HasFileManagerSync'])) {
                            if (method_exists($className, 'getStorageFolder')) {
                                $associations[$className::getStorageFolder()] = $className;
                            } else {
                                $associations[Str::lower(class_basename($className))] = $className;
                            }
                        }
                    }
                }
            }
        }

        return $associations;
    }

    public static function getReservedNames(): array
    {
        $configNames = config('touch-file-manager.reserved_names', []);
        $modelFolders = static::getAllModelFolderNames();

        return array_unique(array_merge($configNames, $modelFolders));
    }

    protected $fillable = [
        'user_id',
        'edit_user_id',
        'name',
        'alt',
        'path',
        'type',
        'mime_type',
        'size',
        'parent_id',
        'is_folder',
        'metadata',
        'tags',
    ];

    protected $casts = [
        'is_folder' => 'boolean',
        'size' => 'integer',
        'metadata' => 'array',
        'tags' => 'array',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(TouchFile::class, 'parent_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edit_user_id');
    }

    public function children()
    {
        return $this->hasMany(TouchFile::class, 'parent_id');
    }

    public function getUrlAttribute(): ?string
    {
        if ($this->is_folder || !$this->path)
            return null;
        return Storage::disk('attachments')->url($this->path);
    }

    public function getThumbnailSizes(): array
    {
        $path = str_replace('\\', '/', $this->path);
        $parts = explode('/', $path);
        $rootFolder = $parts[0] ?? null;

        if ($rootFolder) {
            $modelSizes = config("{$rootFolder}.thumb_sizes");
            if (is_array($modelSizes) && !empty($modelSizes))
                return $modelSizes;
        }

        $globalSettings = app(GeneralSettings::class)->thumbnail_sizes;
        if (is_array($globalSettings) && !empty($globalSettings))
            return $globalSettings;

        return config('touch-file-manager.thumb_sizes', [150]);
    }

    public function getThumbnailPathAttribute(): ?string
    {
        if ($this->is_folder || !$this->path)
            return null;

        $disk = Storage::disk('attachments');
        $path = str_replace('\\', '/', $this->path);
        $dir = pathinfo($path, PATHINFO_DIRNAME);
        $nameOnly = pathinfo($path, PATHINFO_FILENAME);
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        $thumbsDir = ($dir === '.' || $dir === '') ? 'thumbs' : "{$dir}/thumbs";
        $sizes = $this->getThumbnailSizes();
        rsort($sizes); // Prioritize larger/better quality

        // Clear stat cache before checking existence
        if (function_exists('clearstatcache')) {
            clearstatcache(true, $disk->path($path));
        }

        if ($this->type === 'video') {
            $slugName = Str::slug($nameOnly);
            foreach ($sizes as $size) {
                // Check slugged with size
                $thumbPath = "{$thumbsDir}/{$slugName}_{$size}.jpg";
                if ($disk->exists($thumbPath)) {
                    return $thumbPath;
                }

                // Also check non-slugged with size for compatibility
                $thumbPathNonSlug = "{$thumbsDir}/{$nameOnly}_{$size}.jpg";
                if ($disk->exists($thumbPathNonSlug))
                    return $thumbPathNonSlug;
            }

            // Legacy/Fallback (No size suffix)
            $legacyThumbSlug = "{$thumbsDir}/{$slugName}.jpg";
            if ($disk->exists($legacyThumbSlug))
                return $legacyThumbSlug;

            $legacyThumb = "{$thumbsDir}/{$nameOnly}.jpg";
            if ($disk->exists($legacyThumb))
                return $legacyThumb;
        } else { // This is the 'image' or 'other' type block
            foreach ($sizes as $size) {
                $thumbPath = "{$thumbsDir}/{$nameOnly}_{$size}.{$extension}";
                if ($disk->exists($thumbPath))
                    return $thumbPath;
            }

            // If we are here and still no thumb, try to force generate it NOW
            if ($this->type === 'image' && strtolower($extension) !== 'svg') {
                $this->generateThumbnails();
                // Clear stat cache again after generation
                if (function_exists('clearstatcache')) {
                    clearstatcache(true, $disk->path($path));
                }
                // Check one more time after generation
                foreach ($sizes as $size) {
                    $thumbPath = "{$thumbsDir}/{$nameOnly}_{$size}.{$extension}";
                    if ($disk->exists($thumbPath))
                        return $thumbPath;
                }
            }

            // Legacy/Fallback
            $legacyThumb = "{$thumbsDir}/" . basename($path);
            if ($disk->exists($legacyThumb))
                return $legacyThumb;
        }

        // Return original if it's an image, or null if it's something else we can't thumb
        return ($this->type === 'image') ? $this->path : null;
    }

    public function getThumbnailUrl($path = null): ?string
    {
        $thumbPath = $this->getThumbnailPathAttribute(); // Call the accessor

        if ($thumbPath) {
            return \Illuminate\Support\Facades\Storage::disk('attachments')->url($thumbPath);
        }

        return null;
    }

    public function getExtensionAttribute(): string
    {
        return pathinfo($this->path ?? '', PATHINFO_EXTENSION);
    }

    public static function determineFileType(string $mimeType, ?string $path = null): string
    {
        $mimeType = strtolower($mimeType);
        if (str_starts_with($mimeType, 'image/') || $mimeType === 'image/svg+xml')
            return 'image';
        if (str_starts_with($mimeType, 'video/'))
            return 'video';

        if ($path) {
            $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg']))
                return 'image';
            if (in_array($ext, ['mp4', 'mov', 'avi', 'wmv', 'flv', 'webm', 'mpeg']))
                return 'video';
            if (in_array($ext, ['pdf', 'doc', 'docx', 'txt', 'rtf']))
                return 'document';
            if (in_array($ext, ['xls', 'xlsx', 'csv']))
                return 'spreadsheet';
            if (in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz']))
                return 'archive';
        }

        return 'other';
    }

    public static function registerFile(string $path): void
    {
        $disk = Storage::disk('attachments');
        $path = str_replace('\\', '/', $path);

        // Use a more robust check for immediate availability after move
        if (function_exists('clearstatcache')) {
            clearstatcache(true, $disk->path($path));
        }

        if (!$disk->exists($path)) {
            // Tiny sleep and retry for slow file systems
            usleep(100000); // 100ms
            if (!$disk->exists($path))
                return;
        }

        $existing = static::where('path', $path)->where('is_folder', false)->first();
        if ($existing) {
            $newSize = $disk->size($path);
            if ($existing->size !== $newSize) {
                $existing->update(['size' => $newSize]);
            }
            return;
        }

        $parts = explode('/', $path);
        $fileName = array_pop($parts);
        $currentPath = '';
        $parentId = null;

        foreach ($parts as $part) {
            $currentPath = $currentPath ? "{$currentPath}/{$part}" : $part;
            $folder = static::firstOrCreate(
                ['path' => $currentPath, 'is_folder' => true],
                ['name' => $part, 'parent_id' => $parentId]
            );
            $parentId = $folder->id;
        }

        try {
            $mimeType = $disk->mimeType($path) ?? '';
        } catch (Exception $e) {
            $mimeType = '';
        }

        static::create([
            'name' => $fileName,
            'path' => $path,
            'is_folder' => false,
            'parent_id' => $parentId,
            'mime_type' => $mimeType,
            'size' => $disk->size($path),
            'type' => static::determineFileType($mimeType, $path),
        ]);
    }

    public static function unregisterFile(string $path): void
    {
        $path = str_replace('\\', '/', $path);
        $file = static::where('path', $path)->where('is_folder', false)->first();
        if ($file)
            $file->delete();
    }

    public function generateThumbnails(): void
    {
        if ($this->is_folder || $this->type !== 'image' || !$this->path)
            return;

        $disk = Storage::disk('attachments');

        // Clear stat cache before checking existence
        if (function_exists('clearstatcache')) {
            clearstatcache(true, $disk->path($this->path));
        }

        if (!$disk->exists($this->path))
            return;

        // Skip SVGs for thumbnail generation
        if (strtolower(pathinfo($this->path, PATHINFO_EXTENSION)) === 'svg') {
            return;
        }

        try {
            $manager = new ImageManager(new Driver());
            $path = str_replace('\\', '/', $this->path);
            $dir = pathinfo($path, PATHINFO_DIRNAME);
            $nameOnly = pathinfo($path, PATHINFO_FILENAME);
            $extension = pathinfo($path, PATHINFO_EXTENSION);
            $thumbsDir = ($dir === '.' || $dir === '') ? 'thumbs' : "{$dir}/thumbs";

            if (!$disk->exists($thumbsDir)) {
                $disk->makeDirectory($thumbsDir, 0755, true);
            }

            // CLEANUP
            $allThumbFiles = $disk->files($thumbsDir);
            foreach ($allThumbFiles as $fullThumbPath) {
                $tFile = basename($fullThumbPath);
                if ($tFile === $this->name || str_starts_with($tFile, $nameOnly . '_')) {
                    $disk->delete($fullThumbPath);
                }
            }

            foreach ($this->getThumbnailSizes() as $size) {
                $target = "{$thumbsDir}/{$nameOnly}_{$size}.{$extension}";
                $image = $manager->read($disk->path($this->path));
                $image->scale(width: (int) $size);
                $image->save($disk->path($target));
            }
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Thumbnail generation failed for {$this->path}: " . $e->getMessage());
        }
    }

    protected static function booted()
    {
        static::saved(function ($model) {
            if (!$model->is_folder && $model->type === 'image') {
                $model->generateThumbnails();
            }
        });

        static::deleting(function ($file) {
            $disk = Storage::disk('attachments');
            $path = str_replace('\\', '/', $file->path);

            if ($file->is_folder) {
                foreach ($file->children as $child)
                    $child->delete();
                if ($disk->exists($path))
                    $disk->deleteDirectory($path);
            } else {
                if ($disk->exists($path))
                    $disk->delete($path);

                $dir = dirname($path);
                $thumbsDir = ($dir === '.' || $dir === '') ? 'thumbs' : "{$dir}/thumbs";

                if ($disk->exists($thumbsDir)) {
                    $nameOnly = pathinfo($file->name, PATHINFO_FILENAME);
                    $ext = pathinfo($file->name, PATHINFO_EXTENSION);
                    $sizes = $file->getThumbnailSizes();

                    foreach ($sizes as $size) {
                        $disk->delete("{$thumbsDir}/{$nameOnly}_{$size}.{$ext}");
                        $disk->delete("{$thumbsDir}/{$nameOnly}_{$size}.jpg");
                    }
                    $disk->delete("{$thumbsDir}/{$file->name}");
                    $disk->delete("{$thumbsDir}/{$nameOnly}.jpg");
                }
            }
        });
    }
}
