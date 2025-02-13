<?php

declare(strict_types=1);

namespace App\Services\News;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Log;

class RetryPolicy
{
    private array $retryableExceptions;

    public function __construct(array $retryableExceptions = [])
    {
        $this->retryableExceptions = $retryableExceptions;
    }

    /**
     * Executes the given operation with retry logic.
     *
     * @param callable $operation The operation to execute.
     * @return mixed The result of the operation.
     * @throws \Exception If the operation fails after all retries.
     */
    public function execute(callable $operation): mixed
    {
        $attempts = 0;

        while (true) {
            try {
                return $operation();
            } catch (\Exception $e) {
                $exceptionClass = get_class($e);

                if (!isset($this->retryableExceptions[$exceptionClass])) {
                    throw $e;
                }

                $config = $this->retryableExceptions[$exceptionClass];
                $attempts++;

                $this->logException($e, $attempts);

                if ($attempts >= $config['maxAttempts']) {
                    throw $e;
                }

                $delayMs = $this->calculateDelay($attempts, $config);
                usleep($delayMs * 1000); // Convert milliseconds to microseconds
            }
        }
    }

    /**
     * Calculates the delay before the next retry attempt.
     *
     * @param int $attempt The current attempt number.
     * @param array $config The configuration for the exception type.
     * @return int The delay in milliseconds.
     */
    private function calculateDelay(int $attempt, array $config): int
    {
        if ($config['exponentialBackoff']) {
            // Exponential backoff: delayMs * 2^(attempt - 1)
            return $config['delayMs'] * (2 ** ($attempt - 1));
        }

        return $config['delayMs'];
    }

    /**
     * Logs the exception with the appropriate log level.
     *
     * @param \Exception $e The exception to log.
     * @param int $attempt The current attempt number.
     * @return void
     */
    private function logException(\Exception $e, int $attempt): void
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
