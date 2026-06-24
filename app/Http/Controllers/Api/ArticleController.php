<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArticleIndexRequest;
use App\Http\Resources\ArticleResource;
use App\Services\ArticleService;
use App\Support\Filters\ArticleFilter;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ArticleController extends Controller
{
    public function __construct(private readonly ArticleService $articles) {}

    /**
     * GET /api/articles
     * Search, filter (date, category, source, author, provider) and sort.
     */
    public function index(ArticleIndexRequest $request, ArticleFilter $filter): AnonymousResourceCollection
    {
        $articles = $this->articles->paginate($filter, $this->perPage($request));

        return ArticleResource::collection($articles);
    }

    /**
     * GET /api/articles/{article}
     */
    public function show(int $article): ArticleResource
    {
        return new ArticleResource($this->articles->find($article));
    }

    private function perPage(Request $request): int
    {
        return (int) $request->integer('per_page', 15);
    }
}
