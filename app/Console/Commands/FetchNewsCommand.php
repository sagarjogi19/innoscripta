<?php

namespace App\Console\Commands;

use App\Services\News\NewsAggregatorService;
use Illuminate\Console\Command;

class FetchNewsCommand extends Command
{
    protected $signature = 'news:fetch';

    protected $description = 'Fetch the latest articles from all enabled providers and store them locally';

    public function handle(NewsAggregatorService $aggregator): int
    {
        $this->info('Fetching articles from enabled providers...');

        $summary = $aggregator->fetch(function (string $provider, int $count): void {
            $this->line("  - {$provider}: stored {$count} articles");
        });

        $total = array_sum($summary);

        if ($summary === []) {
            $this->warn('No providers are enabled. Add API keys to your .env file.');

            return self::SUCCESS;
        }

        $this->info("Done. {$total} articles stored/updated across ".count($summary).' provider(s).');

        return self::SUCCESS;
    }
}
