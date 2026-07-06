<?php

use App\Http\Controllers\Api\V1\BookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // 誰でも利用できる公開API
    Route::apiResource('books', BookController::class)
        ->only(['index', 'show']);

    // 書き込み系API（Sanctum認証必須）
    Route::middleware('auth:sanctum')->group(function () {
        Route::apiResource('books', BookController::class)
            ->only(['store', 'update', 'destroy']);
    });
});
