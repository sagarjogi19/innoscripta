<?php

namespace App\Services\News\Providers;

use App\DataTransferObjects\ArticleData;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Driver for The Guardian Open Platform (https://open-platform.theguardian.com).
 */
class GuardianProvider extends AbstractNewsProvider
{
    private const SOURCE = 'The Guardian';

    public function fetchLatest(): Collection
    {
        $response = $this->client()->get('/search', [
            'api-key' => $this->apiKey(),
            'order-by' => 'newest',
            'page-size' => $this->pageSize(),
            'show-fields' => 'trailText,bodyText,thumbnail,byline',
        ]);

        if ($response->failed()) {
            $this->logFailure('search request failed', ['status' => $response->status()]);

            return collect();
        }

        return collect($response->json('response.results', []))
            ->map(fn (array $item) => $this->toArticleData($item))
            ->filter(fn (ArticleData $article) => $article->isValid())
            ->values();
    }

    private function toArticleData(array $item): ArticleData
    {
        return new ArticleData(
            provider: $this->key(),
            title: (string) Arr::get($item, 'webTitle', ''),
            url: (string) Arr::get($item, 'webUrl', ''),
            externalId: Arr::get($item, 'id'),
            description: Arr::get($item, 'fields.trailText'),
            content: Arr::get($item, 'fields.bodyText'),
            imageUrl: Arr::get($item, 'fields.thumbnail'),
            sourceName: self::SOURCE,
            categoryName: Arr::get($item, 'sectionName'),
            authorName: Arr::get($item, 'fields.byline'),
            language: 'en',
            publishedAt: $this->parseDate(Arr::get($item, 'webPublicationDate')),
        );
    }

    private function parseDate(?string $date): ?Carbon
    {
        return $date ? Carbon::parse($date) : null;
    }
}
