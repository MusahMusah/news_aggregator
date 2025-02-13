<?php

declare(strict_types=1);

namespace App\Services\News;

use App\Interfaces\NewsSourceInterface;
use App\Models\Article;
use App\Services\News\Observers\NewsObserverInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class NewsAggregator
{
    private Collection $sources;
    private Collection $observers;
    private int $minimumSourcesRequired;

    public function __construct(int $minimumSourcesRequired = 1)
    {
        $this->sources = collect();
        $this->observers = collect();
        $this->minimumSourcesRequired = $minimumSourcesRequired;
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
        $successfulSources = 0;
        $articles = collect();

        foreach ($this->sources as $source) {
            try {
                $sourceArticles = collect($source->fetchArticles());

                if ($sourceArticles->isNotEmpty()) {
                    $successfulSources++;
                    $articles = $articles->concat(
                        (array) $sourceArticles->map(fn($article) => $this->saveArticle($article))
                    );
                }
            } catch (\Exception $e) {
                Log::error("Failed to fetch from source: {$source->getName()}", [
                    'error' => $e->getMessage()
                ]);
            }
        }

        if ($successfulSources < $this->minimumSourcesRequired) {
            Log::critical("Not enough news sources available. Required: {$this->minimumSourcesRequired}, Successful: {$successfulSources}");
            // You might want to send notifications to administrators here
        }

        if ($articles->isNotEmpty()) {
            $this->notifyObservers($articles);
        }
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