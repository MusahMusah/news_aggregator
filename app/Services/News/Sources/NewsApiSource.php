<?php

declare(strict_types=1);

namespace App\Services\News\Sources;

use App\Services\News\Sources\AbstractNewsSource;

class NewsApiSource extends AbstractNewsSource
{
    protected string $baseUrl = 'https://newsapi.org/v2/';

    public function fetchArticles(): array
    {
        $data = $this->fetch('top-headlines', [
            'apiKey' => $this->apiKey,
            'language' => 'en'
        ]);

        return array_map(function ($article) {
            return [
                'title' => $article['title'],
                'description' => $article['description'],
                'content' => $article['content'],
                'author' => $article['author'],
                'source' => 'NewsAPI - ' . $article['source']['name'],
                'category' => 'general',
                'published_at' => Carbon::parse($article['publishedAt']),
                'url' => $article['url'],
                'image_url' => $article['urlToImage']
            ];
        }, $data['articles'] ?? []);
    }

    public function getName(): string
    {
        return 'NewsAPI';
    }
}