<?php

namespace App\Support\Filters;

/**
 * Generic filter for simple name/slug lookup tables (sources, categories,
 * authors). Demonstrates how the same filtering engine powers any listing.
 */
class TaxonomyFilter extends QueryFilter
{
    protected array $searchable = ['name'];

    protected array $sortable = ['name', 'created_at'];
}
