<?php

namespace App\Models;

use App\DataTransferObjects\ArticleData;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
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
