<?php

namespace App\Services\News;

use App\Contracts\NewsProvider;
use Throwable;

/**
 * Orchestrates the data aggregation: asks every registered provider for its
 * latest articles and hands them to the repository for storage. Providers are
 * isolated so a single failing source never aborts the whole run.
 */
class NewsAggregatorService
{
    /**
     * @param  iterable<NewsProvider>  $providers
     */
    public function __construct(
        private readonly iterable $providers,
        private readonly ArticleRepository $repository,
    ) {}

    /**
     * Fetch and store articles from every provider.
     *
     * @return array<string, int> Map of provider key => number of articles stored
     */
    public function fetch(?callable $onProvider = null): array
    {
        $summary = [];

        foreach ($this->providers as $provider) {
            $key = $provider->key();

            try {
                $articles = $provider->fetchLatest();
                $summary[$key] = $this->repository->storeMany($articles);
            } catch (Throwable $e) {
                report($e);
                $summary[$key] = 0;
            }

            if ($onProvider) {
                $onProvider($key, $summary[$key]);
            }
        }

        return $summary;
    }
}
