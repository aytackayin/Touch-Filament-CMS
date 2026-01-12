<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\BlogCategory;
use App\Models\Blog;
use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BlogCategoryDeletionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a language for testing
        Language::factory()->create(['code' => 'en', 'name' => 'English']);
    }

    public function test_it_deletes_category_with_all_children()
    {
        $parent = BlogCategory::factory()->create(['title' => 'Parent']);
        $child1 = BlogCategory::factory()->create(['title' => 'Child 1', 'parent_id' => $parent->id]);
        $child2 = BlogCategory::factory()->create(['title' => 'Child 2', 'parent_id' => $parent->id]);
        $grandchild = BlogCategory::factory()->create(['title' => 'Grandchild', 'parent_id' => $child1->id]);

        $parent->delete();

        $this->assertDatabaseMissing('blog_categories', ['id' => $parent->id]);
        $this->assertDatabaseMissing('blog_categories', ['id' => $child1->id]);
        $this->assertDatabaseMissing('blog_categories', ['id' => $child2->id]);
        $this->assertDatabaseMissing('blog_categories', ['id' => $grandchild->id]);
    }

    public function test_it_deletes_blog_when_only_associated_with_deleted_category()
    {
        $category = BlogCategory::factory()->create(['title' => 'Category']);
        $blog = Blog::factory()->create(['title' => 'Blog']);

        $blog->categories()->attach($category->id);

        $category->delete();

        $this->assertDatabaseMissing('blogs', ['id' => $blog->id]);
        $this->assertDatabaseMissing('categorizables', ['category_id' => $category->id]);
    }

    public function test_it_keeps_blog_when_associated_with_multiple_categories()
    {
        $category1 = BlogCategory::factory()->create(['title' => 'Category 1']);
        $category2 = BlogCategory::factory()->create(['title' => 'Category 2']);
        $blog = Blog::factory()->create(['title' => 'Blog']);

        $blog->categories()->attach([$category1->id, $category2->id]);

        $category1->delete();

        // Blog should still exist
        $this->assertDatabaseHas('blogs', ['id' => $blog->id]);

        // Category 1 should be deleted
        $this->assertDatabaseMissing('blog_categories', ['id' => $category1->id]);

        // Category 2 should still exist
        $this->assertDatabaseHas('blog_categories', ['id' => $category2->id]);

        // Pivot with category 1 should be removed
        $this->assertDatabaseMissing('categorizables', [
            'category_id' => $category1->id,
            'categorizable_id' => $blog->id
        ]);

        // Pivot with category 2 should still exist
        $this->assertDatabaseHas('categorizables', [
            'category_id' => $category2->id,
            'categorizable_id' => $blog->id
        ]);
    }

    public function test_it_deletes_blogs_in_child_categories_when_parent_deleted()
    {
        $parent = BlogCategory::factory()->create(['title' => 'Parent']);
        $child = BlogCategory::factory()->create(['title' => 'Child', 'parent_id' => $parent->id]);

        $blogInParent = Blog::factory()->create(['title' => 'Blog in Parent']);
        $blogInChild = Blog::factory()->create(['title' => 'Blog in Child']);

        $blogInParent->categories()->attach($parent->id);
        $blogInChild->categories()->attach($child->id);

        $parent->delete();

        // Both blogs should be deleted
        $this->assertDatabaseMissing('blogs', ['id' => $blogInParent->id]);
        $this->assertDatabaseMissing('blogs', ['id' => $blogInChild->id]);

        // Both categories should be deleted
        $this->assertDatabaseMissing('blog_categories', ['id' => $parent->id]);
        $this->assertDatabaseMissing('blog_categories', ['id' => $child->id]);
    }

    public function test_it_handles_complex_multi_category_scenario()
    {
        // Create categories
        $cat1 = BlogCategory::factory()->create(['title' => 'Cat 1']);
        $cat2 = BlogCategory::factory()->create(['title' => 'Cat 2']);
        $cat3 = BlogCategory::factory()->create(['title' => 'Cat 3', 'parent_id' => $cat1->id]);

        // Create blogs
        $blogA = Blog::factory()->create(['title' => 'Blog A']);
        $blogB = Blog::factory()->create(['title' => 'Blog B']);
        $blogC = Blog::factory()->create(['title' => 'Blog C']);

        // Blog A: Cat1 and Cat2
        $blogA->categories()->attach([$cat1->id, $cat2->id]);

        // Blog B: Only Cat3 (child of Cat1)
        $blogB->categories()->attach($cat3->id);

        // Blog C: Cat2 and Cat3
        $blogC->categories()->attach([$cat2->id, $cat3->id]);

        // Delete Cat1 (which will also delete Cat3)
        $cat1->delete();

        // Blog A should still exist (has Cat2)
        $this->assertDatabaseHas('blogs', ['id' => $blogA->id]);

        // Blog B should be deleted (only had Cat3)
        $this->assertDatabaseMissing('blogs', ['id' => $blogB->id]);

        // Blog C should still exist (has Cat2)
        $this->assertDatabaseHas('blogs', ['id' => $blogC->id]);

        // Cat1 and Cat3 should be deleted
        $this->assertDatabaseMissing('blog_categories', ['id' => $cat1->id]);
        $this->assertDatabaseMissing('blog_categories', ['id' => $cat3->id]);

        // Cat2 should still exist
        $this->assertDatabaseHas('blog_categories', ['id' => $cat2->id]);
    }
}
