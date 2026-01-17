<?php

namespace App\Filament\Imports;

use App\Models\Blog;
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
                ->label('ID'),
            ImportColumn::make('title')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('slug')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('content')
                ->requiredMapping()
                ->rules(['required']),
            ImportColumn::make('language_id')
                ->requiredMapping()
                ->rules(['required', 'integer']),
            ImportColumn::make('user_id')
                ->requiredMapping()
                ->rules(['required', 'integer']),
            ImportColumn::make('is_published')
                ->boolean(),
            ImportColumn::make('publish_start')
                ->rules(['nullable', 'datetime']),
            ImportColumn::make('publish_end')
                ->rules(['nullable', 'datetime']),
            ImportColumn::make('sort')
                ->rules(['nullable', 'integer']),
        ];
    }

    public function resolveRecord(): ?Blog
    {
        // If an ID is provided, use it as the primary lookup key.
        if (!empty($this->data['id'])) {
            $blog = Blog::find($this->data['id']);
            if ($blog) {
                return $blog; // Found by ID, return for updating.
            }
        }

        // If no ID is provided, or if the ID was not found,
        // try to find a record based on a composite unique key.
        // This prevents creating duplicates.
        return Blog::firstOrNew([
            'title' => $this->data['title'],
            'slug' => $this->data['slug'],
            'language_id' => $this->data['language_id'],
            'user_id' => $this->data['user_id'] ?? null,
        ]);
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
