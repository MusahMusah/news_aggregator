<?php

namespace App\Models;

use App\DataTransferObjects\ArticleData;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected string $dataClass = ArticleData::class;
}
