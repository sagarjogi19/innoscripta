<?php

use App\Services\News\Providers\GuardianProvider;
use App\Services\News\Providers\NewsApiProvider;
use App\Services\News\Providers\NytProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Fetching behaviour
    |--------------------------------------------------------------------------
    |
    | Global settings that control how the aggregator pulls data from the
    | configured providers. "page_size" is the number of articles requested
    | per provider call and "schedule" is the cron-friendly frequency used
    | by the scheduler (see routes/console.php).
    |
    */

    'fetch' => [
        'page_size' => (int) env('NEWS_FETCH_PAGE_SIZE', 50),
        'schedule' => env('NEWS_FETCH_SCHEDULE', 'hourly'),
        'timeout' => (int) env('NEWS_FETCH_TIMEOUT', 20),
        'retries' => (int) env('NEWS_FETCH_RETRIES', 2),
    ],

    /*
    |--------------------------------------------------------------------------
    | Providers
    |--------------------------------------------------------------------------
    |
    | Each provider maps a key to a concrete driver implementing the
    | App\Contracts\NewsProvider contract. A provider is only used when it is
    | enabled and has an API key. Adding a new source is as simple as adding
    | a new entry here and creating its driver class.
    |
    */

    'providers' => [

        'newsapi' => [
            'enabled' => (bool) env('NEWSAPI_KEY'),
            'driver' => NewsApiProvider::class,
            'label' => 'NewsAPI.org',
            'key' => env('NEWSAPI_KEY'),
            'base_url' => env('NEWSAPI_BASE_URL', 'https://newsapi.org/v2'),
            'categories' => [
                'business', 'entertainment', 'general',
                'health', 'science', 'sports', 'technology',
            ],
        ],

        'guardian' => [
            'enabled' => (bool) env('GUARDIAN_KEY'),
            'driver' => GuardianProvider::class,
            'label' => 'The Guardian',
            'key' => env('GUARDIAN_KEY'),
            'base_url' => env('GUARDIAN_BASE_URL', 'https://content.guardianapis.com'),
        ],

        'nyt' => [
            'enabled' => (bool) env('NYT_KEY'),
            'driver' => NytProvider::class,
            'label' => 'The New York Times',
            'key' => env('NYT_KEY'),
            'base_url' => env('NYT_BASE_URL', 'https://api.nytimes.com/svc'),
        ],

    ],

];
