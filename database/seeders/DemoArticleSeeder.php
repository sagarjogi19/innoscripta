<?php

namespace Database\Seeders;

use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Models\Source;
use Illuminate\Database\Seeder;

/**
 * Seeds realistic-looking demo data so the API can be explored without any
 * live provider API keys configured.
 */
class DemoArticleSeeder extends Seeder
{
    public function run(): void
    {
        $sources = Source::factory()->count(5)->create();
        $categories = Category::factory()->count(6)->create();
        $authors = Author::factory()->count(10)->create();

        Article::factory()
            ->count(60)
            ->recycle([$sources, $categories, $authors])
            ->create();
    }
}
