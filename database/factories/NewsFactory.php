<?php

namespace Database\Factories;

use App\Models\News;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<News>
 */
class NewsFactory extends Factory
{
    protected $model = News::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(5),
            'slug' => fake()->unique()->slug(5),
            'excerpt' => fake()->sentence(),
            'body' => implode("\n\n", fake()->paragraphs(3)),
            'cover_url' => null,
            'author_id' => User::factory(),
            'is_featured' => false,
            'published_at' => now()->subHour(),
        ];
    }
}
