<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasFileManagerSync;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Exception;

class Blog extends Model
{
    use HasFactory;
    use HasFileManagerSync;

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

    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'is_published' => 'boolean',
            'publish_start' => 'datetime',
            'publish_end' => 'datetime',
            'tags' => 'array',
        ];
    }

    // Temporary storage for old attachments
    public $oldAttachmentsForSync = null;

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
        });

        static::saved(function ($model) {
            $disk = Storage::disk('attachments');
            $thumbnailsData = $model->video_thumbnails_store_temp;

            if (empty($thumbnailsData)) {
                $thumbnailsData = request()->input('data.video_thumbnails_store') ?? request()->input('video_thumbnails_store');
            }

            if (empty($thumbnailsData) && isset($model->attributes['_video_thumbnails'])) {
                $thumbnailsData = $model->attributes['_video_thumbnails'];
                unset($model->attributes['_video_thumbnails']);
            }

            if (!empty($thumbnailsData)) {
                $thumbnails = is_string($thumbnailsData) ? json_decode($thumbnailsData, true) : $thumbnailsData;
                $manager = class_exists(ImageManager::class) ? new ImageManager(new Driver()) : null;

                if (is_array($thumbnails) && !empty($thumbnails)) {
                    $thumbsDir = $model->getFileManagerFolderName() . "/{$model->id}/videos/thumbs";

                    if (!$disk->exists($thumbsDir)) {
                        $disk->makeDirectory($thumbsDir, 0755, true);
                    }

                    $sizes = $model->getThumbnailSizes();

                    foreach ($thumbnails as $thumbnail) {
                        $filename = $thumbnail['filename'] ?? null;
                        $base64Data = $thumbnail['thumbnail'] ?? null;

                        if ($filename && $base64Data) {
                            $nameNoExt = pathinfo($filename, PATHINFO_FILENAME);
                            $extension = pathinfo($filename, PATHINFO_EXTENSION);
                            $sluggedName = Str::slug($nameNoExt);
                            $expectedVideoName = $sluggedName . '.' . $extension;

                            $videoExists = false;
                            if (is_array($model->attachments)) {
                                foreach ($model->attachments as $att) {
                                    if (basename($att) === $expectedVideoName) {
                                        $videoExists = true;
                                        break;
                                    }
                                }
                            }

                            if ($videoExists && $manager) {
                                $imageData = $base64Data;
                                if (str_contains($imageData, 'data:image')) {
                                    $imageData = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
                                }
                                $imageData = str_replace(' ', '+', $imageData);
                                $decodedImage = base64_decode($imageData);

                                if ($decodedImage !== false) {
                                    foreach ($sizes as $size) {
                                        $thumbPath = "{$thumbsDir}/{$sluggedName}_{$size}.jpg";
                                        try {
                                            $image = $manager->read($decodedImage);
                                            $image->scale(width: (int) $size);
                                            $disk->put($thumbPath, $image->toJpeg()->toString());
                                        } catch (Exception $e) {
                                            Log::error("Failed to generate video thumbnail ({$size}px): " . $e->getMessage());
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        });

        static::deleting(function ($model) {
            // Detach categories to clean up pivot table
            $model->categories()->detach();
        });
    }

    public function getCoverMediaAttribute()
    {
        if (empty($this->attachments)) {
            return null;
        }
        return collect($this->attachments)->last();
    }

    public function getSlideMediaAttribute()
    {
        return $this->cover_media;
    }

    public function getDetailHeaderMediaAttribute()
    {
        $cover = $this->cover_media;
        if (!$cover)
            return null;

        if ($this->isImage($cover)) {
            return $cover;
        }

        // Cover is a video, check for any image in attachments
        $firstImage = collect($this->attachments)->filter(fn($a) => $this->isImage($a))->first();

        if ($firstImage) {
            return $firstImage;
        }

        // No image found at all, return the video (cover)
        return $cover;
    }

    public function isVideo($path): bool
    {
        if (!$path)
            return false;
        return str_ends_with(strtolower($path), '.mp4') || str_ends_with(strtolower($path), '.webm');
    }

    public function isImage($path): bool
    {
        if (!$path)
            return false;
        return str_ends_with(strtolower($path), '.jpg') || str_ends_with(strtolower($path), '.jpeg') || str_ends_with(strtolower($path), '.png') || str_ends_with(strtolower($path), '.webp');
    }

    public function getThumbnailUrl($path = null)
    {
        $path = $path ?? $this->cover_media;

        if (!$path) {
            return 'https://images.unsplash.com/photo-1499750310107-5fef28a66643?auto=format&fit=crop&q=80&w=2070';
        }

        if ($this->isVideo($path)) {
            $slugName = Str::slug(pathinfo($path, PATHINFO_FILENAME));
            $thumbPath = $this->getFileManagerFolderName() . "/{$this->id}/videos/thumbs/{$slugName}.jpg";
            if (Storage::disk('attachments')->exists($thumbPath)) {
                return Storage::disk('attachments')->url($thumbPath);
            }
            return null;
        }

        return Storage::disk('attachments')->url($path);
    }

    public function getMediaUrl($path = null)
    {
        $path = $path ?? $this->slide_media;
        if (!$path) {
            return 'https://images.unsplash.com/photo-1499750310107-5fef28a66643?auto=format&fit=crop&q=80&w=2070';
        }
        return Storage::disk('attachments')->url($path);
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
