<?php

namespace App\Support\Filters;

use Illuminate\Database\Eloquent\Builder;

/**
 * Concrete filter for the articles listing. Declares which columns are
 * searchable / sortable and the filtering criteria required by the brief:
 * search query, date range, category, source and author.
 */
class ArticleFilter extends QueryFilter
{
    protected array $searchable = ['title', 'description', 'content'];

    protected array $sortable = ['published_at', 'title', 'created_at'];

    public function category(mixed $value): void
    {
        $this->builder->whereHas('category', fn (Builder $q) => $this->matchSlugOrId($q, $value));
    }

    public function source(mixed $value): void
    {
        $this->builder->whereHas('source', fn (Builder $q) => $this->matchSlugOrId($q, $value));
    }

    public function author(mixed $value): void
    {
        $this->builder->whereHas('author', fn (Builder $q) => $this->matchSlugOrId($q, $value));
    }

    public function provider(mixed $value): void
    {
        $this->builder->whereIn('provider', $this->splitList($value));
    }

    public function language(mixed $value): void
    {
        $this->builder->whereIn('language', $this->splitList($value));
    }

    public function dateFrom(string $value): void
    {
        $this->builder->whereDate('published_at', '>=', $value);
    }

    public function dateTo(string $value): void
    {
        $this->builder->whereDate('published_at', '<=', $value);
    }
}
