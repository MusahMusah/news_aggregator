<?php

namespace App\Providers;

use App\Services\News\NewsAggregator;
use App\Services\News\Observers\CacheObserver;
use App\Services\News\Sources\GuardianSource;
use App\Services\News\Sources\NewsApiSource;
use Illuminate\Support\ServiceProvider;

class NewsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NewsAggregator::class, function () {
            $aggregator = new NewsAggregator();

            // Add news sources
            $aggregator->addSource(new NewsApiSource(config('news_sources.newsapi.api_key')));
            $aggregator->addSource(new GuardianSource(config('news_sources.guardian.api_key')));

            // Add observers
            $aggregator->addObserver(new CacheObserver());

            return $aggregator;
        });
    }
}
