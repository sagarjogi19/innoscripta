<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\SourceResource;
use App\Models\Source;
use App\Support\Filters\TaxonomyFilter;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SourceController extends Controller
{
    public function index(TaxonomyFilter $filter): AnonymousResourceCollection
    {
        $sources = Source::query()
            ->withCount('articles')
            ->filter($filter)
            ->when(! $filter->appliedSort(), fn ($q) => $q->orderBy('name'))
            ->get();

        return SourceResource::collection($sources);
    }
}
