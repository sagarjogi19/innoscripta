<?php

namespace App\Support\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Reusable, declarative query filter.
 *
 * Each request parameter is mapped (via camelCase) to a method on the concrete
 * filter. Generic `search` and `sort` behaviour is provided here so any listing
 * only has to declare its searchable / sortable columns and any custom filters.
 *
 * Example: ?search=ai&category=technology&sort=-published_at,title
 */
abstract class QueryFilter
{
    protected Builder $builder;

    /** Columns included in the generic free-text search. */
    protected array $searchable = [];

    /** Columns allowed for sorting via the `sort` parameter. */
    protected array $sortable = [];

    /** Whether a valid sort was applied during this request. */
    protected bool $sorted = false;

    public function __construct(protected Request $request) {}

    public function apply(Builder $builder): Builder
    {
        $this->builder = $builder;

        foreach ($this->activeFilters() as $name => $value) {
            $method = Str::camel($name);

            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }

        return $this->builder;
    }

    /**
     * Whether the request resulted in at least one valid sort clause. Lets
     * callers apply a sensible default ordering only when none was requested.
     */
    public function appliedSort(): bool
    {
        return $this->sorted;
    }

    /**
     * Request parameters that actually carry a value, so empty inputs are
     * silently ignored instead of producing meaningless WHERE clauses.
     */
    protected function activeFilters(): array
    {
        return array_filter(
            $this->request->all(),
            static fn ($value) => $value !== null && $value !== '' && $value !== [],
        );
    }

    /**
     * Generic free-text search across the declared searchable columns.
     */
    public function search(string $value): void
    {
        if ($this->searchable === []) {
            return;
        }

        $this->builder->where(function (Builder $query) use ($value): void {
            foreach ($this->searchable as $column) {
                $query->orWhere($column, 'like', "%{$value}%");
            }
        });
    }

    /**
     * Generic multi-column sort. Prefix a column with "-" for descending order.
     * Example: sort=-published_at,title
     */
    public function sort(string $value): void
    {
        foreach (explode(',', $value) as $field) {
            $field = trim($field);
            $direction = str_starts_with($field, '-') ? 'desc' : 'asc';
            $column = ltrim($field, '-');

            if (in_array($column, $this->sortable, true)) {
                $this->builder->orderBy($column, $direction);
                $this->sorted = true;
            }
        }
    }

    /**
     * Normalise a value that may be a comma separated string or an array
     * into a clean list of trimmed values.
     */
    protected function splitList(mixed $value): array
    {
        $values = is_array($value) ? $value : explode(',', (string) $value);

        return array_values(array_filter(array_map('trim', $values), static fn ($v) => $v !== ''));
    }

    /**
     * Constrain a related query by slug(s) or numeric id(s). Shared by the
     * source / category / author style relationship filters.
     */
    protected function matchSlugOrId(Builder $query, mixed $value): void
    {
        $values = $this->splitList($value);

        $query->where(function (Builder $inner) use ($values): void {
            $inner->whereIn('slug', $values);

            $numericIds = array_filter($values, 'is_numeric');
            if ($numericIds !== []) {
                $inner->orWhereIn('id', $numericIds);
            }
        });
    }
}
