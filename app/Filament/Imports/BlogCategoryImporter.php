<?php

namespace App\Filament\Imports;

use App\Models\BlogCategory;
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
            ImportColumn::make('user')
                ->relationship(),
            ImportColumn::make('edit_user_id')
                ->numeric()
                ->rules(['integer']),
            ImportColumn::make('language')
                ->requiredMapping()
                ->relationship()
                ->rules(['required']),
            ImportColumn::make('title')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('description'),
            ImportColumn::make('attachments'),
            ImportColumn::make('parent')
                ->relationship(),
            ImportColumn::make('slug')
                ->requiredMapping()
                ->rules(['required', 'max:255']),
            ImportColumn::make('is_published')
                ->requiredMapping()
                ->boolean()
                ->rules(['required', 'boolean']),
            ImportColumn::make('publish_start')
                ->rules(['datetime']),
            ImportColumn::make('publish_end')
                ->rules(['datetime']),
            ImportColumn::make('sort')
                ->requiredMapping()
                ->numeric()
                ->rules(['required', 'integer']),
            ImportColumn::make('tags'),
        ];
    }

    public function resolveRecord(): BlogCategory
    {
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
