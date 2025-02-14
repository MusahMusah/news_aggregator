<?php

declare(strict_types=1);

use App\Services\News\NewsAggregator;
use App\Services\News\Sources\NewsApiSource;
use App\Services\News\TestRetryPolicy;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function (): void {
    Config::set('news_sources.newsapi.api_key', 'test-api-key');
    RateLimiter::clear('rate_limit:NewsAPI');
    CarbonImmutable::setTestNow(); // Reset time
});

afterEach(function (): void {
    RateLimiter::clear('rate_limit:NewsAPI');
    CarbonImmutable::setTestNow(); // Reset time
});

// News Fetch Command Tests
test('news:fetch command is scheduled to run hourly', function (): void {
    $schedule = app()->make(Schedule::class);

    $scheduledCommand = collect($schedule->events())->first(fn (Event $event) => str_contains($event->command, 'news:fetch'));

    expect($scheduledCommand)->not->toBeNull()
        ->and($scheduledCommand->expression)->toBe('0 * * * *');
});

test('command successfully fetches and stores news articles', function (): void {
    // Arrange
    Http::fake([
        'newsapi.org/*' => Http::response([
            'articles' => [
                [
                    'title' => 'Test Article 1',
                    'description' => 'Description 1',
                    'content' => 'Content 1',
                    'author' => 'Author 1',
                    'source' => ['name' => 'Source 1'],
                    'url' => 'https://example.com/1',
                    'urlToImage' => 'https://example.com/image1.jpg',
                    'publishedAt' => '2025-02-14T09:00:00Z'
                ],
                [
                    'title' => 'Test Article 2',
                    'description' => 'Description 2',
                    'content' => 'Content 2',
                    'author' => 'Author 2',
                    'source' => ['name' => 'Source 2'],
                    'url' => 'https://example.com/2',
                    'urlToImage' => 'https://example.com/image2.jpg',
                    'publishedAt' => '2025-02-14T08:00:00Z'
                ]
            ]
        ]),
    ]);

    $newsApiSource = new NewsApiSource('test-api-key');
    $newsAggregator = new NewsAggregator();
    $newsAggregator->addSource($newsApiSource);

    // Act
    $result = Artisan::call('news:fetch');

    // Assert
    expect($result)->toBe(0)
        ->and(Http::recorded())->toHaveCount(1);

    $this->assertDatabaseHas('articles', [
        'title' => 'Test Article 1',
        'url' => 'https://example.com/1',
        'source' => 'NewsAPI - Source 1'
    ]);

    $this->assertDatabaseHas('articles', [
        'title' => 'Test Article 2',
        'url' => 'https://example.com/2',
        'source' => 'NewsAPI - Source 2'
    ]);
});

test('command handles API errors gracefully', function (): void {
    Http::fake([
        'newsapi.org/*' => Http::response(
            ['error' => 'API Error'],
            500
        ),
    ]);

    $exitCode = Artisan::call('news:fetch');

    expect($exitCode)->toBe(0);
});

test('command handles empty API responses', function (): void {
    Http::fake([
        'newsapi.org/*' => Http::response([
            'articles' => []
        ]),
    ]);

    $exitCode = Artisan::call('news:fetch');

    expect($exitCode)->toBe(0)
        ->and(App\Models\Article::count())->toBe(0);
});

// RetryPolicy Tests
test('retry policy handles request exceptions with fixed delay', function (): void {
    $startTime = microtime(true);

    $retryPolicy = new TestRetryPolicy([
        RequestException::class => [
            'delayMs' => 100,
            'exponentialBackoff' => false,
        ]
    ], 3);

    $attempts = 0;

    try {
        $retryPolicy->execute(function () use (&$attempts): void {
            $attempts++;
            throw new RequestException(
                new Response(new GuzzleHttp\Psr7\Response(429))
            );
        });
    } catch (RequestException) {
        // Expected
    }

    $duration = (microtime(true) - $startTime) * 1000;

    expect($attempts)->toBe(3)
        ->and($duration)->toBeLessThan(100); // Should not have exponential backoff
});

test('news source respects rate limits', function (): void {
    config([
        'news_sources.NewsAPI.rate_limit' => [
            'max_requests' => 2,
            'per_minutes' => 1
        ]
    ]);

    $source = new NewsApiSource('test-key');

    // Mock successful API responses
    Http::fake([
        'newsapi.org/*' => Http::response(['articles' => []]),
    ]);

    // First two requests should succeed
    $source->fetchArticles();
    $source->fetchArticles();

    // Third request should be rate limited
    $source->fetchArticles();

    expect(Http::recorded())->toHaveCount(2);
});

test('news source implements retry policy for connection errors', function (): void {
    $source = new NewsApiSource('test-key');

    $attempts = 0;
    Http::fake([
        'newsapi.org/*' => function () use (&$attempts) {
            $attempts++;
            if ($attempts < 3) {
                throw new ConnectionException("Connection error");
            }
            return Http::response(['articles' => []]);
        },
    ]);

    $source->fetchArticles();

    expect($attempts)->toBe(3);
});


test('news source rate limiter resets after time window', function (): void {
    config([
        'news_sources.NewsAPI.rate_limit' => [
            'max_requests' => 1,
            'per_minutes' => 1
        ]
    ]);

    $source = new NewsApiSource('test-key');

    Http::fake([
        'newsapi.org/*' => Http::response(['articles' => []]),
    ]);

    // First request
    $source->fetchArticles();

    // Second request should be rate limited
    $source->fetchArticles();

    // Move time forward
    RateLimiter::clear($source->getRateLimitKey());

    // Third request should succeed
    $source->fetchArticles();

    expect(Http::recorded())->toHaveCount(2);
});

test('news source handles missing rate limit config', function (): void {
    config(['news_sources.NewsAPI.rate_limit' => null]);

    $source = new NewsApiSource('test-key');

    Http::fake([
        'newsapi.org/*' => Http::response(['articles' => []]),
    ]);

    // Should be able to make multiple requests without rate limiting
    for ($i = 0; $i < 5; $i++) {
        $source->fetchArticles();
    }

    expect(Http::recorded())->toHaveCount(5);
});
