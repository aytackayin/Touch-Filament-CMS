<?php

namespace App\Filament\Resources\Blogs\Widgets;

use App\Filament\Resources\Blogs\BlogResource;
use App\Filament\Resources\Blogs\Tables\BlogsTable;
use App\Models\Blog;
use App\Models\BlogCategory;
use Filament\Actions\CreateAction;
use Filament\Actions\ActionGroup;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RelatedItemsWidget extends BaseWidget
{
    protected int|string|array $columnSpan = 'full';

    public ?int $parent_id = null;
    protected static ?string $heading = null;

    public function mount(): void
    {
        if ($this->parent_id) {
            $record = BlogCategory::find($this->parent_id);
            if ($record) {
                static::$heading = __('blog.label.articles_in', ['name' => $record->title]);
            } else {
                static::$heading = __('blog.label.articles');
            }
        } else {
            static::$heading = __('blog.label.articles');
        }
    }

    public function table(Table $table): Table
    {
        $query = Blog::query();

        if ($this->parent_id) {
            // Robust filtering using whereHas
            $query->whereHas('categories', function ($q) {
                $q->where('blog_categories.id', $this->parent_id);
            });
        } else {
            // If no parent_id, show nothing
            $query->whereRaw('1 = 0');
        }

        $table->query($query)
            ->headerActions([
                ActionGroup::make([
                    CreateAction::make()
                        ->label('')
                        ->tooltip(__('filament-actions::create.single.modal.actions.create.label'))
                        ->color('success')
                        ->size('xs')
                        ->icon('heroicon-m-document-plus')
                        ->url(fn(): string => BlogResource::getUrl('create', ['category_id' => $this->parent_id])),
                ])->buttonGroup()
            ]);

        return BlogsTable::configure($table);
    }
}
