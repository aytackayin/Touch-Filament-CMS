<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;
use App\Services\BlogCategoryDeletionService;

class BlogCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'edit_user_id',
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

    /**
     * Relationships
     */
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'edit_user_id');
    }

    public function blogs(): MorphToMany
    {
        return $this->morphedByMany(Blog::class, 'categorizable', 'categorizables', 'category_id');
    }

    /**
     * Boot method - Register deletion service
     */
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
            if (auth()->check()) {
                $model->edit_user_id = auth()->id();
            }
        });

        // Handle deletion using the professional service
        static::deleting(function ($model) {
            // Use the deletion service for complex logic
            $deletionService = app(BlogCategoryDeletionService::class);
            $deletionService->delete($model);

            // Return false to prevent the default delete since service handles it
            return false;
        });
    }

    /**
     * Generate a unique slug from title
     * 
     * @param string $title
     * @param int|null $ignoreId
     * @return string
     */
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
