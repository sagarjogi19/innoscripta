# News Aggregator API

A Laravel 13 backend that aggregates articles from multiple live news providers,
stores them locally, keeps them up to date on a schedule, and exposes a clean
REST API for a frontend to search, filter, sort and personalise the news feed.

Built with DRY, KISS and SOLID principles: thin controllers, dedicated service
classes, Form Request validation, API Resources for responses, and a single
**reusable** search / filter / sort engine that works for any listing.

---

## Table of contents

- [Features](#features)
- [Data sources](#data-sources)
- [Architecture](#architecture)
- [Requirements](#requirements)
- [Setup](#setup)
- [Getting API keys](#getting-api-keys)
- [Fetching & scheduling data](#fetching--scheduling-data)
- [API reference](#api-reference)
- [The reusable filter engine](#the-reusable-filter-engine)
- [Testing](#testing)
- [Project structure](#project-structure)

---

## Features

- **Data aggregation** from 3+ providers, normalised into one schema.
- **Local storage** with de-duplication (idempotent upserts on a URL hash).
- **Scheduled refresh** via a single artisan command + the Laravel scheduler.
- **Search** (free-text across title/description/content).
- **Filtering** by date range, category, source, author, provider and language.
- **Sorting** on any allow-listed column, multi-column, asc/desc.
- **Pagination** on every listing.
- **User accounts** (Laravel Sanctum tokens).
- **User preferences** (preferred sources, categories, authors) powering a
  **personalised feed**.
- **Feature tests** covering the API, auth, preferences and the fetch pipeline.

## Data sources

Three providers are implemented out of the box. Each one is a small driver that
maps the provider payload into a common DTO, so adding a fourth source is just a
new class + a config entry.

| Key        | Source             | Docs |
|------------|--------------------|------|
| `newsapi`  | NewsAPI.org        | https://newsapi.org/docs |
| `guardian` | The Guardian       | https://open-platform.theguardian.com/documentation |
| `nyt`      | The New York Times | https://developer.nytimes.com/docs/top-stories-product/1/overview |

A provider is only used when its API key is present in `.env`. If no keys are
configured the app still runs fully — use the demo seeder for sample data.

## Architecture

```
HTTP request
   │
Controller (thin)  ──►  Form Request (validation)
   │
Service  (ArticleService / AuthService / PreferenceService)
   │
Model + QueryFilter (reusable search/filter/sort)
   │
API Resource (response shaping)


Scheduler / Console
   │
news:fetch  ──►  NewsAggregatorService
                     │  iterates providers (NewsProvider contract)
                     ▼
              NewsApiProvider / GuardianProvider / NytProvider
                     │  returns ArticleData[] (normalised DTO)
                     ▼
              ArticleRepository  ──►  upsert into DB (dedupe by URL hash)
```

SOLID highlights:

- **S** — each class has one job (providers fetch, repository stores, services
  orchestrate, resources format).
- **O** — add a new source by creating a driver + config entry; no existing code
  changes.
- **L** — every provider is interchangeable behind `NewsProvider`.
- **I** — the `NewsProvider` contract is tiny and focused.
- **D** — the aggregator depends on the `NewsProvider` abstraction, wired in
  `AppServiceProvider` from config.

## Requirements

- PHP **8.4+** (the lock file targets 8.4)
- Composer 2
- SQLite (default, zero-config) or MySQL/PostgreSQL

> On Laragon/Windows make sure the CLI uses PHP 8.4, e.g.
> `D:\laragon\bin\php\php-8.4.x-...\php.exe`.

## Setup

```bash
# 1. Install dependencies
composer install

# 2. Environment
cp .env.example .env        # Windows: copy .env.example .env
php artisan key:generate

# 3. Database (SQLite is the default)
#    Create the sqlite file if it does not exist:
#    Windows: type nul > database\database.sqlite
#    Unix:    touch database/database.sqlite
php artisan migrate

# 4. (Optional) Seed demo data so you can explore the API without API keys
php artisan db:seed

# 5. Add your provider API keys to .env (see below), then fetch live data
php artisan news:fetch

# 6. Serve
php artisan serve
```

The API is now available at `http://127.0.0.1:8000/api`.

### Using MySQL instead of SQLite

Set the following in `.env` and re-run `php artisan migrate`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=news_aggregator
DB_USERNAME=root
DB_PASSWORD=
```

## Getting API keys

Add the keys you have to `.env` (any subset works — disabled providers are
skipped automatically):

```env
NEWSAPI_KEY=your_newsapi_org_key
GUARDIAN_KEY=your_guardian_key
NYT_KEY=your_nyt_key

# Optional fetch tuning
NEWS_FETCH_PAGE_SIZE=50
NEWS_FETCH_SCHEDULE=hourly      # everyMinute | everyFiveMinutes | everyThirtyMinutes | hourly | daily
NEWS_FETCH_TIMEOUT=20
NEWS_FETCH_RETRIES=2
```

- **NewsAPI.org** – register at https://newsapi.org/register
- **The Guardian** – register at https://open-platform.theguardian.com/access
- **New York Times** – create an app at https://developer.nytimes.com

## Fetching & scheduling data

Fetch on demand:

```bash
php artisan news:fetch
```

This pulls the latest articles from every enabled provider and upserts them
(no duplicates on re-runs).

Keep data fresh automatically. The schedule is already registered in
`routes/console.php` (frequency from `NEWS_FETCH_SCHEDULE`, default hourly).
Run the scheduler:

```bash
# Local development
php artisan schedule:work

# Production (cron) — add this single entry:
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## API reference

Base URL: `/api`. All responses are JSON. List endpoints are paginated and
include `data`, `links` and `meta`.

### Public endpoints

| Method | Endpoint               | Description |
|--------|------------------------|-------------|
| GET    | `/api/articles`        | List/search/filter/sort articles |
| GET    | `/api/articles/{id}`   | Single article |
| GET    | `/api/sources`         | List sources (with article counts) |
| GET    | `/api/categories`      | List categories (with article counts) |
| GET    | `/api/authors`         | List authors (paginated) |
| POST   | `/api/auth/register`   | Register, returns a token |
| POST   | `/api/auth/login`      | Login, returns a token |

### Authenticated endpoints (Bearer token via Sanctum)

| Method | Endpoint              | Description |
|--------|-----------------------|-------------|
| GET    | `/api/auth/me`        | Current user |
| POST   | `/api/auth/logout`    | Revoke current token |
| GET    | `/api/preferences`    | Get saved preferences |
| PUT    | `/api/preferences`    | Update preferences |
| GET    | `/api/feed`           | Personalised feed based on preferences |

### Query parameters for `/api/articles` (and `/api/feed`)

| Param        | Example                       | Description |
|--------------|-------------------------------|-------------|
| `search`     | `search=climate`              | Free-text across title/description/content |
| `category`   | `category=technology`         | Slug or id (comma-separated for multiple) |
| `source`     | `source=the-guardian`         | Slug or id (comma-separated) |
| `author`     | `author=jane-doe`             | Slug or id (comma-separated) |
| `provider`   | `provider=guardian,nyt`       | One or more provider keys |
| `language`   | `language=en`                 | Language code |
| `date_from`  | `date_from=2024-01-01`        | Published on/after |
| `date_to`    | `date_to=2024-12-31`          | Published on/before |
| `sort`       | `sort=-published_at,title`    | `-` prefix = descending; comma-separated |
| `per_page`   | `per_page=20`                 | Page size (1–100, default 15) |
| `page`       | `page=2`                      | Page number |

Sortable columns: `published_at`, `title`, `created_at`.

### Examples

```bash
# Search + filter + sort + paginate
curl "http://127.0.0.1:8000/api/articles?search=ai&category=technology&provider=guardian&sort=-published_at&per_page=10"

# Date range
curl "http://127.0.0.1:8000/api/articles?date_from=2024-01-01&date_to=2024-06-30"

# Register
curl -X POST http://127.0.0.1:8000/api/auth/register \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"name":"Jane","email":"jane@example.com","password":"password123","password_confirmation":"password123"}'

# Save preferences (use the token from register/login)
curl -X PUT http://127.0.0.1:8000/api/preferences \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" -H "Accept: application/json" \
  -d '{"sources":["The Guardian"],"categories":["Technology"],"authors":[]}'

# Personalised feed
curl "http://127.0.0.1:8000/api/feed" -H "Authorization: Bearer YOUR_TOKEN"
```

## The reusable filter engine

Search, filtering and sorting are not hard-coded per controller. They live in a
small reusable engine so **any** listing can opt in:

- `App\Support\Filters\QueryFilter` — abstract base providing generic `search`
  and `sort`, plus list/relationship helpers. Maps request params to methods.
- `App\Models\Concerns\Filterable` — a trait adding a `filter()` query scope to
  any model.
- Concrete filters declare only what differs:
  - `ArticleFilter` — searchable/sortable columns + `category`, `source`,
    `author`, `provider`, `language`, `dateFrom`, `dateTo`.
  - `TaxonomyFilter` — reused by sources/categories/authors.

Add filtering to a new model in two steps:

```php
// 1. Use the trait on the model
class Product extends Model { use Filterable; }

// 2. Declare a filter
class ProductFilter extends QueryFilter
{
    protected array $searchable = ['name', 'sku'];
    protected array $sortable   = ['price', 'created_at'];

    public function brand($value): void
    {
        $this->builder->whereHas('brand', fn ($q) => $this->matchSlugOrId($q, $value));
    }
}

// 3. Use it in a controller (auto-resolved from the request)
public function index(ProductFilter $filter)
{
    return ProductResource::collection(
        Product::query()->filter($filter)->paginate()
    );
}
```

## Testing

```bash
php artisan test
```

Tests run against an in-memory SQLite database and cover:

- Article listing, search, category filter, date-range filter, single article.
- Auth registration and preference-driven personalised feed (incl. auth guard).
- The full fetch pipeline using faked HTTP, including de-duplication on re-run.

## Project structure

```
app/
├── Console/Commands/FetchNewsCommand.php      # php artisan news:fetch
├── Contracts/NewsProvider.php                 # provider interface
├── DataTransferObjects/ArticleData.php        # normalised article DTO
├── Http/
│   ├── Controllers/Api/                       # thin controllers
│   ├── Requests/                              # validation
│   └── Resources/                             # response shaping
├── Models/
│   ├── Article, Source, Category, Author, UserPreference
│   └── Concerns/Filterable.php                # filter() scope
├── Services/
│   ├── ArticleService, AuthService, PreferenceService
│   └── News/
│       ├── NewsAggregatorService.php          # orchestrates providers
│       ├── ArticleRepository.php              # bulk store + dedupe
│       └── Providers/                         # NewsAPI / Guardian / NYT drivers
└── Support/Filters/                           # QueryFilter, ArticleFilter, TaxonomyFilter
config/news.php                                # providers + fetch config
routes/api.php                                 # API routes
routes/console.php                             # scheduler
database/migrations, factories, seeders
tests/Feature                                  # feature tests
```
