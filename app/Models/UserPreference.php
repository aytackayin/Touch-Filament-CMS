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

    public static function get(string $category, string $key, mixed $default = null, ?int $userId = null): mixed
    {
        $preference = static::where('user_id', $userId ?? auth()->id())
            ->where('category', $category)
            ->where('key', $key)
            ->first();

        return $preference ? $preference->value : $default;
    }

    public static function set(string $category, string $key, mixed $value, ?int $userId = null): void
    {
        static::updateOrCreate(
            [
                'user_id' => $userId ?? auth()->id(),
                'category' => $category,
                'key' => $key,
            ],
            [
                'value' => $value,
            ]
        );
    }

    public static function getTableSettings(string $tableName): ?array
    {
        return static::get('table', $tableName);
    }

    public static function setTableSettings(string $tableName, array $settings): void
    {
        static::set('table', $tableName, $settings);
    }

    public static function getEditorSettings(): ?array
    {
        return static::get('editor', 'default');
    }

    public static function getThemeSettings(): ?array
    {
        return static::get('theme', 'appearance');
    }
}
