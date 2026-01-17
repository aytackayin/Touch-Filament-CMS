<?php

namespace App\Filament\Exports;

use App\Models\Blog;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Support\Number;

class BlogExporter extends Exporter
{
    protected static ?string $model = Blog::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')->label('ID'),
            ExportColumn::make('title'),
            ExportColumn::make('slug'),
            ExportColumn::make('content'),
            ExportColumn::make('categories')
                ->state(fn(Blog $record) => $record->categories->pluck('title')->implode(', ')),
            ExportColumn::make('language_id'),
            ExportColumn::make('language_name')
                ->label('Language')
                ->state(fn(Blog $record): ?string => $record->language?->name),
            ExportColumn::make('user_id'),
            ExportColumn::make('user_name')
                ->label('Author')
                ->state(fn(Blog $record): ?string => $record->user?->name),
            ExportColumn::make('is_published'),
            ExportColumn::make('publish_start'),
            ExportColumn::make('publish_end'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your blog export has completed and ' . Number::format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . Number::format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}
