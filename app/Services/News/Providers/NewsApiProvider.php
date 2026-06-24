<?php

namespace App\Services\News\Providers;

use App\DataTransferObjects\ArticleData;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Driver for NewsAPI.org (https://newsapi.org).
 *
 * Pulls top headlines per configured category so each article carries a
 * meaningful category mapping.
 */
class NewsApiProvider extends AbstractNewsProvider
{
    public function fetchLatest(): Collection
    {
        $articles = collect();
        $categories = Arr::get($this->config, 'categories', ['general']);

        foreach ($categories as $category) {
            $response = $this->client()->get('/top-headlines', [
                'apiKey' => $this->apiKey(),
                'language' => 'en',
                'category' => $category,
                'pageSize' => $this->pageSize(),
            ]);

            if ($response->failed()) {
                $this->logFailure('top-headlines request failed', [
                    'category' => $category,
                    'status' => $response->status(),
                ]);

                continue;
            }

            foreach ($response->json('articles', []) as $item) {
                $article = $this->toArticleData($item, $category);

                if ($article->isValid()) {
                    $articles->push($article);
                }
            }
        }

        return $articles;
    }

    private function toArticleData(array $item, string $category): ArticleData
    {
        return new ArticleData(
            provider: $this->key(),
            title: (string) Arr::get($item, 'title', ''),
            url: (string) Arr::get($item, 'url', ''),
            externalId: Arr::get($item, 'url'),
            description: Arr::get($item, 'description'),
            content: Arr::get($item, 'content'),
            imageUrl: Arr::get($item, 'urlToImage'),
            sourceName: Arr::get($item, 'source.name'),
            categoryName: $category,
            authorName: Arr::get($item, 'author'),
            language: 'en',
            publishedAt: $this->parseDate(Arr::get($item, 'publishedAt')),
        );
    }

    private function parseDate(?string $date): ?Carbon
    {
        return $date ? Carbon::parse($date) : null;
    }
}
