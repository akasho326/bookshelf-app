<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Favorite;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    /**
     * お気に入り一覧を表示する。
     */
    public function index(): View
    {
        $books = auth()->user()
            ->favoriteBooks()
            ->paginate(10);

        return view('favorites.index', compact('books'));
    }

    /**
     * 書籍のお気に入り登録を切り替える。
     */
    public function toggle(Book $book): RedirectResponse
    {
        // 既にお気に入り登録しているか確認
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
