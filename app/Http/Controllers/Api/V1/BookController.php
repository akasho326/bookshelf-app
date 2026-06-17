<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        if ($request->filled('keyword')) {
            $keyword = $request->keyword;

            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('author', 'like', "%{$keyword}%");
            });
        }

        if ($request->filled('genre_id')) {
            $query->whereHas('genres', function ($q) use ($request) {
                $q->where('genres.id', $request->genre_id);
            });
        }

        $perPage = $request->input('per_page', 20);

        $books = $query->latest()
            ->paginate($perPage);

        return BookResource::collection($books);
    }

    public function show(Book $book): BookResource
    {
        $book->load(['genres', 'reviews.user'])
            ->loadAvg('reviews', 'rating')
            ->loadCount('reviews');

        return new BookResource($book);
    }

    public function store(StoreBookRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['user_id'] = 1;
        $genreIds = $data['genres'];
        unset($data['genres']);

        $book = Book::create($data);

        $book->genres()->attach($genreIds);

        $book->load('genres');

        return (new BookResource($book))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateBookRequest $request, Book $book): BookResource
    {
        $data = $request->validated();

        $genreIds = $data['genres'];
        unset($data['genres']);

        $book->update($data);

        $book->genres()->sync($genreIds);

        $book->load('genres');

        return new BookResource($book);
    }

    public function destroy(Book $book): JsonResponse
    {
        $book->delete();

        return response()->json(null, 204);
    }
}
