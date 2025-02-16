<?php

declare(strict_types=1);

namespace App\Traits;

use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;

trait WithLazyCollection
{
    public function useLazyCollection(iterable $articles, callable $mapCallback): Collection
    {
        return LazyCollection::make($articles)->map($mapCallback)->filter()->collect();
    }
}
