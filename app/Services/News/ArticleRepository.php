<?php

namespace App\Services\News;

use App\DataTransferObjects\ArticleData;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Source;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Persists normalised ArticleData into the local database.
 *
 * Relationships (source / category / author) are resolved in bulk and articles
 * are written with a single upsert keyed on the URL hash, so re-running the
 * fetch never creates duplicates and keeps existing rows fresh.
 */
class ArticleRepository
{
    public function storeMany(Collection $articles): int
    {
        $articles = $articles
            ->filter(fn (ArticleData $a) => $a->isValid())
            ->keyBy(fn (ArticleData $a) => $a->urlHash());

        if ($articles->isEmpty()) {
            return 0;
        }

        $sources = $this->resolveRelation($articles, 'sourceName', Source::class);
        $categories = $this->resolveRelation($articles, 'categoryName', Category::class);
        $authors = $this->resolveRelation($articles, 'authorName', Author::class);

        $now = now();

        $rows = $articles->map(fn (ArticleData $a) => [
            'source_id' => $this->idFor($sources, $a->sourceName),
            'category_id' => $this->idFor($categories, $a->categoryName),
            'author_id' => $this->idFor($authors, $a->authorName),
            'provider' => $a->provider,
            'external_id' => $a->externalId,
            'title' => Str::limit($a->title, 250, ''),
            'description' => $a->description,
            'content' => $a->content,
            'url' => $a->url,
            'url_hash' => $a->urlHash(),
            'image_url' => $a->imageUrl,
            'language' => $a->language ?? 'en',
            'published_at' => $a->publishedAt,
            'created_at' => $now,
            'updated_at' => $now,
        ])->values()->all();

        Article::upsert(
            $rows,
            uniqueBy: ['url_hash'],
            update: [
                'source_id', 'category_id', 'author_id', 'provider', 'external_id',
                'title', 'description', 'content', 'image_url', 'language',
                'published_at', 'updated_at',
            ],
        );

        return count($rows);
    }

    /**
     * Resolve a map of slug => id for a given relation, creating any missing
     * records in bulk.
     *
     * @param  Collection<int, ArticleData>  $articles
     * @param  class-string  $model
     * @return array<string, int>
     */
    private function resolveRelation(Collection $articles, string $property, string $model): array
    {
        $names = $articles
            ->map(fn (ArticleData $a) => $a->{$property})
            ->filter()
            ->mapWithKeys(fn (string $name) => [Str::slug($name) => trim($name)]);

        if ($names->isEmpty()) {
            return [];
        }

        $existing = $model::whereIn('slug', $names->keys())->pluck('slug')->all();

        $missing = $names
            ->reject(fn ($name, $slug) => in_array($slug, $existing, true))
            ->map(fn ($name, $slug) => [
                'name' => $name,
                'slug' => $slug,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        if ($missing->isNotEmpty()) {
            $model::insert($missing->values()->all());
        }

        return $model::whereIn('slug', $names->keys())->pluck('id', 'slug')->all();
    }

    /**
     * @param  array<string, int>  $map
     */
    private function idFor(array $map, ?string $name): ?int
    {
        return $name ? ($map[Str::slug($name)] ?? null) : null;
    }
}
