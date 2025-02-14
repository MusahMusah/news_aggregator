<?php

declare(strict_types=1);

namespace App\Actions;

use App\DataTransferObjects\ArticleData;
use App\Models\Article;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Pagination\CursorPaginator;
use Spatie\QueryBuilder\QueryBuilder;

final class GetArticlesAction
{
    public function __invoke(): array|Paginator|CursorPaginator
    {
        return ArticleData::collect(
            QueryBuilder::for(Article::class)
                ->allowedFilters([
                    'title',
                    'source',
                    'author',
                ])
                ->allowedSorts(['title'])
                ->paginate()
        )->appends(request()->query());
    }
}
