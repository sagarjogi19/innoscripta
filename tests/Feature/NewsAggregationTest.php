<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Services\News\NewsAggregatorService;
use App\Services\News\Providers\GuardianProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NewsAggregationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_fetches_normalises_and_stores_articles_from_a_provider(): void
    {
        config()->set('news.providers', [
            'guardian' => [
                'enabled' => true,
                'driver' => GuardianProvider::class,
                'label' => 'The Guardian',
                'key' => 'test-key',
                'base_url' => 'https://content.guardianapis.com',
            ],
        ]);

        Http::fake([
            'content.guardianapis.com/*' => Http::response([
                'response' => [
                    'results' => [
                        [
                            'id' => 'tech/article-1',
                            'webTitle' => 'A Guardian Tech Story',
                            'webUrl' => 'https://theguardian.com/article-1',
                            'sectionName' => 'Technology',
                            'webPublicationDate' => '2024-06-01T10:00:00Z',
                            'fields' => [
                                'trailText' => 'Summary',
                                'bodyText' => 'Full body',
                                'byline' => 'Reporter Name',
                                'thumbnail' => 'https://img/1.jpg',
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $summary = app(NewsAggregatorService::class)->fetch();

        $this->assertSame(['guardian' => 1], $summary);
        $this->assertDatabaseHas('articles', [
            'title' => 'A Guardian Tech Story',
            'provider' => 'guardian',
        ]);
        $this->assertDatabaseHas('sources', ['name' => 'The Guardian']);
        $this->assertDatabaseHas('categories', ['slug' => 'technology']);
        $this->assertDatabaseHas('authors', ['name' => 'Reporter Name']);
    }

    public function test_running_the_fetch_twice_does_not_create_duplicates(): void
    {
        config()->set('news.providers', [
            'guardian' => [
                'enabled' => true,
                'driver' => GuardianProvider::class,
                'label' => 'The Guardian',
                'key' => 'test-key',
                'base_url' => 'https://content.guardianapis.com',
            ],
        ]);

        Http::fake([
            'content.guardianapis.com/*' => Http::response([
                'response' => [
                    'results' => [
                        [
                            'id' => 'tech/article-1',
                            'webTitle' => 'Same Story',
                            'webUrl' => 'https://theguardian.com/article-1',
                            'sectionName' => 'Technology',
                            'webPublicationDate' => '2024-06-01T10:00:00Z',
                            'fields' => ['bodyText' => 'Body'],
                        ],
                    ],
                ],
            ]),
        ]);

        app(NewsAggregatorService::class)->fetch();
        app(NewsAggregatorService::class)->fetch();

        $this->assertSame(1, Article::count());
    }
}
