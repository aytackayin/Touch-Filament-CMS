<?php

namespace App\Filament\Widgets;

use App\Models\Blog;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Support\Enums\IconSize;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\Blogs\BlogResource;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Actions\EditAction;
use Illuminate\Support\HtmlString;
use BezhanSalleh\FilamentShield\Traits\HasWidgetShield;

class LatestBlogsWidget extends BaseWidget
{
    use HasWidgetShield;
    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 2;
    protected static ?string $heading = null;

    public function mount(): void
    {
        static::$heading = __('blog.label.latest_blogs');
    }

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
            ->extraAttributes([
                'class' => 'latest-blogs-table',
            ])
            ->description(new HtmlString('<style>.latest-blogs-table thead { display: none !important; }</style>'))
            ->columns([
                ImageColumn::make('cover_thumbnail')
                    ->label('')
                    ->disk('attachments')
                    ->state(fn(Blog $record) => $record->getThumbnailPath())
                    ->defaultImageUrl(fn(Blog $record) => url(config('blog.icon_paths.base') . config('blog.icon_paths.file')))
                    ->square()
                    ->extraImgAttributes(['style' => 'border-radius: 8px !important; object-fit: cover;'])
                    ->size(40),
                TextColumn::make('title')
                    ->label('')
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
                    ->tooltip(__('filament-actions::edit.single.label')),
            ]);
    }
}
