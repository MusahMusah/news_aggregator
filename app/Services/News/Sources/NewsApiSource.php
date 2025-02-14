<?php

declare(strict_types=1);

namespace App\Services\News\Sources;

use App\DataTransferObjects\ArticleData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class NewsApiSource extends AbstractNewsSource
{
    protected string $baseUrl = 'https://newsapi.org/v2/';

    public function fetchArticles(): Collection
    {
        $data = $this->fetch('top-headlines', [
            'apiKey' => $this->apiKey,
            'language' => 'en',
        ]);

        return collect($data['articles'] ?? [])->map(fn ($article) => ArticleData::from([
            'title' => $article['title'],
            'description' => $article['description'],
            'content' => $article['content'] ?? null,
            'author' => $article['author'] ?? null,
            'source' => 'NewsAPI - ' . $article['source']['name'],
            'url' => $article['url'],
            'image' => $article['urlToImage'],
            'published_at' => CarbonImmutable::parse($article['publishedAt']),
        ]));
    }

    public function getName(): string
    {
        return 'NewsAPI';
    }
}
