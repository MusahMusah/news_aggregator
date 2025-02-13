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
            $aggregator->addSource(new NewsApiSource(config('services.newsapi.key')));
            $aggregator->addSource(new GuardianSource(config('services.guardian.key')));

            // Add observers
            $aggregator->addObserver(new CacheObserver());

            return $aggregator;
        });
    }
}
