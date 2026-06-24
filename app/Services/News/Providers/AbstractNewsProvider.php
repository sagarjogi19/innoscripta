<?php

namespace App\Services\News\Providers;

use App\Contracts\NewsProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Shared behaviour for HTTP based news providers: configuration access, a
 * pre-configured HTTP client (timeout + retries) and safe logging. Concrete
 * providers only implement the bits that differ (endpoint + payload mapping).
 */
abstract class AbstractNewsProvider implements NewsProvider
{
    /**
     * @param  array<string, mixed>  $config  The provider's config block from config/news.php
     */
    public function __construct(protected array $config) {}

    public function key(): string
    {
        return $this->config['key_name'];
    }

    protected function apiKey(): ?string
    {
        return $this->config['key'] ?? null;
    }

    protected function baseUrl(): string
    {
        return rtrim((string) $this->config['base_url'], '/');
    }

    protected function pageSize(): int
    {
        return (int) config('news.fetch.page_size', 50);
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->timeout((int) config('news.fetch.timeout', 20))
            ->retry((int) config('news.fetch.retries', 2), 1000, throw: false)
            ->acceptJson();
    }

    protected function logFailure(string $message, array $context = []): void
    {
        Log::warning("[news:{$this->key()}] {$message}", $context);
    }
}
