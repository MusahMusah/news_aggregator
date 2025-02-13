<?php

declare(strict_types=1);

namespace App\Services\News\Observers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CacheObserver implements NewsObserverInterface
{
    public function onNewsUpdated(Collection $articles): void
    {
        Cache::tags(['news'])->flush();
        Cache::tags(['news'])->put('latest_articles', $articles->take(10), now()->addHour());
    }
}