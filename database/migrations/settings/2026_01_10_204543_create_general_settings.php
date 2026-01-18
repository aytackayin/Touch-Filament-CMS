<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration {
    public function up(): void
    {
        $this->migrator->add('general.site_title', 'My Awesome Site');
        $this->migrator->add('general.site_description', 'Just another Filament project.');
        $this->migrator->add('general.site_keywords', 'laravel, filament, cms');
        $this->migrator->add('general.contact_email', 'admin@example.com');
        $this->migrator->add('general.google_maps_api_key', null);
        $this->migrator->add('general.google_analytics_id', null);
        $this->migrator->add('general.attachments_path', 'attachments');
    }
};
