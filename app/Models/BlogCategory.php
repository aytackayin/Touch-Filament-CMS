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
        });

        static::deleting(function ($model) {
            // Delete attachments
            if ($model->id) {
                Storage::disk('public')->deleteDirectory("attachments/blog_categories/{$model->id}");
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
