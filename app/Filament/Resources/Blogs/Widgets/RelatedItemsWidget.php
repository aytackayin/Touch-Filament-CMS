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
use App\Traits\HasTableSettings;
use Filament\Actions\Action as TableAction;

class RelatedItemsWidget extends BaseWidget
{
    use HasTableSettings;

    protected int|string|array $columnSpan = 'full';

    public ?int $parent_id = null;
    protected static ?string $heading = null;

    public function mount(): void
    {
        $this->mountHasTableSettings();

        if ($this->parent_id) {
            $record = BlogCategory::find($this->parent_id);
            if ($record) {
                static::$heading = __('blog.label.articles_in', ['name' => $record->title]);
            } else {
                static::$heading = __('blog.label.articles');
            }
        } else {
            static::$heading = __('blog.label.uncategorized_articles');
        }
    }

    public function table(Table $table): Table
    {
        $query = Blog::query();

        if ($this->parent_id) {
            $category = BlogCategory::find($this->parent_id);
            if ($category) {
                $categoryIds = $category->getAllDescendantIds();
                $query->whereHas('categories', function ($q) use ($categoryIds) {
                    $q->whereIn('blog_categories.id', $categoryIds);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        } else {
            // Show blogs with no categories at root level
            $query->whereDoesntHave('categories');
        }

        $table->query($query)
            ->headerActions([
                ActionGroup::make([
                    CreateAction::make()
                        ->label('')
                        ->tooltip(__('blog.label.create_blog'))
                        ->color('warning')
                        ->size('xs')
                        ->icon('heroicon-m-document-plus')
                        ->url(fn(): string => BlogResource::getUrl('create', ['category_id' => $this->parent_id])),
                ])->buttonGroup()
            ]);

        return BlogsTable::configure($table);
    }

    protected function getTableSettingsKey(): string
    {
        return 'blog_list';
    }

    protected function getDefaultVisibleColumns(): array
    {
        return ['categories', 'user', 'is_published', 'created_at'];
    }

    protected function getTableColumnOptions(): array
    {
        return [
            'categories' => __('blog.label.categories'),
            'language' => __('blog.label.language'),
            'user' => __('blog.label.author'),
            'editor' => __('blog.label.last_edited_by'),
            'tags' => __('blog.label.tags'),
            'is_published' => __('blog.label.is_published'),
            'created_at' => __('blog.label.created_at'),
        ];
    }
}
