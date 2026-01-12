<?php

namespace Database\Factories;

use App\Models\BlogCategory;
use App\Models\Language;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BlogCategory>
 */
class BlogCategoryFactory extends Factory
{
    protected $model = BlogCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'language_id' => Language::first()?->id ?? Language::factory(),
            'title' => fake()->words(3, true),
            'description' => fake()->paragraph(),
            'attachments' => null,
            'parent_id' => null,
            'slug' => fake()->unique()->slug(),
            'is_published' => true,
            'publish_start' => null,
            'publish_end' => null,
            'sort' => 0,
        ];
    }
}
