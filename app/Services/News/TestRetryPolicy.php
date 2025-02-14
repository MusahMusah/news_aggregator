<?php

declare(strict_types=1);

namespace App\Services\News;

use App\Interfaces\RetryPolicyInterface;
use Exception;

class TestRetryPolicy implements RetryPolicyInterface
{
    public array $delays = [];
    private array $backoffStrategy;
    private int $maxAttempts;

    public function __construct(array $backoffStrategy, int $maxAttempts)
    {
        $this->backoffStrategy = $backoffStrategy;
        $this->maxAttempts = $maxAttempts;
    }

    public function execute(callable $operation)
    {
        $attempts = 0;

        while ($attempts < $this->maxAttempts) {
            try {
                return $operation();
            } catch (Exception $e) {
                $attempts++;
                if ($attempts >= $this->maxAttempts || ! $this->shouldRetry($e)) {
                    throw $e;
                }

                $delayMs = $this->calculateDelay($attempts);
                $this->delays[] = $delayMs;
                // Simulate delay without actual sleep
            }
        }
    }

    private function shouldRetry(Exception $e): bool
    {
        return array_key_exists(get_class($e), $this->backoffStrategy);
    }

    private function calculateDelay(int $attempt): int
    {
        $exceptionClass = array_keys($this->backoffStrategy)[0];
        $baseDelay = $this->backoffStrategy[$exceptionClass]['delayMs'] ?? 100;
        $exponentialBackoff = $this->backoffStrategy[$exceptionClass]['exponentialBackoff'] ?? false;

        if ($exponentialBackoff) {
            return $baseDelay * (2 ** ($attempt - 1));
        }

        return $baseDelay;
    }
}
