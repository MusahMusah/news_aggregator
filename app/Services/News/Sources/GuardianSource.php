<?php

declare(strict_types=1);

namespace App\Services\News\Sources;

use App\DataTransferObjects\ArticleData;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

class GuardianSource extends AbstractNewsSource
{
    protected string $baseUrl = 'https://content.guardianapis.com/';

    public function fetchArticles(): Collection
    {
        $data = $this->fetch('search', [
            'api-key' => $this->apiKey,
            'show-fields' => 'all',
        ]);

        return collect($data['response']['results'] ?? [])->map(function ($article) {
            return ArticleData::from([
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
        });
    }

    public function getName(): string
    {
        return 'The Guardian';
    }
}
