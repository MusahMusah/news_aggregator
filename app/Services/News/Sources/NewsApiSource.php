<?php

declare(strict_types=1);

namespace App\Services\News\Sources;

use App\DataTransferObjects\ArticleData;
use App\Traits\WithLazyCollection;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Collection;

final class NewsApiSource extends AbstractNewsSource
{
    use WithLazyCollection;

    protected string $baseUrl = 'https://newsapi.org/v2/';

    public function fetchArticles(): Collection
    {
        $data = $this->fetchData('top-headlines');

        return $this->useLazyCollection($data['articles'] ?? [], $this->mapCallBack());
    }

    public function getName(): string
    {
        return 'NewsAPI';
    }

    public function mapCallBack(): Closure
    {
        $sources = $this->fetchSources();

        return fn ($article) => ArticleData::from([
            'title' => $article['title'],
            'description' => $article['description'],
            'content' => $article['content'] ?? null,
            'author' => $article['author'] ?? null,
            'category' => optional($sources->firstWhere('name', $article['source']['name']))['category'] ?? null,
            'source' => 'NewsAPI - ' . $article['source']['name'],
            'url' => $article['url'],
            'image' => $article['urlToImage'],
            'published_at' => CarbonImmutable::parse($article['publishedAt']),
        ]);
    }

    private function fetchSources(): Collection
    {
        $data = $this->fetchData('top-headlines/sources');

        return collect($data['sources'] ?? []);
    }

    private function fetchData(string $endpoint): array
    {
        return $this->fetch($endpoint, [
            'apiKey' => $this->apiKey,
            'language' => 'en',
        ]);
    }
}
