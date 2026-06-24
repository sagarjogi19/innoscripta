<?php

namespace App\Models\Concerns;

use App\Support\Filters\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

/**
 * Gives any Eloquent model a reusable `filter()` scope that delegates the
 * search / filter / sort logic to a dedicated QueryFilter object. This keeps
 * controllers thin and makes listing behaviour reusable across models.
 */
trait Filterable
{
    public function scopeFilter(Builder $query, QueryFilter $filter): Builder
    {
        return $filter->apply($query);
    }
}
