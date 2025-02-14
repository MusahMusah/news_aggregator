<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\News\NewsAggregator;
use App\Services\News\Observers\CacheObserver;
use App\Services\News\Sources\GuardianSource;
use App\Services\News\Sources\NewsApiSource;
use App\Services\News\Sources\NewYorkTimesSource;
use Illuminate\Support\ServiceProvider;

final class NewsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(NewsAggregator::class, function () {
            $aggregator = new NewsAggregator();

            $aggregator->addSource(new NewsApiSource(config('news_sources.newsapi.api_key')));
            if ( ! app()->environment('testing')) {
                $aggregator->addSource(new GuardianSource(config('news_sources.guardian.api_key')));
                $aggregator->addSource(new NewYorkTimesSource(config('news_sources.new_york_times.api_key')));
            }

            // Add observers
            $aggregator->addObserver(new CacheObserver());

            return $aggregator;
        });
    }
}
