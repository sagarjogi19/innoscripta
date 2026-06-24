<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_paginated_articles(): void
    {
        Article::factory()->count(20)->create();

        $response = $this->getJson('/api/articles?per_page=5');

        $response->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.total', 20)
            ->assertJsonStructure([
                'data' => [['id', 'title', 'url', 'published_at', 'source', 'category', 'author']],
                'meta' => ['current_page', 'total'],
            ]);
    }

    public function test_it_filters_articles_by_category_slug(): void
    {
        $tech = Category::factory()->create(['name' => 'Technology', 'slug' => 'technology']);
        $sport = Category::factory()->create(['name' => 'Sport', 'slug' => 'sport']);

        Article::factory()->count(3)->for($tech)->create();
        Article::factory()->count(2)->for($sport)->create();

        $this->getJson('/api/articles?category=technology')
            ->assertOk()
            ->assertJsonPath('meta.total', 3);
    }

    public function test_it_searches_articles_by_keyword(): void
    {
        Article::factory()->create([
            'title' => 'Breakthrough in artificial intelligence',
            'description' => 'A major leap forward.',
            'content' => 'Researchers unveiled new findings.',
        ]);
        Article::factory()->create([
            'title' => 'Local sports update',
            'description' => 'Weekend results.',
            'content' => 'The home team won the match.',
        ]);

        $this->getJson('/api/articles?search=artificial')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.title', 'Breakthrough in artificial intelligence');
    }

    public function test_it_filters_by_date_range(): void
    {
        Article::factory()->create(['published_at' => '2024-01-01 10:00:00']);
        Article::factory()->create(['published_at' => '2024-06-01 10:00:00']);

        $this->getJson('/api/articles?date_from=2024-05-01&date_to=2024-07-01')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    public function test_it_returns_a_single_article(): void
    {
        $article = Article::factory()->create();

        $this->getJson("/api/articles/{$article->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $article->id);
    }
}
