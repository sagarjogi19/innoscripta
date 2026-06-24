<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Source;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $url = fake()->unique()->url().'/'.fake()->slug();

        return [
            'source_id' => Source::factory(),
            'category_id' => Category::factory(),
            'author_id' => Author::factory(),
            'provider' => fake()->randomElement(['newsapi', 'guardian', 'nyt']),
            'external_id' => fake()->uuid(),
            'title' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'content' => fake()->paragraphs(3, true),
            'url' => $url,
            'url_hash' => hash('sha256', $url),
            'image_url' => fake()->imageUrl(),
            'language' => 'en',
            'published_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
