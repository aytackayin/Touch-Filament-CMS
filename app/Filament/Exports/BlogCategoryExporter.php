<?php

namespace App\Filament\Exports;

use App\Models\BlogCategory;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class BlogCategoryExporter extends Exporter
{
    protected static ?string $model = BlogCategory::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('title'),
            ExportColumn::make('slug'),
            ExportColumn::make('description'),
            ExportColumn::make('attachments')
                ->state(fn(BlogCategory $record) => is_array($record->attachments) ? json_encode($record->attachments) : null),
            ExportColumn::make('parent_id'),
            ExportColumn::make('parent_name')
                ->label('Parent Category')
                ->state(fn(BlogCategory $record): ?string => $record->parent?->title),
            ExportColumn::make('language_id'),
            ExportColumn::make('language_name')
                ->label('Language')
                ->state(fn(BlogCategory $record): ?string => $record->language?->name),
            ExportColumn::make('user_id'),
            ExportColumn::make('user_name')
                ->label('Author')
                ->state(fn(BlogCategory $record): ?string => $record->user?->name),
            ExportColumn::make('edit_user_id'),
            ExportColumn::make('edit_user_name')
                ->label('Last Editor')
                ->state(fn(BlogCategory $record): ?string => $record->editor?->name),
            ExportColumn::make('is_published'),
            ExportColumn::make('publish_start'),
            ExportColumn::make('publish_end'),
            ExportColumn::make('sort'),
            ExportColumn::make('tags')
                ->state(fn(BlogCategory $record) => is_array($record->tags) ? json_encode($record->tags) : null),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your blog category export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
