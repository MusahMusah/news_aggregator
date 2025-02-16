<?php

declare(strict_types=1);

namespace App\Services\News\Sources;

use App\DataTransferObjects\ArticleData;
use App\Traits\WithLazyCollection;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Support\Collection;

final class GuardianSource extends AbstractNewsSource
{
    use WithLazyCollection;

    protected string $baseUrl = 'https://content.guardianapis.com/';

    public function fetchArticles(): Collection
    {
        $data = $this->fetch('search', [
            'api-key' => $this->apiKey,
            'show-fields' => 'all',
        ]);

        return $this->useLazyCollection($data['response']['results'] ?? [], $this->mapCallBack());
    }

    public function getName(): string
    {
        return 'The Guardian';
    }

    public function mapCallBack(): Closure
    {
        return fn ($article) => ArticleData::from([
            'title' => $article['webTitle'],
            'description' => $article['fields']['trailText'] ?? null,
            'content' => $article['fields']['bodyText'] ?? null,
            'author' => $article['fields']['byline'] ?? null,
            'category' => $article['sectionName'] ?? null,
            'source' => 'The Guardian - ' . $article['fields']['publication'] ?? '',
            'url' => $article['webUrl'],
            'image' => optional($article['fields'])['thumbnail'],
            'published_at' => CarbonImmutable::parse($article['webPublicationDate']),
        ]);
    }
}
