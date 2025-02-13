<?php

declare(strict_types=1);

namespace App\Services\News\Sources;

use App\Interfaces\NewsSourceInterface;
use App\Services\News\CircuitBreaker;
use App\Services\News\RetryPolicy;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

abstract class AbstractNewsSource implements NewsSourceInterface
{
    protected string $apiKey;
    protected string $baseUrl;
    protected CircuitBreaker $circuitBreaker;
    protected RetryPolicy $retryPolicy;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->circuitBreaker = new CircuitBreaker($this->getName());
        $this->retryPolicy = new RetryPolicy(
            retryableExceptions: [
                ConnectionException::class => [
                    'maxAttempts' => 5,    // More retries for connection issues
                    'delayMs' => 2000,     // Longer delay between retries
                    'exponentialBackoff' => true
                ],
                RequestException::class => [
                    'maxAttempts' => 3,    // Fewer retries for request issues
                    'delayMs' => 1000,
                    'exponentialBackoff' => false
                ]
            ]
        );
    }

    protected function fetch(string $endpoint, array $params = []): array
    {
        if (!$this->circuitBreaker->isAvailable()) {
            Log::warning("Circuit breaker is open for {$this->getName()}");
            return [];
        }

        if (!$this->withinRateLimit()) {
            Log::warning("Rate limit exceeded for {$this->getName()}");
            return [];
        }

        try {
            $result = $this->retryPolicy->execute(function () use ($endpoint, $params) {
                $response = Http::timeout(5)
                    ->get($this->baseUrl . $endpoint, $params);

                if (!$response->successful()) {
                    throw new RequestException($response);
                }

                return $response->json();
            });

            $this->circuitBreaker->recordSuccess();
            return $result;

        } catch (\Exception $e) {
            $this->circuitBreaker->recordFailure();

            Log::error("Failed to fetch from {$this->getName()}", [
                'error' => $e->getMessage(),
                'endpoint' => $endpoint
            ]);

            return [];
        }
    }

    protected function withinRateLimit(): bool
    {
        $rateLimitKey = $this->getRateLimitKey();
        $rateLimitConfig = config("news_sources.{$this->getName()}.rate_limit");

        if (is_null($rateLimitConfig)) {
            // If no rate limit config is found, allow the request
            return true;
        }

        $maxRequests = $rateLimitConfig['max_requests'];
        $perMinutes = $rateLimitConfig['per_minutes'];

        if (RateLimiter::tooManyAttempts($rateLimitKey, $maxRequests)) {
            Log::warning("Rate limit exceeded for {$this->getName()}");
            return false;
        }

        RateLimiter::hit($rateLimitKey, $perMinutes * 60);
        return true;
    }

    protected function getRateLimitKey(): string
    {
        return "rate_limit:{$this->getName()}";
    }

    abstract public function getName(): string;
}