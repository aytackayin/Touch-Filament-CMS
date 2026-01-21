<?php

namespace App\Filament\Resources\Blogs\Pages;

use App\Filament\Resources\Blogs\BlogResource;
use App\Filament\Exports\BlogExporter;
use App\Filament\Imports\BlogImporter;
use Filament\Actions\ActionGroup;
use Filament\Actions\ExportAction;
use Filament\Actions\ImportAction;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

class ListBlogs extends ListRecords
{
    protected static string $resource = BlogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                ExportAction::make()
                    ->label('')
                    ->icon(Heroicon::OutlinedArrowUpOnSquareStack)
                    //->tooltip(__('filament-actions::export.label', ['label' => 'Bloglar'])) //Label ile translate örneği
                    ->tooltip(__('filament-actions::export.modal.actions.export.label'))
                    ->color('gray')
                    ->size('xs')
                    ->exporter(BlogExporter::class)
                    ->visible(fn() => auth()->user()->can('export', BlogResource::getModel())),
                ImportAction::make()
                    ->label('')
                    ->icon(Heroicon::OutlinedArrowDownOnSquareStack)
                    ->tooltip(__('filament-actions::import.modal.actions.import.label'))
                    ->color('gray')
                    ->size('xs')
                    ->importer(BlogImporter::class)
                    ->visible(fn() => auth()->user()->can('import', BlogResource::getModel())),
                CreateAction::make()
                    ->label('')
                    ->tooltip(__('filament-actions::create.single.modal.actions.create.label'))
                    ->color('success')
                    ->size('xs')
                    ->icon('heroicon-m-document-plus'),
            ])->buttonGroup()
        ];
    }
}
