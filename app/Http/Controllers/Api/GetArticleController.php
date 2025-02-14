<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\GetArticlesAction;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiSuccessResponse;

final class GetArticleController extends Controller
{
    public function __invoke(GetArticlesAction $action): ApiSuccessResponse
    {
        return new ApiSuccessResponse(
            data: $action(),
            message: 'Articles retrieved successfully.'
        );
    }
}
