<?php

declare(strict_types=1);

namespace App\Services\News\Observers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class CacheObserver implements NewsObserverInterface
{
    public function onNewsUpdated(Collection $articles): void
    {
        Cache::forget('latest_articles');
        Cache::put('latest_articles', $articles->take(10), now()->addHour());
    }
}
