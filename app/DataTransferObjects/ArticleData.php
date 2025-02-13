<?php

namespace App\DataTransferObjects;

use Carbon\CarbonImmutable;
use Spatie\LaravelData\Data;

final class ArticleData extends Data
{
    public function __construct(
        readonly public ?string $author,
        readonly public string $title,
        readonly public ?string $content,
        readonly public ?string $category,
        readonly public string $description,
        readonly public string $source,
        readonly public string $url,
        readonly public ?string $image,
        readonly public CarbonImmutable $published_at
    ) {}
}
