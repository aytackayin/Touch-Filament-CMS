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

    // Temporary storage for old attachments (not a database column)
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
