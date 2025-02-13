<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface NewsSourceInterface
{
    public function fetchArticles(): Collection;

    public function getName(): string;
}
