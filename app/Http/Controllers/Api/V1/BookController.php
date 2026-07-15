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
    /**
     * 書籍一覧を検索・絞り込みして取得する。
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        // ジャンル・平均評価・レビュー件数を含む取得クエリを作成
        $query = Book::with('genres')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews');

        // キーワードでタイトルまたは著者名を検索
        if ($request->filled('keyword')) {
            $keyword = $request->keyword;

            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'like', "%{$keyword}%")
                    ->orWhere('author', 'like', "%{$keyword}%");
            });
        }

        // ジャンルIDで書籍を絞り込む
        if ($request->filled('genre_id')) {
            $query->whereHas('genres', function ($q) use ($request) {
                $q->where('genres.id', $request->genre_id);
            });
        }

        // 1ページあたりの表示件数を取得
        $perPage = $request->input('per_page', 20);

        // 新しい書籍順でページネーションして取得
        $books = $query->latest()
            ->paginate($perPage);

        // 書籍一覧をResource形式で返却
        return BookResource::collection($books);
    }

    /**
     * 書籍の詳細情報を取得する。
     */
    public function show(Book $book): BookResource
    {
        $book->load(['genres', 'reviews.user'])
            ->loadAvg('reviews', 'rating')
            ->loadCount('reviews');

        return new BookResource($book);
    }

    /**
     * 書籍を登録し、ジャンルを関連付ける。
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $data = $request->validated();

        $data['user_id'] = $request->user()->id;

        $genreIds = $data['genres'];
        unset($data['genres']);

        // 書籍とジャンルをトランザクションで登録
        $book = DB::transaction(function () use ($data, $genreIds) {
            $book = Book::create($data);

            $book->genres()->attach($genreIds);

            return $book;
        });

        // レスポンスに含めるジャンル情報を読み込む
        $book->load('genres');

        return (new BookResource($book))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 書籍情報を更新し、ジャンルとの関連を同期する。
     */
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

    /**
     * 書籍を削除する。
     */
    public function destroy(Book $book): JsonResponse
    {
        $this->authorize('delete', $book);

        $book->delete();

        return response()->json(null, 204);
    }
}
