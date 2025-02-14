<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final readonly class ApiSuccessResponse implements Responsable
{
    public function __construct(
        public mixed $data,
        public string $message,
        public array $metadata = [],
        private int $status = Response::HTTP_OK,
        private array $headers = []
    ) {
    }

    public function toResponse($request): JsonResponse|Response
    {
        return response()->json(
            data: [
                'success' => true,
                'message' => $this->message,
                'data' => $this->data,
                ...($this->metadata ? ['meta' => $this->metadata] : []),
            ],
            status: $this->status,
            headers: $this->headers
        );
    }
}
