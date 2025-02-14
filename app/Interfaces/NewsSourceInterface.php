<?php

declare(strict_types=1);

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface NewsSourceInterface
{
    public function fetchArticles(): Collection;

    public function getName(): string;
}
