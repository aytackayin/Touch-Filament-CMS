<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\TouchFile;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Exception;

class Blog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'edit_user_id',
        'language_id',
        'title',
        'slug',
        'content',
        'is_published',
        'publish_start',
        'publish_end',
        'sort',
        'attachments',
        'video_thumbnails_store',
        'tags',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_published' => 'boolean',
        'publish_start' => 'datetime',
        'publish_end' => 'datetime',
        'tags' => 'array',
    ];

    // Temporary storage for old attachments (not a database column)
    public $oldAttachmentsForCleanup = null;

    // Temporary storage for video thumbnails
    public $video_thumbnails_store_temp = null;

    public function setVideoThumbnailsStoreAttribute($value)
    {
        $this->video_thumbnails_store_temp = $value;
        // Do not set attribute to avoid SQL error
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edit_user_id');
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

            if (auth()->check()) {
                $model->edit_user_id = auth()->id();
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
                    TouchFile::unregisterFile($deletedFile);

                    // Delete thumbnail
                    $filename = basename($deletedFile);
                    $thumbPath = "blogs/{$model->id}/images/thumbs/{$filename}";
                    if ($disk->exists($thumbPath)) {
                        $disk->delete($thumbPath);
                    }

                    // Delete video thumbnail
                    $nameNoExt = pathinfo($filename, PATHINFO_FILENAME);
                    $videoThumbPath = "blogs/{$model->id}/videos/thumbs/{$nameNoExt}.jpg";
                    if ($disk->exists($videoThumbPath)) {
                        $disk->delete($videoThumbPath);
                    }
                }

                // Clean up empty directories for this blog
                $blogDir = "blogs/{$model->id}";
                if ($disk->exists($blogDir)) {
                    // Check if there are any files left in the blog directory (recursively)
                    $allFiles = $disk->allFiles($blogDir);
                    if (empty($allFiles)) {
                        $disk->deleteDirectory($blogDir);
                        // Also remove folder from TouchFile
                        $touchFolder = TouchFile::where('path', $blogDir)->first();
                        if ($touchFolder)
                            $touchFolder->delete();
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
            $newVideoFiles = []; // Track newly added video files

            // Initialize Intervention Image Manager
            $manager = null;
            if (class_exists(ImageManager::class)) {
                $manager = new ImageManager(new Driver());
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
                        TouchFile::registerFile($newPath);

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
                                } catch (Exception $e) {
                                    // Fail silently or log
                                }
                            }
                        }

                        $newAttachments[] = $newPath;
                        $changed = true;
                    } else {
                        $newAttachments[] = $attachment;
                        // Attempt to register strictly if it exists physically
                        if ($disk->exists($attachment)) {
                            TouchFile::registerFile($attachment);
                        }
                    }
                } else {
                    // Handle existing files - check if thumbnail exists ONLY for images
                    if ($disk->exists($attachment)) {
                        TouchFile::registerFile($attachment); // SYNC HERE

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
                                } catch (Exception $e) {
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

            // Process video thumbnails
            // Try getting from temp property (set via Mutator)
            $thumbnailsData = $model->video_thumbnails_store_temp;

            // Fallback to request input
            if (empty($thumbnailsData)) {
                $thumbnailsData = request()->input('data.video_thumbnails_store') ?? request()->input('video_thumbnails_store');
            }
            // Fallback for CreateBlog/EditBlog page mutation
            if (empty($thumbnailsData) && isset($model->attributes['_video_thumbnails'])) {
                $thumbnailsData = $model->attributes['_video_thumbnails'];
                unset($model->attributes['_video_thumbnails']); // Clean up
            }

            if (!empty($thumbnailsData)) {
                $thumbnails = is_string($thumbnailsData) ? json_decode($thumbnailsData, true) : $thumbnailsData;

                if (is_array($thumbnails) && !empty($thumbnails)) {
                    $thumbsDir = "blogs/{$model->id}/videos/thumbs";

                    // Ensure thumbs directory exists
                    if (!$disk->exists($thumbsDir)) {
                        $disk->makeDirectory($thumbsDir, 0755, true);
                    }

                    foreach ($thumbnails as $thumbnail) {
                        $filename = $thumbnail['filename'] ?? null;
                        $base64Data = $thumbnail['thumbnail'] ?? null;

                        if ($filename && $base64Data) {
                            // 1. Slugify the incoming filename to match storage strategy in BlogForm
                            $originalNameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);
                            $extension = pathinfo($filename, PATHINFO_EXTENSION);

                            $sluggedName = Str::slug($originalNameWithoutExt);
                            $expectedVideoName = $sluggedName . '.' . $extension;
                            $thumbFilename = $sluggedName . '.jpg';

                            // 2. Check if corresponding video exists in current attachments
                            // We need to check against basename of attachments
                            $videoExists = false;
                            if (is_array($model->attachments)) {
                                foreach ($model->attachments as $att) {
                                    if (basename($att) === $expectedVideoName) {
                                        $videoExists = true;
                                        break;
                                    }
                                }
                            }

                            if ($videoExists) {
                                $thumbPath = "{$thumbsDir}/{$thumbFilename}";

                                // Decode base64 image
                                $imageData = $base64Data;
                                if (str_contains($imageData, 'data:image')) {
                                    $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
                                }
                                $imageData = str_replace(' ', '+', $imageData);
                                $decodedImage = base64_decode($imageData);

                                if ($decodedImage !== false) {
                                    // Save thumbnail using Storage disk
                                    $disk->put($thumbPath, $decodedImage);
                                    Log::info("Video thumbnail saved: {$thumbPath}");
                                } else {
                                    Log::error("Failed to decode base64 for thumbnail");
                                }
                            } else {
                                Log::info("Skipping thumbnail for deleted/missing video: {$expectedVideoName}");
                                // Optional: Ensure thumbnail is deleted if it exists?
                                // The cleanup logic at the start of saved() or in deleting() should handle this handled via oldAttachmentsForCleanup
                                // But if this is a subsequent save where video was removed, cleanup logic ran first.
                                // Here we just ensure we don't CREATE/UPDATE a dead thumbnail.
                            }
                        }
                    }
                } else {
                    Log::debug('Video Thumbnails - Not an array or empty');
                }
            } else {
                Log::debug('Video Thumbnails - No data received');
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
