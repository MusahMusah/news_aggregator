<?php

declare(strict_types=1);

namespace App\Models;

use App\DataTransferObjects\ArticleData;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\WithData;

final class Article extends Model
{
    use WithData;

    protected string $dataClass = ArticleData::class;

    protected $fillable = [
        'title',
        'description',
        'content',
        'author',
        'url',
        'image',
        'source',
        'category',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }
}
