<?php

namespace App\Services;

use App\Models\Article;
use App\Models\User;
use App\Support\Filters\ArticleFilter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class ArticleService
{
    private const RELATIONS = ['source', 'category', 'author'];

    /**
     * Paginated, filtered list of articles.
     */
    public function paginate(ArticleFilter $filter, int $perPage): LengthAwarePaginator
    {
        return $this->baseQuery()
            ->filter($filter)
            ->when(! $filter->appliedSort(), fn (Builder $q) => $q->latest('published_at'))
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Personalised feed: articles matching any of the user's preferred
     * sources, categories or authors, while still honouring active filters.
     */
    public function feedFor(User $user, ArticleFilter $filter, int $perPage): LengthAwarePaginator
    {
        $preference = $user->preference;

        return $this->baseQuery()
            ->filter($filter)
            ->when($preference, function (Builder $query) use ($preference): void {
                $query->where(function (Builder $inner) use ($preference): void {
                    if ($preference->sources) {
                        $inner->orWhereHas('source', fn (Builder $q) => $q->whereIn('slug', $preference->sources));
                    }
                    if ($preference->categories) {
                        $inner->orWhereHas('category', fn (Builder $q) => $q->whereIn('slug', $preference->categories));
                    }
                    if ($preference->authors) {
                        $inner->orWhereHas('author', fn (Builder $q) => $q->whereIn('slug', $preference->authors));
                    }
                });
            })
            ->when(! $filter->appliedSort(), fn (Builder $q) => $q->latest('published_at'))
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): Article
    {
        return $this->baseQuery()->findOrFail($id);
    }

    private function baseQuery(): Builder
    {
        return Article::query()->with(self::RELATIONS);
    }
}
