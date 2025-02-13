<?php

declare(strict_types=1);

namespace App\Services\News\Sources;

use App\Interfaces\NewsSourceInterface;
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
    protected RetryPolicy $retryPolicy;

    public function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->retryPolicy = new RetryPolicy(
            retryableExceptions: [
                ConnectionException::class => [
                    'delayMs' => 2000,     // Longer delay between retries
                    'exponentialBackoff' => true
                ],
                RequestException::class => [
                    'delayMs' => 1000,
                    'exponentialBackoff' => false
                ]
            ]
        );
    }

    protected function fetch(string $endpoint, array $params = []): array
    {
        if (!$this->withinRateLimit()) {
            Log::warning("Rate limit exceeded for {$this->getName()}");
            return [];
        }

        try {
            return $this->retryPolicy->execute(function () use ($endpoint, $params) {
                $response = Http::get($this->baseUrl . $endpoint, $params);

                if (!$response->successful()) {
                    throw new RequestException($response);
                }

                return $response->json();
            });
        } catch (\Exception $e) {
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

        $maxRequests = $rateLimitConfig['max_requests'] ?? 10;
        $perMinutes = $rateLimitConfig['per_minutes'] ?? 1;

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