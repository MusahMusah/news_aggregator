<?php

declare(strict_types=1);

namespace App\Http\Responses;

use Exception;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final readonly class ApiErrorResponse implements Responsable
{
    public function __construct(
        private string $message,
        private array $errors = [],
        private null|Throwable|Exception $exception = null,
        private int $code = Response::HTTP_INTERNAL_SERVER_ERROR,
        private array $headers = []
    ) {
    }

    public function toResponse($request): JsonResponse|Response
    {
        $response = [
            'success' => false,
            'message' => $this->message,
            ...(count($this->errors) > 0 ? ['errors' => $this->errors] : []),
            'statusCode' => $this->code,
        ];

        if (null !== $this->exception && config('app.debug') && ! $this->errors) {
            $response['debug'] = [
                'message' => $this->exception->getMessage(),
                'file' => $this->exception->getFile(),
                'line' => $this->exception->getLine(),
                'code' => $this->exception->getCode(),
                'trace' => $this->exception->getTraceAsString(),
            ];
        }

        return response()->json(
            data: $response,
            status: $this->code > 0 ? $this->code : 500,
            headers: $this->headers
        );
    }
}
