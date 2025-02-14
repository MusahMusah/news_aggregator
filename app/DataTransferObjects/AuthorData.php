<?php

declare(strict_types=1);

namespace App\DataTransferObjects;

use Spatie\LaravelData\Data;

final class AuthorData extends Data
{
    public function __construct(
        readonly public ?int $id,
        readonly public string $name,
    ) {
    }
}
