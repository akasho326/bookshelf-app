<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookRequest;
use App\Http\Requests\UpdateBookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class BookController extends Controller
{
    /**
     * 書籍一覧を検索・絞り込み・並び替えて表示する。
     */
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

        // 並び順を適用
        match ($request->input('sort', 'newest')) {
            'oldest' => $query->oldest(),
            'title' => $query->orderBy('title'),
            'rating' => $query->orderByDesc('reviews_avg_rating'),
            default => $query->latest(),
        };

        $books = $query->paginate(10)->withQueryString();

        return view('books.index', compact('books', 'genres'));
    }

    /**
     * 書籍登録画面を表示する。
     */
    public function create(): View
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    /**
     * 書籍を登録し、ジャンルを関連付ける。
     */
    public function store(StoreBookRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['user_id'] = auth()->id();

        // ジャンル情報を分離
        $genreIds = $data['genres'];
        unset($data['genres']);

        // 書籍とジャンルをトランザクションで登録
        $book = DB::transaction(function () use ($data, $genreIds) {
            $book = Book::create($data);

            $book->genres()->attach($genreIds);

            return $book;
        });

        return redirect()->route('books.show', $book)
            ->with('success', '書籍を登録しました。');
    }

    /**
     * 書籍の詳細情報を表示する。
     */
    public function show(Book $book): View
    {
        $book->load(['genres', 'reviews.user']);

        return view('books.show', compact('book'));
    }

    /**
     * 書籍編集画面を表示する。
     */
    public function edit(Book $book): View
    {
        $this->authorize('update', $book);

        $genres = Genre::all();

        return view('books.edit', compact('book', 'genres'));
    }

    /**
     * 書籍情報を更新し、ジャンルとの関連を同期する。
     */
    public function update(UpdateBookRequest $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);

        $data = $request->validated();

        $genreIds = $data['genres'];
        unset($data['genres']);

        DB::transaction(function () use ($book, $data, $genreIds) {
            $book->update($data);

            $book->genres()->sync($genreIds);
        });

        return redirect()->route('books.show', $book)
            ->with('success', '書籍を更新しました。');
    }

    /**
     * 書籍を削除する。
     */
    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return redirect()->route('books.index')
            ->with('success', '書籍を削除しました。');
    }

    /**
     * ISBNを使用してGoogle Books APIから書籍情報を取得する。
     */
    public function searchByIsbn(string $isbn): JsonResponse
    {
        // ISBNの形式をチェック
        if (! preg_match('/^\d{13}$/', $isbn)) {
            return response()->json([
                'error' => 'ISBNは13桁の数字で入力してください。',
            ], 422);
        }

        // Google Books APIから書籍情報を取得
        $response = Http::get('https://www.googleapis.com/books/v1/volumes', [
            'q' => 'isbn:'.$isbn,
            'key' => config('services.google.books_api_key'),
        ]);

        // API利用上限エラー
        if ($response->status() === 429) {
            return response()->json([
                'error' => 'Google Books APIの利用上限に達しました。時間をおいて再度お試しください。',
            ], 429);
        }

        // API通信エラー
        if ($response->failed()) {
            return response()->json([
                'error' => '書籍情報の取得に失敗しました。',
            ], 500);
        }

        // 検索結果を取得
        $items = $response->json('items');

        // 該当する書籍が存在しない場合
        if (empty($items)) {
            return response()->json([
                'error' => '該当する書籍が見つかりませんでした。',
            ], 404);
        }

        // 書籍情報を取得
        $volumeInfo = $items[0]['volumeInfo'] ?? [];

        // 出版日が yyyy-mm-dd 形式か確認
        $publishedDate = $volumeInfo['publishedDate'] ?? null;

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $publishedDate)) {
            $publishedDate = null;
        }

        return response()->json([
            'title' => $volumeInfo['title'] ?? '',
            'author' => implode(', ', $volumeInfo['authors'] ?? []),
            'isbn' => $isbn,
            'published_date' => $publishedDate,
            'description' => $volumeInfo['description'] ?? '',
            'image_url' => $volumeInfo['imageLinks']['thumbnail'] ?? '',
        ]);
    }
}
