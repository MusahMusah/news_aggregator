<?php

declare(strict_types=1);

namespace App\Services\News;

use App\DataTransferObjects\ArticleData;
use App\Interfaces\NewsSourceInterface;
use App\Models\Article;
use App\Services\News\Observers\NewsObserverInterface;
use Illuminate\Support\Collection;

final class NewsAggregator
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
        $articles = $this->sources
            ->map(fn (NewsSourceInterface $source) => $source->fetchArticles()
                ->map(fn (ArticleData $article) => $this->saveArticle($article)))
            ->flatten();

        $this->notifyObservers($articles);
    }

    private function saveArticle(ArticleData $articleData): Article
    {
        return Article::query()->updateOrCreate(
            ['url' => $articleData->url],
            $articleData->toArray()
        );
    }

    private function notifyObservers(Collection $articles): void
    {
        $this->observers->each(function ($observer) use ($articles): void {
            $observer->onNewsUpdated($articles);
        });
    }
}
