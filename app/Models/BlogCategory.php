<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class BlogCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'language_id',
        'title',
        'description',
        'attachments',
        'parent_id',
        'slug',
        'is_published',
        'publish_start',
        'publish_end',
        'sort',
    ];

    protected $casts = [
        'attachments' => 'array',
        'is_published' => 'boolean',
        'publish_start' => 'datetime',
        'publish_end' => 'datetime',
    ];

    // Temporary storage for old attachments (not a database column)
    public $oldAttachmentsForCleanup = null;

    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(BlogCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(BlogCategory::class, 'parent_id');
    }

    public function blogs(): MorphToMany
    {
        return $this->morphedByMany(Blog::class, 'categorizable', 'categorizables', 'category_id');
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = static::generateUniqueSlug($model->title);
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

        static::updated(function ($model) {
            if ($model->isDirty('language_id')) {
                $newLang = $model->language_id;

                // Recursively update children
                // Using each() to ensure their Updated events fire if we needed recursive logic there too, 
                // but simpler mass update might be better for performance if depth is high. 
                // However, user requirement is strict sync.
                $model->children()->each(function ($child) use ($newLang) {
                    $child->update(['language_id' => $newLang]);
                });

                // Update associated blogs
                $model->blogs()->each(function ($blog) use ($newLang) {
                    $blog->update(['language_id' => $newLang]);
                });
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
                    $thumbPath = "blog_categories/{$model->id}/images/thumbs/{$filename}";
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
                if (str_contains($attachment, 'blog_categories/temp')) {
                    // Handle new uploads from temp
                    $filename = basename($attachment);
                    $newPath = "blog_categories/{$model->id}/images/{$filename}";

                    if ($disk->exists($attachment)) {
                        // Ensure directory exists
                        $directory = dirname($newPath);
                        if (!$disk->exists($directory)) {
                            $disk->makeDirectory($directory);
                        }

                        // Ensure thumbs directory exists
                        $thumbsDir = "blog_categories/{$model->id}/images/thumbs";
                        if (!$disk->exists($thumbsDir)) {
                            $disk->makeDirectory($thumbsDir);
                        }

                        // Move main file
                        $disk->move($attachment, $newPath);

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

                        $newAttachments[] = $newPath;
                        $changed = true;
                    } else {
                        $newAttachments[] = $attachment;
                    }
                } else {
                    // Handle existing files - check if thumbnail exists
                    $filename = basename($attachment);
                    $thumbsDir = "blog_categories/{$model->id}/images/thumbs";
                    $thumbPath = "{$thumbsDir}/{$filename}";

                    // If main file exists but thumbnail doesn't, create it
                    if ($disk->exists($attachment) && !$disk->exists($thumbPath) && $manager) {
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

                    $newAttachments[] = $attachment;
                }
            }

            if ($changed) {
                $model->attachments = $newAttachments;
                $model->saveQuietly();
            }
        });

        static::deleting(function ($model) {
            // Delete attachments
            if ($model->id) {
                Storage::disk('attachments')->deleteDirectory("blog_categories/{$model->id}");
            }

            // Delete children categories
            $model->children()->each(function ($child) {
                $child->delete();
            });

            // Delete associated blogs (that are ONLY associated with this category? Or remove relationship?)
            // Requirement says: "Bir kategori silindiğinde alt kategorileri, attachments ve bağlı tüm bloglar ve blogların attachments ları silinecek."
            // This implies deleting the blogs themselves.
            $model->blogs()->each(function ($blog) {
                $blog->delete();
            });
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
