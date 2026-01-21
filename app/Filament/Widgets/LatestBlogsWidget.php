<?php

namespace App\Filament\Widgets;

use App\Models\Blog;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Support\Enums\IconSize;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\Blogs\BlogResource;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Actions\EditAction;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class LatestBlogsWidget extends BaseWidget
{
    use HasWidgetShield;
    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Blog::query()
                    ->latest('updated_at')
                    ->limit(10)
            )
            ->searchable(false)
            ->paginated(false)
            ->striped()
            ->columns([
                TextColumn::make('title')
                    ->label(__('blog.label.title'))
                    ->icon('heroicon-s-document-text')
                    ->wrap(),
                IconColumn::make('is_published')
                    ->label('')
                    ->size(IconSize::Medium)
                    ->alignCenter(true)
                    ->boolean()
                    ->action(function ($record) {
                        if (auth()->user()->can('update', $record)) {
                            $record->is_published = !$record->is_published;
                            $record->save();
                        }
                    }),
                /*                     TextColumn::make('created_at')
                                        ->label(__('Oluşturulma Tarihi'))
                                        ->date(), */
            ])
            ->actions([
                EditAction::make()
                    ->url(fn(Blog $record): string => BlogResource::getUrl('edit', ['record' => $record]))
                    ->label('')
                    ->tooltip(__('button.edit')),
            ]);
    }
}
