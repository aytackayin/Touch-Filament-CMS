<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Language;
use App\Models\BlogCategory;
use Illuminate\Database\Seeder;

class BlogCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tr = Language::where('code', 'tr')->first() ?? Language::where('is_default', true)->first();
        $admin = User::where('email', 'admin@admin.com')->first() ?? User::first();

        if (!$tr || !$admin) {
            return;
        }

        // 7. Nested Categories (Exactly 20)
        $count = 0;
        $topCats = BlogCategory::factory()->count(5)->create([
            'parent_id' => null,
            'language_id' => $tr->id,
            'user_id' => $admin->id,
        ]);
        $count += 5;

        foreach ($topCats as $topCat) {
            if ($count >= 20)
                break;
            $subs = BlogCategory::factory()->count(2)->create([
                'parent_id' => $topCat->id,
                'language_id' => $tr->id,
                'user_id' => $admin->id,
            ]);
            $count += 2;

            foreach ($subs as $sub) {
                if ($count >= 20)
                    break;
                BlogCategory::factory()->create([
                    'parent_id' => $sub->id,
                    'language_id' => $tr->id,
                    'user_id' => $admin->id,
                ]);
                $count++;
            }
        }

        if ($count < 20) {
            BlogCategory::factory()->count(20 - $count)->create([
                'language_id' => $tr->id,
                'user_id' => $admin->id,
            ]);
        }
    }
}
