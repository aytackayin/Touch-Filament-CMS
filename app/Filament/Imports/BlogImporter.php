<?php

namespace App\Filament\Imports;

use App\Models\Blog;
use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class BlogImporter extends Importer
{
    protected static ?string $model = Blog::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('id')
                ->label('ID')
                ->rules(['nullable', 'integer']),
            ImportColumn::make('title')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('slug')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('content')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('attachments')
                ->castStateUsing(function (?string $state): ?array {
                    if (blank($state)) {
                        return null;
                    }

                    $decoded = json_decode($state, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        return $decoded;
                    }

                    return array_map('trim', explode(',', $state));
                })
                ->rules(['nullable']),
            ImportColumn::make('language_id')
                ->requiredMapping()
                ->rules(['required', 'integer']),
            ImportColumn::make('user_id')
                ->rules(['nullable', 'integer']),
            ImportColumn::make('edit_user_id')
                ->rules(['nullable', 'integer']),
            ImportColumn::make('is_published')
                ->boolean(),
            ImportColumn::make('publish_start')
                ->rules(['nullable', 'datetime']),
            ImportColumn::make('publish_end')
                ->rules(['nullable', 'datetime']),
            ImportColumn::make('sort')
                ->rules(['nullable', 'integer']),
            ImportColumn::make('tags')
                ->castStateUsing(function (?string $state): ?array {
                    if (blank($state)) {
                        return null;
                    }

                    $decoded = json_decode($state, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        return $decoded;
                    }

                    return array_map('trim', explode(',', $state));
                })
                ->rules(['nullable']),
        ];
    }

    public function resolveRecord(): ?Blog
    {
        // Get the user who initiated the import from the Import model
        $importingUser = $this->getImport()->user;

        if (!$importingUser) {
            return null;
        }

        $isAdminOrSuperAdmin = $importingUser->hasAnyRole(['admin', 'super_admin']);


        // Override user_id in import data with importing user's ID if not admin/super_admin
        if (!$isAdminOrSuperAdmin) {
            $this->data['user_id'] = $importingUser->id;
        }

        $existingRecord = null;

        // Try to find existing record by ID first
        if (!empty($this->data['id'])) {
            $existingRecord = Blog::find($this->data['id']);
        }

        // If not found by ID, try by slug
        if (!$existingRecord && !empty($this->data['slug'])) {
            $existingRecord = Blog::where('slug', $this->data['slug'])->first();
        }

        // If existing record found, check ownership
        if ($existingRecord) {

            // If user is not admin/super_admin and doesn't own the record, skip this import
            if (!$isAdminOrSuperAdmin && $existingRecord->user_id !== $importingUser->id) {
                return null;
            }

            return $existingRecord;
        }

        return new Blog();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your blog import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
