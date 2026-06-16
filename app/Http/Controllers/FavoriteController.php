<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Favorite;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function index(): View
    {
        $books = auth()->user()
            ->favoriteBooks()
            ->paginate(10);

        return view('favorites.index', compact('books'));
    }

    public function toggle(Book $book): RedirectResponse
    {
        $favorite = Favorite::where('user_id', auth()->id())
            ->where('book_id', $book->id)
            ->first();

        if ($favorite) {
            $favorite->delete();

            return redirect()->route('books.show', $book);
        }

        Favorite::create([
            'user_id' => auth()->id(),
            'book_id' => $book->id,
        ]);

        return redirect()->route('books.show', $book);
    }
}
