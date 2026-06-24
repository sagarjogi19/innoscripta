<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('source_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('author_id')->nullable()->constrained()->nullOnDelete();

            $table->string('provider')->index()->comment('Origin API key, e.g. newsapi, guardian, nyt');
            $table->string('external_id')->nullable()->comment('Provider side identifier');

            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('content')->nullable();

            $table->string('url', 1000);
            $table->string('url_hash', 64)->unique()->comment('sha256 of url for reliable dedupe');
            $table->string('image_url', 1000)->nullable();

            $table->string('language', 10)->nullable()->default('en');
            $table->timestamp('published_at')->nullable()->index();

            $table->timestamps();

            $table->index('title');
            $table->index(['provider', 'external_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
