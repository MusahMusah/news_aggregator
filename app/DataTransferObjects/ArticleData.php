<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use App\Models\Article;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;
use Spatie\LaravelData\Lazy;

final class ArticleData extends Data
{
    public function __construct(
        readonly public ?int $id,
        readonly public string $title,
        readonly public ?string $author,
        readonly public ?string $content,
        readonly public ?string $category,
        readonly public ?string $description,
        readonly public string $source,
        readonly public string $url,
        readonly public ?string $image,
        readonly public CarbonImmutable $published_at,
        #[DataCollectionOf(AuthorData::class)]
        public DataCollection|Lazy|null $authors,
        #[DataCollectionOf(CategoryData::class)]
        public DataCollection|Lazy|null $categories,
    ) {
    }

    public static function fromModel(Article $article): self
    {
        return self::from([
            ...$article->toArray(),
            'published_at' => CarbonImmutable::parse($article->published_at),
            'authors' => Lazy::whenLoaded('authors', $article, fn () => AuthorData::collect($article->authors)),
            'categories' => Lazy::whenLoaded('categories', $article, fn () => CategoryData::collect($article->categories)),
        ])->exclude('author', 'category');
    }
}
