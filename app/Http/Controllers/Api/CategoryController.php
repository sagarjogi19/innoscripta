<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Support\Filters\TaxonomyFilter;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryController extends Controller
{
    public function index(TaxonomyFilter $filter): AnonymousResourceCollection
    {
        $categories = Category::query()
            ->withCount('articles')
            ->filter($filter)
            ->when(! $filter->appliedSort(), fn ($q) => $q->orderBy('name'))
            ->get();

        return CategoryResource::collection($categories);
    }
}
