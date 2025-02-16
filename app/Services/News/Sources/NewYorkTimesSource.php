<?php

declare(strict_types=1);

namespace App\Services\News\Sources;

use App\DataTransferObjects\ArticleData;
use App\Traits\WithLazyCollection;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Collection;

final class NewYorkTimesSource extends AbstractNewsSource
{
    use WithLazyCollection;

    protected string $baseUrl = 'https://api.nytimes.com/svc/search/v2/';

    public function fetchArticles(): Collection
    {
        $data = $this->fetch('articlesearch.json', [
            'api-key' => $this->apiKey,
            'q' => 'news',
        ]);

        return $this->useLazyCollection($data['response']['docs'] ?? [], $this->mapCallBack());
    }

    public function getName(): string
    {
        return 'The New York Times';
    }

    public function mapCallBack(): Closure
    {
        return fn ($article) => ArticleData::from([
            'title' => $article['headline']['main'],
            'description' => $article['abstract'] ?? null,
            'content' => $article['lead_paragraph'] ?? null,
            'author' => $article['byline']['original'] ?? 'Unknown Author',
            'source' => 'The New York Times',
            'category' => $article['news_desk'] ?? $article['section_name'] ?? 'Uncategorized',
            'url' => $article['web_url'] ?? null,
            'image' => $this->extractImageUrl($article),
            'published_at' => isset($article['pub_date']) ? CarbonImmutable::parse($article['pub_date']) : null,
        ]);
    }

    private function extractImageUrl(array $article): ?string
    {
        $media = collect($article['multimedia'] ?? [])->first(fn ($media) => isset($media['url']));

        return optional($media)['url'] ? 'https://www.nytimes.com/' . $media['url'] : null;
    }
}
