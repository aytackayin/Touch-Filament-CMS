<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $site_title;
    public ?string $site_description = null;
    public array $site_keywords = [];
    public string $attachments_path = 'attachments';
    public array $custom_settings = [];

    public static function group(): string
    {
        return 'general';
    }
}
