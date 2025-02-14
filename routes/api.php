<?php

declare(strict_types=1);

use App\Http\Controllers\Api\GetArticleController;
use App\Http\Controllers\Api\GetLatestArticleController;
use Illuminate\Support\Facades\Route;

Route::get('/articles', GetArticleController::class);
Route::get('/articles/latest', GetLatestArticleController::class);
