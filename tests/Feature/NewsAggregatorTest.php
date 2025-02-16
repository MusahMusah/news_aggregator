<?php

declare(strict_types=1);

use App\DataTransferObjects\ArticleData;
use App\Interfaces\NewsSourceInterface;
use App\Models\Article;
use App\Services\News\NewsAggregator;
use App\Services\News\Observers\NewsObserverInterface;
use Illuminate\Support\Collection;

beforeEach(function (): void {
    $this->newsAggregator = new NewsAggregator();
});

// Source Management Tests
test('news aggregator starts with no sources', function (): void {
    $aggregator = new NewsAggregator();
    expect($aggregator)->toHaveProperty('sources')
        ->and($aggregator->sources)->toBeInstanceOf(Collection::class)
        ->and($aggregator->sources)->toBeEmpty();
});

test('can add news source to aggregator', function (): void {
    $source = Mockery::mock(NewsSourceInterface::class);
    $this->newsAggregator->addSource($source);
    expect($this->newsAggregator->sources)->toHaveCount(1)
        ->and($this->newsAggregator->sources->first())->toBe($source);
});

// Observer Management Tests
test('news aggregator starts with no observers', function (): void {
    expect($this->newsAggregator)->toHaveProperty('observers')
        ->and($this->newsAggregator->observers)->toBeInstanceOf(Collection::class)
        ->and($this->newsAggregator->observers)->toBeEmpty();
});

test('can add observer to aggregator', function (): void {
    $observer = Mockery::mock(NewsObserverInterface::class);
    $this->newsAggregator->addObserver($observer);
    expect($this->newsAggregator->observers)->toHaveCount(1)
        ->and($this->newsAggregator->observers->first())->toBe($observer);
});

// Article Fetching Tests
test('fetchNews calls fetchArticles on all sources', function (): void {
    // Create mock sources
    $source1 = Mockery::mock(NewsSourceInterface::class);
    $source1->shouldReceive('fetchArticles')
        ->once()
        ->andReturn(collect([]));

    $source2 = Mockery::mock(NewsSourceInterface::class);
    $source2->shouldReceive('fetchArticles')
        ->once()
        ->andReturn(collect([]));

    $this->newsAggregator->addSource($source1);
    $this->newsAggregator->addSource($source2);

    $this->newsAggregator->fetchNews();
});

// Article Saving Tests
test('saves new article with single author', function (): void {
    $articleData = ArticleData::from([
        'title' => 'Test Article',
        'url' => 'https://example.com/test',
        'content' => 'Test content',
        'source' => '',
        'published_at' => now(),
        'author' => 'John Doe'
    ]);

    $source = Mockery::mock(NewsSourceInterface::class);
    $source->shouldReceive('fetchArticles')
        ->once()
        ->andReturn(collect([$articleData]));

    $this->newsAggregator->addSource($source);
    $this->newsAggregator->fetchNews();

    $this->assertDatabaseHas('articles', [
        'title' => 'Test Article',
        'url' => 'https://example.com/test'
    ]);

    $this->assertDatabaseHas('authors', [
        'name' => 'John Doe'
    ]);
});

test('saves article with multiple authors', function (): void {
    $articleData = ArticleData::from([
        'title' => 'Test Article',
        'url' => 'https://example.com/test',
        'content' => 'Test content',
        'source' => '',
        'published_at' => now(),
        'author' => 'John Doe, Jane Smith'
    ]);

    $source = Mockery::mock(NewsSourceInterface::class);
    $source->shouldReceive('fetchArticles')
        ->once()
        ->andReturn(collect([$articleData]));

    $this->newsAggregator->addSource($source);
    $this->newsAggregator->fetchNews();

    $article = Article::where('url', 'https://example.com/test')->first();
    expect($article->authors)->toHaveCount(2)
        ->and($article->authors->pluck('name')->toArray())->toContain('John Doe', 'Jane Smith');
});

test('updates existing article without creating duplicate', function (): void {
    // Create initial article data
    $initialData = ArticleData::from([
        'title' => 'Original Title',
        'url' => 'https://example.com/test',
        'source' => '',
        'content' => 'Original content',
        'published_at' => now(),
        'author' => 'John Doe'
    ]);

    // Updated version of the same article
    $updatedData = ArticleData::from([
        'title' => 'Updated Title',
        'url' => 'https://example.com/test',
        'content' => 'Updated content',
        'source' => '',
        'published_at' => now(),
        'author' => 'John Doe'
    ]);

    // Mock the first news source
    $source1 = Mockery::mock(NewsSourceInterface::class);
    $source1->shouldReceive('fetchArticles')
        ->once()
        ->andReturn(collect([$initialData]));

    // Add the first source and fetch news
    $this->newsAggregator->addSource($source1);
    $this->newsAggregator->fetchNews();

    // Mock the second news source
    $source2 = Mockery::mock(NewsSourceInterface::class);
    $source2->shouldReceive('fetchArticles')
        ->once()
        ->andReturn(collect([$updatedData]));

    // Reset the news aggregator to avoid re-fetching from the first source
    $this->newsAggregator = new NewsAggregator();

    // Add the second source and fetch news
    $this->newsAggregator->addSource($source2);
    $this->newsAggregator->fetchNews();

    // Assert that there is only one article with the given URL
    $articles = Article::where('url', 'https://example.com/test')->get();
    expect($articles)->toHaveCount(1)
        ->and($articles->first()->title)->toBe('Updated Title');
});


// Edge Cases
test('handles article with no author', function (): void {
    $articleData = ArticleData::from([
        'title' => 'Test Article',
        'url' => 'https://example.com/test',
        'content' => 'Test content',
        'source' => '',
        'published_at' => now(),
        'author' => ''
    ]);

    $source = Mockery::mock(NewsSourceInterface::class);
    $source->shouldReceive('fetchArticles')
        ->once()
        ->andReturn(collect([$articleData]));

    $this->newsAggregator->addSource($source);
    $this->newsAggregator->fetchNews();

    $article = Article::where('url', 'https://example.com/test')->first();

    expect($article)->not->toBeNull()
        ->and($article->authors)->toBeEmpty();
});

test('notifies all observers when news is updated', function (): void {
    $articleData = ArticleData::from([
        'title' => 'Test Article',
        'url' => 'https://example.com/test',
        'source' => '',
        'content' => 'Test content',
        'published_at' => now()
    ]);

    $source = Mockery::mock(NewsSourceInterface::class);
    $source->shouldReceive('fetchArticles')
        ->once()
        ->andReturn(collect([$articleData]));

    $observer1 = Mockery::mock(NewsObserverInterface::class);
    $observer1->shouldReceive('onNewsUpdated')
        ->once()
        ->with(Mockery::type(Collection::class));

    $observer2 = Mockery::mock(NewsObserverInterface::class);
    $observer2->shouldReceive('onNewsUpdated')
        ->once()
        ->with(Mockery::type(Collection::class));

    $this->newsAggregator
        ->addSource($source)
        ->addObserver($observer1)
        ->addObserver($observer2);

    $this->newsAggregator->fetchNews();
});

test('handles empty response from news sources', function (): void {
    $source = Mockery::mock(NewsSourceInterface::class);
    $source->shouldReceive('fetchArticles')
        ->once()
        ->andReturn(collect([]));

    $observer = Mockery::mock(NewsObserverInterface::class);
    $observer->shouldReceive('onNewsUpdated')
        ->never()
        ->with(Mockery::on(fn ($articles) => $articles->isEmpty()));

    $this->newsAggregator
        ->addSource($source)
        ->addObserver($observer);

    $this->newsAggregator->fetchNews();
});

test('handles malformed author strings', function (): void {
    $articleData = ArticleData::from([
        'title' => 'Test Article',
        'url' => 'https://example.com/test',
        'content' => 'Test content',
        'source' => '',
        'published_at' => now(),
        'author' => '  John Doe  ,  Jane Smith ' // Extra spaces and trailing comma
    ]);

    $source = Mockery::mock(NewsSourceInterface::class);
    $source->shouldReceive('fetchArticles')
        ->once()
        ->andReturn(collect([$articleData]));

    $this->newsAggregator->addSource($source);
    $this->newsAggregator->fetchNews();

    $article = Article::where('url', 'https://example.com/test')->first();
    expect($article->authors)->toHaveCount(2)
        ->and($article->authors->pluck('name')->toArray())->toContain('John Doe', 'Jane Smith');
});

afterEach(function (): void {
    Mockery::close();
});
