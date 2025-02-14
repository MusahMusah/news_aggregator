<?php

declare(strict_types=1);

namespace App\Interfaces;

interface RetryPolicyInterface
{
    public function execute(callable $operation);
}
