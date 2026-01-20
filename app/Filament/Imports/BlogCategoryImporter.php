<?php

namespace App\Filament\Imports;

use App\Models\BlogCategory;
use App\Models\User;
use Filament\Actions\Imports\ImportColumn;
use Filament\Actions\Imports\Importer;
use Filament\Actions\Imports\Models\Import;
use Illuminate\Support\Number;

class BlogCategoryImporter extends Importer
{
    protected static ?string $model = BlogCategory::class;

    public static function getColumns(): array
    {
        return [
            ImportColumn::make('id')
                ->label('ID')
                ->numeric()
                ->rules(['nullable', 'integer']),
            ImportColumn::make('title')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('slug')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('description')
                ->rules(['nullable']),
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
            ImportColumn::make('parent_id')
                ->numeric()
                ->rules(['nullable', 'integer']),
            ImportColumn::make('language_id')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('user_id')
                ->numeric()
                ->rules(['nullable', 'integer']),
            ImportColumn::make('edit_user_id')
                ->numeric()
                ->rules(['nullable', 'integer']),
            ImportColumn::make('is_published')
                ->boolean()
                ->rules(['nullable', 'boolean']),
            ImportColumn::make('publish_start')
                ->rules(['nullable', 'datetime']),
            ImportColumn::make('publish_end')
                ->rules(['nullable', 'datetime']),
            ImportColumn::make('sort')
                ->numeric()
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

    public function resolveRecord(): ?BlogCategory
    {
        // Get the user who initiated the import from the Import model
        $importingUser = $this->getImport()->user;

        if (!$importingUser) {
            // If no user found, skip this record
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
            $existingRecord = BlogCategory::find($this->data['id']);
        }

        // If not found by ID, try by slug
        if (!$existingRecord && !empty($this->data['slug'])) {
            $existingRecord = BlogCategory::where('slug', $this->data['slug'])->first();
        }

        // If existing record found, check ownership
        if ($existingRecord) {
            // If user is not admin/super_admin and doesn't own the record, skip this import
            if (!$isAdminOrSuperAdmin && $existingRecord->user_id !== $importingUser->id) {
                // Return null to skip this record
                return null;
            }
            // User owns the record or is admin, allow update
            return $existingRecord;
        }

        // No existing record found, create new one
        return new BlogCategory();
    }

    public static function getCompletedNotificationBody(Import $import): string
    {
        $body = 'Your blog category import has completed and ' . Number::format($import->successful_rows) . ' ' . str('row')->plural($import->successful_rows) . ' imported.';

        if ($failedRowsCount = $import->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to import.';
        }

        return $body;
    }
}
