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
use Illuminate\Support\Str;

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
        $this->sources
            ->each(function (NewsSourceInterface $source): void {
                // Process articles in chunks to prevent memory issues
                $source->fetchArticles()
                    ->chunk(100)
                    ->each(function ($articles): void {
                        $savedArticles = $this->saveArticles($articles);
                        $this->notifyObservers($savedArticles);
                    });
            });
    }

    private function saveArticles(Collection $articles): Collection
    {
        return DB::transaction(function () use ($articles) {
            $existingCategories = $this->processCategories($articles);
            $existingAuthors = $this->processAuthors($articles);

            return $articles->map(function (ArticleData $articleData) use ($existingAuthors, $existingCategories) {
                $article = Article::query()->updateOrCreate(
                    ['url' => $articleData->url],
                    $articleData->except('author', 'category')->toArray()
                );

                // Sync authors if present
                if ( ! empty($articleData->author)) {
                    $authorIds = $this->getAuthorIds($articleData->author, $existingAuthors);
                    $article->authors()->sync($authorIds);
                }

                // Sync category if present
                if ($articleData->category && $category = $existingCategories->firstWhere(
                    'name',
                    $articleData->category
                )) {
                    $article->categories()->sync([$category->id]);
                }

                return $article->load('authors', 'categories');
            });
        });
    }

    private function processCategories(Collection $articles): Collection
    {
        $categoryNames = $articles
            ->pluck('category')
            ->map(fn ($category) => Str::title($category))
            ->filter()
            ->unique();
        $existingCategories = Category::query()->whereIn('name', $categoryNames)->get();

        $newCategories = $categoryNames
            ->diff($existingCategories->pluck('name'))
            ->map(fn ($name) => ['name' => $name]);

        if ($newCategories->isNotEmpty()) {
            Category::query()->insert($newCategories->map(fn ($category) => [...$category, ...$this->getTimeStamps()])->toArray());
            return Category::query()->whereIn('name', $categoryNames)->get();
        }

        return $existingCategories;
    }

    private function processAuthors(Collection $articles): Collection
    {
        $authorNames = $articles
            ->pluck('author')
            ->filter()
            ->flatMap(fn ($author) => collect(explode(',', $author)))
            ->map(fn ($name) => Str::squish(Str::title($name)))
            ->unique();

        $existingAuthors = Author::query()->whereIn('name', $authorNames)->get();

        $newAuthors = $authorNames
            ->diff($existingAuthors->pluck('name'))
            ->map(fn ($name) => ['name' => $name]);

        if ($newAuthors->isNotEmpty()) {
            Author::query()->insert($newAuthors->map(fn ($author) => [...$author, ...$this->getTimeStamps()])->toArray());
            return Author::query()->whereIn('name', $authorNames)->get();
        }

        return $existingAuthors;
    }

    private function getAuthorIds(string $authorString, Collection $existingAuthors): array
    {
        return collect(explode(',', $authorString))
            ->map(fn ($name) => mb_trim($name))
            ->map(fn ($name) => $existingAuthors->firstWhere('name', $name)?->id)
            ->filter()
            ->values()
            ->toArray();
    }

    private function getTimeStamps(): array
    {
        return [
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    private function notifyObservers(Collection $articles): void
    {
        $this->observers->each(function ($observer) use ($articles): void {
            $observer->onNewsUpdated($articles);
        });
    }
}
