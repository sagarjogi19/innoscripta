<?php

namespace App\Services\News\Providers;

use App\DataTransferObjects\ArticleData;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Driver for the New York Times Top Stories API
 * (https://developer.nytimes.com/docs/top-stories-product/1/overview).
 */
class NytProvider extends AbstractNewsProvider
{
    private const SOURCE = 'The New York Times';

    public function fetchLatest(): Collection
    {
        $response = $this->client()->get('/topstories/v2/home.json', [
            'api-key' => $this->apiKey(),
        ]);

        if ($response->failed()) {
            $this->logFailure('top stories request failed', ['status' => $response->status()]);

            return collect();
        }

        return collect($response->json('results', []))
            ->map(fn (array $item) => $this->toArticleData($item))
            ->filter(fn (ArticleData $article) => $article->isValid())
            ->values();
    }

    private function toArticleData(array $item): ArticleData
    {
        return new ArticleData(
            provider: $this->key(),
            title: (string) Arr::get($item, 'title', ''),
            url: (string) Arr::get($item, 'url', ''),
            externalId: Arr::get($item, 'uri', Arr::get($item, 'url')),
            description: Arr::get($item, 'abstract'),
            content: Arr::get($item, 'abstract'),
            imageUrl: $this->extractImage($item),
            sourceName: self::SOURCE,
            categoryName: Arr::get($item, 'section'),
            authorName: $this->cleanByline(Arr::get($item, 'byline')),
            language: 'en',
            publishedAt: $this->parseDate(Arr::get($item, 'published_date')),
        );
    }

    private function extractImage(array $item): ?string
    {
        $multimedia = Arr::get($item, 'multimedia');

        if (is_array($multimedia)) {
            return Arr::get($multimedia, '0.url') ?? Arr::get($multimedia, 'default.url');
        }

        return null;
    }

    private function cleanByline(?string $byline): ?string
    {
        if (! $byline) {
            return null;
        }

        return Str::of($byline)->after('By ')->trim()->toString() ?: $byline;
    }

    private function parseDate(?string $date): ?Carbon
    {
        return $date ? Carbon::parse($date) : null;
    }
}
