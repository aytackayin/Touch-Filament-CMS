<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $site_title;
    public ?string $site_description = null;
    public array $site_keywords = [];
    public ?string $contact_email = null;
    public ?string $google_maps_api_key = null;
    public ?string $google_analytics_id = null;

    public static function group(): string
    {
        return 'general';
    }
}
