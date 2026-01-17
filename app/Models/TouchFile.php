<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TouchFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'path',
        'type',
        'mime_type',
        'size',
        'parent_id',
        'is_folder',
        'metadata',
    ];

    protected $casts = [
        'is_folder' => 'boolean',
        'size' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the parent folder
     */
    public function parent()
    {
        return $this->belongsTo(TouchFile::class, 'parent_id');
    }

    /**
     * Get all children (files and folders)
     */
    public function children()
    {
        return $this->hasMany(TouchFile::class, 'parent_id');
    }

    /**
     * Get only folder children
     */
    public function folders()
    {
        return $this->hasMany(TouchFile::class, 'parent_id')->where('is_folder', true);
    }

    /**
     * Get only file children
     */
    public function files()
    {
        return $this->hasMany(TouchFile::class, 'parent_id')->where('is_folder', false);
    }

    /**
     * Get full path including parent folders
     */
    public function getFullPathAttribute(): string
    {
        if ($this->parent) {
            return $this->parent->full_path . '/' . $this->name;
        }
        return $this->name;
    }

    /**
     * Get file URL if it's a file
     */
    public function getUrlAttribute(): ?string
    {
        if ($this->is_folder) {
            return null;
        }
        return Storage::disk('attachments')->url($this->path);
    }

    /**
     * Get human readable file size
     */
    public function getHumanSizeAttribute(): string
    {
        if ($this->is_folder) {
            return '-';
        }

        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get file icon based on type
     */
    public function getIconAttribute(): string
    {
        if ($this->is_folder) {
            return 'heroicon-o-folder';
        }

        return match ($this->type) {
            'image' => 'heroicon-o-photo',
            'video' => 'heroicon-o-film',
            'document' => 'heroicon-o-document-text',
            'archive' => 'heroicon-o-archive-box',
            'spreadsheet' => 'heroicon-o-table-cells',
            'presentation' => 'heroicon-o-presentation-chart-bar',
            default => 'heroicon-o-document',
        };
    }

    /**
     * Get thumbnail path relative to disk root
     */
    public function getThumbnailPathAttribute(): ?string
    {
        if ($this->is_folder) {
            return null;
        }

        $disk = Storage::disk('attachments');

        // Handle root directory case properly and normalize separators
        $path = str_replace('\\', '/', $this->path);
        $dir = pathinfo($path, PATHINFO_DIRNAME);

        if ($dir === '.') {
            $thumbsDir = 'thumbs';
        } else {
            $thumbsDir = $dir . '/thumbs';
        }

        // Check for image thumbnail (same name)
        $thumbPath = $thumbsDir . '/' . basename($path);

        // Check for video thumbnail (.jpg extension)
        if ($this->type === 'video') {
            $thumbName = pathinfo($this->path, PATHINFO_FILENAME) . '.jpg';
            $thumbPath = $thumbsDir . '/' . $thumbName;
            if ($disk->exists($thumbPath)) {
                return $thumbPath;
            }
        }

        // Check for image thumbnail
        if ($this->type === 'image') {
            if ($disk->exists($thumbPath)) {
                return $thumbPath;
            }
            // Fallback to original image if no thumb
            return $this->path;
        }

        return null;
    }

    public function getExtensionAttribute(): string
    {
        return pathinfo($this->path, PATHINFO_EXTENSION);
    }

    /**
     * Delete file from storage when model is deleted
     */
    protected static function booted()
    {
        static::updating(function ($model) {
            $disk = Storage::disk('attachments');

            // 📂 CASE 1: FOLDER RENAME / MOVE
            if ($model->is_folder && ($model->isDirty('name') || $model->isDirty('parent_id'))) {
                // 1. Calculate Old Path
                $oldParentId = $model->getOriginal('parent_id');
                $oldName = $model->getOriginal('name');

                $oldPath = $oldName;
                if ($oldParentId) {
                    $oldParent = static::find($oldParentId);
                    if ($oldParent) {
                        $oldPath = $oldParent->full_path . '/' . $oldName;
                    }
                }

                // 2. Calculate New Path
                $newParentId = $model->parent_id;
                $newName = $model->name;

                $newPath = $newName;
                if ($newParentId) {
                    $newParent = static::find($newParentId);
                    if ($newParent) {
                        $newPath = $newParent->full_path . '/' . $newName;
                    }
                }

                // 3. Move Directory on Disk
                if ($oldPath !== $newPath && $disk->exists($oldPath)) {
                    $disk->move($oldPath, $newPath);
                }

                // 4. Update Database Paths for ALL Children (Recursive)
                // Since path column is stored as string, we need to update all records starting with oldPath
                // We add a slash to ensure we match directory boundaries (e.g. avoid matching "Folder 2" when renaming "Folder")
                $allChildren = static::where('path', 'like', $oldPath . '/%')->get();

                foreach ($allChildren as $child) {
                    // Replace the start of the path with the new path
                    $child->path = preg_replace('/^' . preg_quote($oldPath, '/') . '/', $newPath, $child->path, 1);
                    $child->saveQuietly();
                }
            }

            // 📄 CASE 2: FILE RENAME / MOVE
            elseif (!$model->is_folder && ($model->isDirty('name') || $model->isDirty('parent_id'))) {
                $oldPath = $model->getOriginal('path');

                // Calculate New Path Manually
                $parentPath = '';
                if ($model->parent_id) {
                    $parent = static::find($model->parent_id);
                    if ($parent) {
                        $parentPath = $parent->full_path . '/';
                    }
                }
                $newPath = $parentPath . $model->name;

                // Move File on Disk
                if ($oldPath && $oldPath !== $newPath && $disk->exists($oldPath)) {
                    $disk->move($oldPath, $newPath);

                    // Also Move Thumbnail if exists
                    $thumbsDir = dirname($oldPath) . '/thumbs';
                    if ($thumbsDir === './thumbs')
                        $thumbsDir = 'thumbs';

                    $newThumbsDir = dirname($newPath) . '/thumbs';
                    if ($newThumbsDir === './thumbs')
                        $newThumbsDir = 'thumbs';

                    // Check for image thumbnail
                    $oldThumbPath = $thumbsDir . '/' . basename($oldPath);
                    $newThumbPath = $newThumbsDir . '/' . $model->name;

                    if ($disk->exists($oldThumbPath)) {
                        // Ensure new thumb dir exists
                        if (!$disk->exists($newThumbsDir)) {
                            $disk->makeDirectory($newThumbsDir);
                        }
                        $disk->move($oldThumbPath, $newThumbPath);
                    }

                    // Check for video thumbnail
                    $oldNameNoExt = pathinfo(basename($oldPath), PATHINFO_FILENAME);
                    $newNameNoExt = pathinfo($model->name, PATHINFO_FILENAME);

                    $oldVideoThumb = $thumbsDir . '/' . $oldNameNoExt . '.jpg';
                    $newVideoThumb = $newThumbsDir . '/' . $newNameNoExt . '.jpg';

                    if ($disk->exists($oldVideoThumb)) {
                        if (!$disk->exists($newThumbsDir)) {
                            $disk->makeDirectory($newThumbsDir);
                        }
                        $disk->move($oldVideoThumb, $newVideoThumb);
                    }
                }

                // Update Path in Model
                $model->path = $newPath;
            }
        });

        static::deleting(function ($file) {
            if ($file->is_folder) {
                // Delete all children recursively
                foreach ($file->children as $child) {
                    $child->delete();
                }

                // Also delete the directory itself from storage
                $disk = Storage::disk('attachments');
                // Calculate folder path
                // We can't rely on 'path' column as it might be null for folders ideally, 
                // but we can calculate it from full_path
                $folderPath = $file->full_path;

                if ($disk->exists($folderPath)) {
                    $disk->deleteDirectory($folderPath);
                }
            } else {
                // Delete the actual file from storage
                if (Storage::disk('attachments')->exists($file->path)) {
                    Storage::disk('attachments')->delete($file->path);
                }

                // Also delete thumbnails
                $disk = Storage::disk('attachments');
                $thumbsDir = dirname($file->path) . '/thumbs';
                if ($thumbsDir === './thumbs')
                    $thumbsDir = 'thumbs';

                $thumbPath = $thumbsDir . '/' . basename($file->path);
                if ($disk->exists($thumbPath)) {
                    $disk->delete($thumbPath);
                }

                // Video thumb
                $nameNoExt = pathinfo(basename($file->path), PATHINFO_FILENAME);
                $videoThumb = $thumbsDir . '/' . $nameNoExt . '.jpg';
                if ($disk->exists($videoThumb)) {
                    $disk->delete($videoThumb);
                }
            }
        });
    }

    /**
     * Determine file type based on mime type
     */
    public static function determineFileType(string $mimeType): string
    {
        if (str_starts_with($mimeType, 'image/')) {
            return 'image';
        }

        if (str_starts_with($mimeType, 'video/')) {
            return 'video';
        }

        $documentTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'text/plain',
        ];

        if (in_array($mimeType, $documentTypes)) {
            return 'document';
        }

        $spreadsheetTypes = [
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ];

        if (in_array($mimeType, $spreadsheetTypes)) {
            return 'spreadsheet';
        }

        $presentationTypes = [
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        ];

        if (in_array($mimeType, $presentationTypes)) {
            return 'presentation';
        }

        $archiveTypes = [
            'application/zip',
            'application/x-rar-compressed',
            'application/x-7z-compressed',
            'application/x-tar',
            'application/gzip',
        ];

        if (in_array($mimeType, $archiveTypes)) {
            return 'archive';
        }

        return 'other';
    }
}
