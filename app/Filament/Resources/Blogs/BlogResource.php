<?php

namespace App\Filament\Resources\Blogs;

use App\Filament\Resources\Blogs\Pages\CreateBlog;
use App\Filament\Resources\Blogs\Pages\EditBlog;
use App\Filament\Resources\Blogs\Pages\ListBlogs;
use App\Filament\Resources\Blogs\Schemas\BlogForm;
use App\Filament\Resources\Blogs\Tables\BlogsTable;
use App\Models\Blog;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use BezhanSalleh\FilamentShield\Contracts\HasShieldPermissions;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Storage;

class BlogResource extends Resource implements HasShieldPermissions
{
    protected static ?string $model = Blog::class;

    public static function getNavigationIcon(): string
    {
        return __('blog.nav.icon');
    }
    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'content'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return ' ';
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];

        // 1. Thumbnail or Icon
        $imageUrl = url('/assets/icons/colorful-icons/blog.svg');
        $attachments = $record->attachments;

        if (is_array($attachments) && count($attachments) > 0) {
            foreach ($attachments as $attachment) {
                // Check if it's an image
                if (preg_match('/\.(jpg|jpeg|png|gif|webp|bmp|svg)$/i', $attachment)) {
                    $filename = basename($attachment);
                    $thumbPath = "blogs/{$record->id}/images/thumbs/{$filename}";
                    if (Storage::disk('attachments')->exists($thumbPath)) {
                        $imageUrl = Storage::disk('attachments')->url($thumbPath);
                        break;
                    }
                }
            }
        }

        $uniqueId = 'gs-blog-' . $record->id;
        $details[] = new HtmlString('
            <style>
                #' . $uniqueId . ' { color: #030712 !important; opacity: 1 !important; }
                .dark #' . $uniqueId . ' { color: #ffffff !important; }
            </style>
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 6px; margin-top: -10px;">
                <div style="width: 48px; height: 48px; border-radius: 8px; overflow: hidden; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <img src="' . $imageUrl . '" 
                         style="width: 100%; height: 100%; object-fit: cover;"
                         onerror="this.src=\'' . url('/assets/icons/colorful-icons/blog.svg') . '\'">
                </div>
                <div style="display: flex; flex-direction: column;">
                    <span id="' . $uniqueId . '" style="font-weight: 600; font-size: 14px;">' . $record->title . '</span>
                </div>
            </div>
        ');

        $description = strip_tags($record->content ?? '');
        $firstSentence = Str::of($description)->explode('.')->first();

        $details[] = new HtmlString('<span style="font-size: 12px; line-height: 1;">' . Str::limit($firstSentence, 120) . '</span>');

        return $details;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Blog';
    }
    public static function getNavigationLabel(): string
    {
        return __('blog.nav.label');
    }
    public static function getBreadcrumb(): string
    {
        return __('blog.nav.label');
    }

    public static function getModelLabel(): string
    {
        return __('blog.label.blog');
    }

    public static function getPluralModelLabel(): string
    {
        return __('blog.label.blogs');
    }
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return BlogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlogs::route('/'),
            'create' => CreateBlog::route('/create'),
            'edit' => EditBlog::route('/{record}/edit'),
        ];
    }

    public static function getPermissionPrefixes(): array
    {
        return [
            'view',
            'view_any',
            'create',
            'update',
            'delete',
            'delete_any',
            'replicate',
            'reorder',
            'import',
            'export',
        ];
    }
}
