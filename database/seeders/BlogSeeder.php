<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Language;
use App\Models\Blog;
use App\Models\BlogCategory;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tr = Language::where('code', 'tr')->first() ?? Language::where('is_default', true)->first();
        $writer = User::where('email', 'writer@writer.com')->first() ?? User::where('email', 'admin@admin.com')->first() ?? User::first();
        $categories = BlogCategory::all();

        if (!$tr || !$writer || $categories->isEmpty()) {
            return;
        }

        // 8. Blogs (30)
        Blog::factory()->count(30)->create([
            'user_id' => $writer->id,
            'language_id' => $tr->id,
        ])->each(function ($blog) use ($categories) {
            $blog->categories()->attach(
                $categories->random(rand(1, 3))->pluck('id')->toArray()
            );
        });
    }
}
