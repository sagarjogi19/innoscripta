<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ArticleIndexRequest;
use App\Http\Resources\ArticleResource;
use App\Services\ArticleService;
use App\Support\Filters\ArticleFilter;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class FeedController extends Controller
{
    public function __construct(private readonly ArticleService $articles) {}

    /**
     * GET /api/feed
     * Personalised article feed based on the authenticated user's preferences.
     */
    public function __invoke(ArticleIndexRequest $request, ArticleFilter $filter): AnonymousResourceCollection
    {
        $articles = $this->articles->feedFor(
            $request->user(),
            $filter,
            (int) $request->integer('per_page', 15),
        );

        return ArticleResource::collection($articles);
    }
}
