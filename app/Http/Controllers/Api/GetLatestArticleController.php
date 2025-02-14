<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\DataTransferObjects\ArticleData;
use App\Http\Controllers\Controller;
use App\Http\Responses\ApiSuccessResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GetLatestArticleController extends Controller
{
    public function __invoke(Request $request): ApiSuccessResponse
    {
        return new ApiSuccessResponse(
            data: ArticleData::collect(Cache::get('latest_articles')),
            message: "Latest articles retrieved."
        );
    }
}
