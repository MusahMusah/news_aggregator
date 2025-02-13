<?php

declare(strict_types=1);

namespace App\Services\News;

use App\Interfaces\NewsSourceInterface;
use App\Models\Article;
use App\Services\News\Observers\NewsObserverInterface;
use Illuminate\Support\Collection;

class NewsAggregator
{
    private Collection $sources;
    private Collection $observers;

    public function __construct()
    {
        $this->sources = collect();
        $this->observers = collect();
    }

    public function addSource(NewsSourceInterface $source): void
    {
        $this->sources->push($source);
    }

    public function addObserver(NewsObserverInterface $observer): void
    {
        $this->observers->push($observer);
    }

    public function fetchNews(): void
    {
        $articles = $this->sources->map(function (NewsSourceInterface $source) {
            return collect($source->fetchArticles())->map(function ($article) use ($source) {
                return $this->saveArticle($article);
            });
        })->flatten();

        $this->notifyObservers($articles);
    }

    private function saveArticle(array $articleData): Article
    {
        return Article::query()->updateOrCreate(
            ['url' => $articleData['url']],
            $articleData
        );
    }

    private function notifyObservers(Collection $articles): void
    {
        $this->observers->each(function ($observer) use ($articles) {
            $observer->onNewsUpdated($articles);
        });
    }
}