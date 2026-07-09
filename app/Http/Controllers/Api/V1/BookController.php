<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreBookRequest;
use App\Http\Requests\Api\UpdateBookRequest;
use App\Http\Resources\BookResource;
use App\Models\Book;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

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

        $data['user_id'] = $request->user()->id;

        $genreIds = $data['genres'];
        unset($data['genres']);

        $book = DB::transaction(function () use ($data, $genreIds) {
            $book = Book::create($data);

            $book->genres()->attach($genreIds);

            return $book;
        });

        $book->load('genres');

        return (new BookResource($book))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateBookRequest $request, Book $book): BookResource
    {
        $this->authorize('update', $book);

        $data = $request->validated();

        $genreIds = $data['genres'];
        unset($data['genres']);

        DB::transaction(function () use ($book, $data, $genreIds) {
            $book->update($data);

            $book->genres()->sync($genreIds);
        });

        $book->load('genres');

        return new BookResource($book);
    }

    public function destroy(Book $book): JsonResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return response()->json(null, 204);
    }
}
