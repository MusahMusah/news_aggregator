<?php

declare(strict_types=1);

namespace App\Services\News;

use App\DataTransferObjects\ArticleData;
use App\Interfaces\NewsSourceInterface;
use App\Models\Article;
use App\Models\Author;
use App\Models\Category;
use App\Services\News\Observers\NewsObserverInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class NewsAggregator
{
    public Collection $sources;

    public Collection $observers;

    public function __construct()
    {
        $this->sources = collect();
        $this->observers = collect();
    }

    public function addSource(NewsSourceInterface $source): self
    {
        $this->sources->push($source);

        return $this;
    }

    public function addObserver(NewsObserverInterface $observer): self
    {
        $this->observers->push($observer);

        return $this;
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

            if ($articleData->category) {
                $category = Category::query()->firstOrCreate(['name' => $articleData->category]);
                $article->categories()->sync($category->id);
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
