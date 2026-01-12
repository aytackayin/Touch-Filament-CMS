<?php

namespace Database\Factories;

use App\Models\Blog;
use App\Models\Language;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Blog>
 */
class BlogFactory extends Factory
{
    protected $model = Blog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::first()?->id ?? User::factory(),
            'language_id' => Language::first()?->id ?? Language::factory(),
            'title' => fake()->sentence(),
            'slug' => fake()->unique()->slug(),
            'content' => fake()->paragraphs(3, true),
            'is_published' => true,
            'publish_start' => null,
            'publish_end' => null,
            'sort' => 0,
            'attachments' => null,
        ];
    }
}
