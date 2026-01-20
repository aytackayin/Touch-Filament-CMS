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
            ExportColumn::make('user.name'),
            ExportColumn::make('edit_user_id'),
            ExportColumn::make('language.name'),
            ExportColumn::make('title'),
            ExportColumn::make('description'),
            ExportColumn::make('attachments'),
            ExportColumn::make('parent.title'),
            ExportColumn::make('slug'),
            ExportColumn::make('is_published'),
            ExportColumn::make('publish_start'),
            ExportColumn::make('publish_end'),
            ExportColumn::make('sort'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
            ExportColumn::make('tags'),
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
