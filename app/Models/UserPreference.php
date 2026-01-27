<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get a preference value for the current user
     */
    public static function get(string $category, string $key, mixed $default = null, ?int $userId = null): mixed
    {
        $userId = $userId ?? auth()->id();

        $preference = static::where('user_id', $userId)
            ->where('category', $category)
            ->where('key', $key)
            ->first();

        return $preference ? $preference->value : $default;
    }

    /**
     * Set a preference value for the current user
     */
    public static function set(string $category, string $key, mixed $value, ?int $userId = null): void
    {
        $userId = $userId ?? auth()->id();

        static::updateOrCreate(
            [
                'user_id' => $userId,
                'category' => $category,
                'key' => $key,
            ],
            [
                'value' => $value,
            ]
        );
    }

    /**
     * Get table-specific settings
     */
    public static function getTableSettings(string $tableName): ?array
    {
        return static::get('table', $tableName);
    }

    /**
     * Set table-specific settings
     */
    public static function setTableSettings(string $tableName, array $settings): void
    {
        static::set('table', $tableName, $settings);
    }

    /**
     * Get editor settings
     */
    public static function getEditorSettings(): ?array
    {
        return static::get('editor', 'default');
    }

    /**
     * Get theme settings
     */
    public static function getThemeSettings(): ?array
    {
        return static::get('theme', 'appearance');
    }
}
