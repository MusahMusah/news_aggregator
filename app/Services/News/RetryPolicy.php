<?php

declare(strict_types=1);

namespace App\Services\News;

use App\Interfaces\RetryPolicyInterface;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

final class RetryPolicy implements RetryPolicyInterface
{
    private array $retryableExceptions;

    private int $maxAttempts;

    public function __construct(array $retryableExceptions = [], int $maxAttempts = 3)
    {
        $this->retryableExceptions = $retryableExceptions;
        $this->maxAttempts = $maxAttempts;
    }

    /**
     * @throws Exception
     */
    public function execute(callable $operation): mixed
    {
        $attempts = 0;

        while (true) {
            try {
                return $operation();
            } catch (Exception $e) {
                $exceptionClass = get_class($e);

                if ( ! isset($this->retryableExceptions[$exceptionClass])) {
                    throw $e;
                }

                $attempts++;
                $this->logException($e, $attempts);

                if ($attempts >= $this->maxAttempts) {
                    throw $e;
                }

                $delayMs = $this->calculateDelay($attempts, $this->retryableExceptions[$exceptionClass]);
                usleep($delayMs * 1000);
            }
        }
    }

    private function calculateDelay(int $attempt, array $config): int
    {
        if ($config['exponentialBackoff']) {
            return $config['delayMs'] * (2 ** ($attempt - 1));
        }

        return $config['delayMs'];
    }

    private function logException(Exception $e, int $attempt): void
    {
        $exceptionClass = get_class($e);
        $message = "Attempt {$attempt} failed with exception [{$exceptionClass}]: {$e->getMessage()}";

        if ($e instanceof ConnectionException) {
            Log::warning($message, ['exception' => $e]);
        } elseif ($e instanceof RequestException) {
            Log::error($message, ['exception' => $e]);
        } else {
            Log::info($message, ['exception' => $e]);
        }
    }
}
