<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = [
        'name',
        'code',
        'charset',
        'direction',
        'is_default',
        'is_active',
    ];
    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];
    protected static function booted(): void
    {
        static::saving(function (Language $language) {
            if ($language->is_default) {
                $language->is_active = true;
            }
        });
    }
}
