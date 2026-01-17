<?php

namespace App\Filament\Resources\Blogs\Pages;

use App\Filament\Resources\Blogs\BlogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBlogs extends ListRecords
{
    protected static string $resource = BlogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ActionGroup::make([
                Actions\ExportAction::make()
                    ->label('')
                    ->icon(\Filament\Support\Icons\Heroicon::OutlinedArrowUpOnSquareStack)
                    ->tooltip(__('button.export'))
                    ->color('gray')
                    ->size('xs')
                    ->exporter(\App\Filament\Exports\BlogExporter::class),
                Actions\ImportAction::make()
                    ->label('')
                    ->icon(\Filament\Support\Icons\Heroicon::OutlinedArrowDownOnSquareStack)
                    ->tooltip(__('button.import'))
                    ->color('gray')
                    ->size('xs')
                    ->importer(\App\Filament\Imports\BlogImporter::class),
                Actions\CreateAction::make()
                    ->label('')
                    ->tooltip(__('button.new'))
                    ->color('success')
                    ->size('xs')
                    ->icon('heroicon-m-document-plus'),
            ])->buttonGroup()
        ];
    }
}
