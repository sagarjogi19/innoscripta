<?php

namespace App\Contracts;

use App\DataTransferObjects\ArticleData;
use Illuminate\Support\Collection;

/**
 * Contract every news source driver must implement. Programming against this
 * interface (Dependency Inversion / Interface Segregation) lets the aggregator
 * treat all providers identically and makes adding a new source trivial.
 */
interface NewsProvider
{
    /**
     * Unique key for the provider, e.g. "newsapi", "guardian", "nyt".
     */
    public function key(): string;

    /**
     * Fetch the latest articles from the source as normalised DTOs.
     *
     * @return Collection<int, ArticleData>
     */
    public function fetchLatest(): Collection;
}
