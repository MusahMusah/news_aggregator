<?php

declare(strict_types=1);

namespace App\Services\News;

use App\Enums\CircuitState;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class CircuitBreaker
{
    private string $serviceName;
    private int $failureThreshold;
    private int $resetTimeout;

    public function __construct(string $serviceName, int $failureThreshold = 3, int $resetTimeout = 60)
    {
        $this->serviceName = $serviceName;
        $this->failureThreshold = $failureThreshold;
        $this->resetTimeout = $resetTimeout;
    }

    public function isAvailable(): bool
    {
        $state = $this->getState();

        if ($state['status'] === CircuitState::OPEN) {
            if (Carbon::now()->timestamp - $state['lastChecked'] > $this->resetTimeout) {
                $this->setHalfOpen();
                return true;
            }
            return false;
        }

        return true;
    }

    public function recordSuccess(): void
    {
        Cache::put($this->getStateKey(), [
            'status' => CircuitState::CLOSED,
            'failures' => 0,
            'lastChecked' => Carbon::now()->timestamp
        ]);
    }

    public function recordFailure(): void
    {
        $state = $this->getState();
        $failures = $state['failures'] + 1;

        if ($failures >= $this->failureThreshold) {
            $this->setOpen();
        } else {
            Cache::put($this->getStateKey(), [
                'status' => $state['status'],
                'failures' => $failures,
                'lastChecked' => Carbon::now()->timestamp
            ]);
        }
    }

    private function setOpen(): void
    {
        Cache::put($this->getStateKey(), [
            'status' => CircuitState::OPEN,
            'failures' => $this->failureThreshold,
            'lastChecked' => Carbon::now()->timestamp
        ]);
    }

    private function setHalfOpen(): void
    {
        Cache::put($this->getStateKey(), [
            'status' => CircuitState::HALF_OPEN,
            'failures' => 0,
            'lastChecked' => Carbon::now()->timestamp
        ]);
    }

    private function getState(): array
    {
        return Cache::get($this->getStateKey(), [
            'status' => CircuitState::CLOSED,
            'failures' => 0,
            'lastChecked' => Carbon::now()->timestamp
        ]);
    }

    private function getStateKey(): string
    {
        return "circuit_breaker_{$this->serviceName}";
    }
}