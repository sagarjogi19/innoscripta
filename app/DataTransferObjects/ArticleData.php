<?php

namespace App\DataTransferObjects;

use Illuminate\Support\Carbon;

/**
 * Provider-agnostic representation of a single article. Every provider maps its
 * own payload into this DTO, so the rest of the system never has to know which
 * API the data originally came from (normalisation = DRY + Open/Closed).
 */
final class ArticleData
{
    public function __construct(
        public readonly string $provider,
        public readonly string $title,
        public readonly string $url,
        public readonly ?string $externalId = null,
        public readonly ?string $description = null,
        public readonly ?string $content = null,
        public readonly ?string $imageUrl = null,
        public readonly ?string $sourceName = null,
        public readonly ?string $categoryName = null,
        public readonly ?string $authorName = null,
        public readonly ?string $language = 'en',
        public readonly ?Carbon $publishedAt = null,
    ) {}

    public function urlHash(): string
    {
        return hash('sha256', $this->url);
    }

    public function isValid(): bool
    {
        return $this->title !== '' && $this->url !== '';
    }
}
