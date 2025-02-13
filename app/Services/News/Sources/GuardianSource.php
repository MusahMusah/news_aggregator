<?php

declare(strict_types=1);

namespace App\Services\News\Sources;

use App\Services\News\Sources\AbstractNewsSource;

class GuardianSource extends AbstractNewsSource
{
    protected string $baseUrl = 'https://content.guardianapis.com/';

    public function fetchArticles(): array
    {
        $data = $this->fetch('search', [
            'api-key' => $this->apiKey,
            'show-fields' => 'all'
        ]);

        return array_map(function ($article) {
            return [
                'title' => $article['webTitle'],
                'description' => $article['fields']['trailText'] ?? '',
                'content' => $article['fields']['bodyText'] ?? '',
                'author' => $article['fields']['byline'] ?? '',
                'source' => 'The Guardian',
                'category' => $article['sectionName'],
                'published_at' => Carbon::parse($article['webPublicationDate']),
                'url' => $article['webUrl'],
                'image_url' => $article['fields']['thumbnail'] ?? ''
            ];
        }, $data['response']['results'] ?? []);
    }

    public function getName(): string
    {
        return 'The Guardian';
    }
}