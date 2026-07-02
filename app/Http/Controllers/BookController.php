<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookController extends Controller
{
    public function index(Request $request): View
    {
        $genres = Genre::all();

        $query = Book::with('genres')
            ->withAvg('reviews', 'rating');

        if ($request->filled('keyword')) {
            $query->where(function ($query) use ($request) {
                $query->where('title', 'like', '%'.$request->keyword.'%')
                    ->orWhere('author', 'like', '%'.$request->keyword.'%');
            });
        }

        if ($request->filled('genre')) {
            $query->whereHas('genres', function ($query) use ($request) {
                $query->where('genres.id', $request->genre);
            });
        }

        match ($request->input('sort', 'newest')) {
            'oldest' => $query->oldest(),
            'title' => $query->orderBy('title'),
            'rating' => $query->orderByDesc('reviews_avg_rating'),
            default => $query->latest(),
        };

        $books = $query->paginate(10)->withQueryString();

        return view('books.index', compact('books', 'genres'));
    }

    public function create(): View
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    public function store(StoreBookRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();
        $genreIds = $data['genres'];
        unset($data['genres']);

        $book = Book::create($data);

        $book->genres()->attach($genreIds);

        return redirect()->route('books.show', $book)
            ->with('success', '書籍を登録しました。');
    }

    public function show(Book $book): View
    {
        $book->load(['genres', 'reviews.user']);

        return view('books.show', compact('book'));
    }

    public function edit(Book $book): View
    {
        $this->authorize('update', $book);

        $genres = Genre::all();

        return view('books.edit', compact('book', 'genres'));
    }

    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);

        $data = $request->validated();

        $genreIds = $data['genres'];
        unset($data['genres']);

        $book->update($data);

        $book->genres()->sync($genreIds);

        return redirect()->route('books.show', $book)
            ->with('success', '書籍を更新しました。');
    }

    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()->route('books.index')
            ->with('success', '書籍を削除しました。');
    }
}
