<?php

declare(strict_types=1);

namespace App\Services\News\Observers;

use Illuminate\Support\Collection;

interface NewsObserverInterface
{
    public function onNewsUpdated(Collection $articles): void;
}
