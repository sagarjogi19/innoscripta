<?php

namespace App\Providers;

use App\Services\News\ArticleRepository;
use App\Services\News\NewsAggregatorService;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(NewsAggregatorService::class, function ($app) {
            $providers = Collection::make(config('news.providers'))
                ->filter(fn (array $config) => ($config['enabled'] ?? false) && ! empty($config['key']))
                ->map(function (array $config, string $key) use ($app) {
                    $config['key_name'] = $key;

                    return $app->make($config['driver'], ['config' => $config]);
                })
                ->values();

            return new NewsAggregatorService($providers, $app->make(ArticleRepository::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
