<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class Blog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'language_id',
        'title',
        'slug',
        'content',
        'is_published',
        'publish_start',
        'publish_end',
        'sort',
        'attachments',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_published' => 'boolean',
        'publish_start' => 'datetime',
        'publish_end' => 'datetime',
    ];

    // Temporary storage for old attachments (not a database column)
    public $oldAttachmentsForCleanup = null;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(BlogCategory::class, 'categorizable', 'categorizables', 'categorizable_id', 'category_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = static::generateUniqueSlug($model->title);
            }
            if (auth()->check() && empty($model->user_id)) {
                $model->user_id = auth()->id();
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('title') && empty($model->slug)) {
                $model->slug = static::generateUniqueSlug($model->title, $model->id);
            }

            // Store old attachments for cleanup
            if ($model->isDirty('attachments')) {
                $model->oldAttachmentsForCleanup = $model->getOriginal('attachments');
            }
        });

        static::saved(function ($model) {
            $disk = Storage::disk('attachments');

            // Clean up deleted attachments
            if (isset($model->oldAttachmentsForCleanup) && is_array($model->oldAttachmentsForCleanup)) {
                $newAttachments = $model->attachments ?? [];
                $deletedFiles = array_diff($model->oldAttachmentsForCleanup, $newAttachments);

                foreach ($deletedFiles as $deletedFile) {
                    // Delete main file
                    if ($disk->exists($deletedFile)) {
                        $disk->delete($deletedFile);
                    }

                    // Delete thumbnail
                    $filename = basename($deletedFile);
                    $thumbPath = "blogs/{$model->id}/images/thumbs/{$filename}";
                    if ($disk->exists($thumbPath)) {
                        $disk->delete($thumbPath);
                    }
                }

                $model->oldAttachmentsForCleanup = null;
            }

            $attachments = $model->attachments;
            if (empty($attachments) || !is_array($attachments)) {
                return;
            }

            $changed = false;
            $newAttachments = [];

            // Initialize Intervention Image Manager
            $manager = null;
            if (class_exists(\Intervention\Image\ImageManager::class)) {
                $manager = new \Intervention\Image\ImageManager(new \Intervention\Image\Drivers\Gd\Driver());
            }

            foreach ($attachments as $attachment) {
                if (str_contains($attachment, 'blogs/temp')) {
                    // Handle new uploads from temp
                    $filename = basename($attachment);

                    // Determine if it's an image or video to set the correct destination
                    $mimeType = $disk->mimeType($attachment);
                    $isImage = str_starts_with($mimeType, 'image/');
                    $subDir = $isImage ? 'images' : 'videos';

                    $newPath = "blogs/{$model->id}/{$subDir}/{$filename}";

                    if ($disk->exists($attachment)) {
                        // Ensure directory exists
                        $directory = dirname($newPath);
                        if (!$disk->exists($directory)) {
                            $disk->makeDirectory($directory);
                        }

                        // Move main file
                        $disk->move($attachment, $newPath);

                        // Handle image-specific logic (thumbnails)
                        if ($isImage) {
                            // Ensure thumbs directory exists
                            $thumbsDir = "blogs/{$model->id}/images/thumbs";
                            if (!$disk->exists($thumbsDir)) {
                                $disk->makeDirectory($thumbsDir);
                            }

                            // Generate thumbnail
                            if ($manager) {
                                try {
                                    $fullPath = $disk->path($newPath);
                                    $thumbPath = $disk->path("{$thumbsDir}/{$filename}");

                                    $image = $manager->read($fullPath);
                                    $image->scale(width: 150);
                                    $image->save($thumbPath);
                                } catch (\Exception $e) {
                                    // Fail silently or log
                                }
                            }
                        }

                        $newAttachments[] = $newPath;
                        $changed = true;
                    } else {
                        $newAttachments[] = $attachment;
                    }
                } else {
                    // Handle existing files - check if thumbnail exists ONLY for images
                    if ($disk->exists($attachment)) {
                        $mimeType = $disk->mimeType($attachment);
                        $isImage = str_starts_with($mimeType, 'image/');

                        if ($isImage) {
                            $filename = basename($attachment);
                            $thumbsDir = "blogs/{$model->id}/images/thumbs";
                            $thumbPath = "{$thumbsDir}/{$filename}";

                            // If main file exists but thumbnail doesn't, create it
                            if (!$disk->exists($thumbPath) && $manager) {
                                try {
                                    // Ensure thumbs directory exists
                                    if (!$disk->exists($thumbsDir)) {
                                        $disk->makeDirectory($thumbsDir);
                                    }

                                    $fullPath = $disk->path($attachment);
                                    $thumbFullPath = $disk->path($thumbPath);

                                    $image = $manager->read($fullPath);
                                    $image->scale(width: 150);
                                    $image->save($thumbFullPath);
                                } catch (\Exception $e) {
                                    // Fail silently or log
                                }
                            }
                        }
                    }

                    $newAttachments[] = $attachment;
                }
            }

            if ($changed) {
                $model->attachments = $newAttachments;
                $model->saveQuietly();
            }
        });

        static::deleting(function ($model) {
            // Delete attachments for this blog
            if ($model->id) {
                Storage::disk('attachments')->deleteDirectory("blogs/{$model->id}");
            }

            // Detach categories to clean up pivot table
            $model->categories()->detach();
        });
    }

    public static function generateUniqueSlug($title, $ignoreId = null)
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;

        while (static::where('slug', $slug)->where('id', '!=', $ignoreId)->exists()) {
            $slug = $originalSlug . '-' . $count;
            $count++;
        }

        return $slug;
    }
}
