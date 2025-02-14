<?php

declare(strict_types=1);

namespace App\Services\News;

use App\DataTransferObjects\ArticleData;
use App\Interfaces\NewsSourceInterface;
use App\Models\Article;
use App\Models\Author;
use App\Services\News\Observers\NewsObserverInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

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
        return DB::transaction(function () use ($articleData) {
            $article = Article::query()->updateOrCreate(
                ['url' => $articleData->url],
                $articleData->except('author')->toArray()
            );

            if ( ! empty($articleData->author)) {
                $authorNames = array_map('trim', explode(',', $articleData->author));

                $authors = collect($authorNames)->map(fn ($name) => Author::query()->firstOrCreate(['name' => $name]));

                $article->authors()->sync($authors->pluck('id')->toArray());
            }

            return $article->load('authors');
        });
    }

    private function notifyObservers(Collection $articles): void
    {
        $this->observers->each(function ($observer) use ($articles): void {
            $observer->onNewsUpdated($articles);
        });
    }
}
