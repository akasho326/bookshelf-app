<?php

use App\Http\Controllers\BookController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Route::resource('books', BookController::class)
//     ->only(['index', 'show']);

// Route::middleware('auth')->group(function () {
//     Route::resource('books', BookController::class)
//         ->except(['index', 'show']);
// });

// 認証不要のルート
Route::get('books', [BookController::class, 'index'])->name('books.index');
Route::get('books/{book}', [BookController::class, 'show'])->name('books.show');

// 認証が必要なルート
Route::middleware('auth')->group(function () {
    Route::get('books/create', [BookController::class, 'create'])->name('books.create');
    Route::post('books', [BookController::class, 'store'])->name('books.store');
    Route::get('books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');
    Route::put('books/{book}', [BookController::class, 'update'])->name('books.update');
    Route::delete('books/{book}', [BookController::class, 'destroy'])->name('books.destroy');
    Route::post('favorites/{book}/toggle', function () {
        return back();
    })->name('favorites.toggle');
    Route::post('reviews/store', function () {
        return back();
    })->name('reviews.store');
    Route::post('reviews/like', function () {
        return back();
    })->name('reviews.like');
});
