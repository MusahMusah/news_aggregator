<?php

declare(strict_types=1);

use App\Http\Controllers\Api\GetArticleController;
use Illuminate\Support\Facades\Route;

Route::get('/articles', GetArticleController::class);
