<?php

namespace App\Filament\Resources\Blogs\Tables;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Support\Enums\IconSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Filament\Resources\Blogs\BlogResource;
use App\Models\Blog;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;

class BlogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->striped()
            ->paginatedWhileReordering()
            ->recordUrl(null)
            ->columns([
                    TextColumn::make('title')
                        ->searchable(['title', 'content'])
                        ->icon('heroicon-s-document-text')
                        ->description(fn(Blog $record): HtmlString => $record->content ? new HtmlString('<span style="font-size: 12px; line-height: 1;" class="text-gray-500 dark:text-gray-400">' . Str::limit(strip_tags($record->content), 100) . '</span>') : new HtmlString(''))
                        ->wrap()
                        ->sortable(),
                    TextColumn::make('language.name')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                    TextColumn::make('user.name')
                        ->label('Author')
                        ->sortable(),
                    IconColumn::make('is_published')
                        ->size(IconSize::Medium)
                        ->alignCenter(true)
                        ->boolean()
                        ->action(function ($record) {
                            $record->is_published = !$record->is_published;
                            $record->save();
                        }),
                    TextColumn::make('created_at')
                        ->dateTime()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true),
                ])
            ->reorderable('sort')
            ->defaultSort('sort', 'asc')
            ->filters([
                    //
                ])
            ->recordActions([
                    Action::make('edit')
                        ->url(fn(Blog $record): string => BlogResource::getUrl('edit', ['record' => $record]))
                        ->icon('heroicon-o-pencil-square')
                        ->label('')
                        ->tooltip(__('button.edit')),
                    DeleteAction::make()
                        ->label('')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->tooltip(__('button.delete'))
                        ->requiresConfirmation()
                        ->action(fn($record) => $record->delete()),
                ])
            ->bulkActions([
                    BulkActionGroup::make([
                        DeleteBulkAction::make()
                            ->label('Delete selected')
                            ->icon('heroicon-o-trash')
                            ->color('danger')
                            ->requiresConfirmation()
                            ->action(fn($records) => $records->each->delete()),
                    ]),
                ]);
    }
}
