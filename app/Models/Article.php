<?php

declare(strict_types=1);

namespace App\Models;

use App\DataTransferObjects\ArticleData;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\LaravelData\WithData;

final class Article extends Model
{
    use HasFactory;
    use WithData;

    protected string $dataClass = ArticleData::class;

    protected $fillable = [
        'title',
        'description',
        'content',
        'url',
        'image',
        'source',
        'published_at',
    ];

    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class, 'article_authors')->withTimestamps();
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'article_categories')->withTimestamps();
    }

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }
}
