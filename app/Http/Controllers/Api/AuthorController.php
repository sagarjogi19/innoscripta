<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuthorResource;
use App\Models\Author;
use App\Support\Filters\TaxonomyFilter;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AuthorController extends Controller
{
    public function index(TaxonomyFilter $filter): AnonymousResourceCollection
    {
        $authors = Author::query()
            ->withCount('articles')
            ->filter($filter)
            ->when(! $filter->appliedSort(), fn ($q) => $q->orderBy('name'))
            ->paginate((int) request()->integer('per_page', 50))
            ->withQueryString();

        return AuthorResource::collection($authors);
    }
}
