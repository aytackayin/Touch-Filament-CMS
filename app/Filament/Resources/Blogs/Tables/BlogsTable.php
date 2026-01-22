<?php

namespace App\Filament\Resources\Blogs\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Support\Enums\IconSize;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Table;
use App\Models\Blog;
use Illuminate\Support\Str;
use Illuminate\Support\HtmlString;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Exports\BlogExporter;
use Filament\Actions\ExportBulkAction;
use Filament\Support\Icons\Heroicon;
use App\Filament\Resources\Blogs\BlogResource;

class BlogsTable
{
    public static function configure(Table $table): Table
    {
        $livewire = $table->getLivewire();
        $isGrid = ($livewire && property_exists($livewire, 'view_type'))
            ? $livewire->view_type === 'grid'
            : false;

        return $table
            ->when(
                $isGrid,
                fn(Table $table) => $table
                    ->contentGrid([
                        'md' => 2,
                        'xl' => 3,
                        '2xl' => 4,
                    ])
                    ->extraAttributes([
                        'class' => 'blogs-grid',
                    ])
            )
            ->striped()
            ->paginatedWhileReordering()
            ->recordUrl(null)
            ->columns($isGrid ? [
                Stack::make([
                    ViewColumn::make('details')
                        ->view('filament.tables.columns.blog-grid')->searchable(['title', 'content', 'tags']),
                ])->space(0),
            ] : [
                TextColumn::make('title')
                    ->label(__('blog.label.title'))
                    ->searchable(['title', 'content'])
                    ->icon('heroicon-s-document-text')
                    ->description(fn(Blog $record): HtmlString => $record->content ? new HtmlString('<span style="font-size: 12px; line-height: 1;" class="text-gray-500 dark:text-gray-400">' . Str::limit(strip_tags($record->content), 100) . '</span>') : new HtmlString(''))
                    ->wrap()
                    ->sortable(),
                TextColumn::make('language.name')
                    ->label(__('blog.label.language'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.name')
                    ->label(__('blog.label.author'))
                    ->sortable(),
                TextColumn::make('editor.name')
                    ->label(__('blog.label.last_edited_by'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tags')
                    ->label(__('blog.label.tags'))
                    ->badge()
                    ->separator(',')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_published')
                    ->label(__('blog.label.is_published'))
                    ->size(IconSize::Medium)
                    ->alignCenter(true)
                    ->boolean()
                    ->action(function ($record) {
                        if (auth()->user()->can('update', $record)) {
                            $record->is_published = !$record->is_published;
                            $record->save();
                        }
                    }),
                TextColumn::make('created_at')
                    ->label(__('blog.label.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->reorderable('sort')
            ->defaultSort('sort', 'asc')
            ->filters([
                SelectFilter::make('user_id')
                    ->label(__('blog.label.author'))
                    ->relationship('user', 'name')
                    ->searchable(),
                SelectFilter::make('edit_user_id')
                    ->label(__('blog.label.last_editor'))
                    ->relationship('editor', 'name')
                    ->searchable(),
                SelectFilter::make('language_id')
                    ->label(__('label.language'))
                    ->relationship('language', 'name'),
                SelectFilter::make('is_published')
                    ->label('Publication Status')
                    ->options([
                        '1' => 'Published',
                        '0' => 'Unpublished',
                    ]),
            ])
            ->actions([
                EditAction::make()
                    ->label('')
                    ->tooltip(__('filament-actions::edit.single.label'))
                    ->url(fn(Blog $record): string => BlogResource::getUrl('edit', ['record' => $record])),
                DeleteAction::make()
                    ->label('')
                    ->tooltip(__('filament-actions::delete.single.label'))
                    ->modalHeading(fn(Blog $record) => __('blog.delete_confirmation_title.blog', ['name' => $record->title]))
                    ->modalDescription(__('blog.delete_confirmation_description')),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label(__('filament-actions::delete.multiple.label'))
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(fn($records) => $records->filter(fn($blog) => auth()->user()->can('delete', $blog))->each->delete()),
                    ExportBulkAction::make()
                        ->label(__('filament-actions::export.modal.actions.export.label'))
                        ->icon(Heroicon::OutlinedArrowUpOnSquareStack)
                        ->exporter(BlogExporter::class),
                ]),
            ]);
    }
}
