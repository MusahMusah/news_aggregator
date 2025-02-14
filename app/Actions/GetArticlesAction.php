<?php

declare(strict_types=1);

namespace App\Actions;

use App\DataTransferObjects\ArticleData;
use App\Models\Article;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use Spatie\QueryBuilder\QueryBuilder;

final class GetArticlesAction
{
    public function __invoke(): array|Paginator|CursorPaginator
    {
        $cacheKey = $this->generateCacheKey(Request::query());

        $freshFor = 300; // 5 minutes
        $staleFor = 600; // Additional 10 minutes

        return Cache::flexible($cacheKey, [$freshFor, $staleFor], function () {
            $query = QueryBuilder::for(Article::class)
                ->allowedFilters(['title', 'source', 'author'])
                ->allowedSorts(['title']);

            $paginator = match (Request::has('cursor')) {
                true => $query->cursorPaginate(),
                default => $query->paginate()
            };

            return ArticleData::collect($paginator->appends(Request::query()));
        });
    }

    private function generateCacheKey(array $params): string
    {
        ksort($params);

        return 'articles_page_' . md5(json_encode($params));
    }
}
