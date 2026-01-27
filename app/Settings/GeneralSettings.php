<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $site_title = 'Filament CMS';
    public ?string $site_description = null;
    public array $site_keywords = [];
    public string $attachments_path = 'attachments';
    public array $thumbnail_sizes = ['150'];
    public array $custom_settings = [];

    public function getCustomSetting(string $key, mixed $default = null): mixed
    {
        foreach ($this->custom_settings as $group) {
            foreach ($group['fields'] ?? [] as $field) {
                if (($field['field_name'] ?? null) === $key) {
                    return $field['value'] ?? $default;
                }
            }
        }

        return $default;
    }

    public static function group(): string
    {
        return 'general';
    }
}
