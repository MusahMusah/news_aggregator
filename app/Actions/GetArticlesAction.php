<?php

declare(strict_types=1);

namespace App\Actions;

use App\DataTransferObjects\ArticleData;
use App\Models\Article;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\CursorPaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

final class GetArticlesAction
{
    public function __invoke(): array|Paginator|CursorPaginator
    {
        $cacheKey = $this->generateCacheKey(Request::query());

        return Cache::flexible($cacheKey, [300, 600], function () {
            $query = QueryBuilder::for(Article::class)
                ->allowedFilters([
                    AllowedFilter::partial('title'),
                    AllowedFilter::exact('source'),
                    AllowedFilter::partial('authors.name'),
                    AllowedFilter::exact('published_at'),
                ])
                ->allowedIncludes('authors')
                ->allowedSorts(['title', 'authors.name', 'source', 'published_at']);

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
