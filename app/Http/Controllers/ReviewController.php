<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreReviewRequest;
use App\Http\Requests\UpdateReviewRequest;
use App\Models\Book;
use App\Models\Review;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /**
     * 書籍にレビューを投稿する。
     * 既にレビューが存在する場合は投稿を行わない。
     */
    public function store(StoreReviewRequest $request, Book $book): RedirectResponse
    {
        if (
            Review::where('book_id', $book->id)
                ->where('user_id', auth()->id())
                ->exists()
        ) {
            return redirect()->route('books.show', $book)
                ->with('error', '既にレビューを投稿しています。');
        }

        $data = $request->validated();

        $data['user_id'] = auth()->id();
        $data['book_id'] = $book->id;

        $review = Review::create($data);

        return redirect()->route('books.show', $review->book)
            ->with('success', 'レビューを投稿しました。');
    }

    /**
     * レビュー編集画面を表示する。
     */
    public function edit(Review $review): View
    {
        $this->authorize('update', $review);

        return view('reviews.edit', compact('review'));
    }

    /**
     * レビューを更新する。
     */
    public function update(UpdateReviewRequest $request, Review $review): RedirectResponse
    {
        $this->authorize('update', $review);

        $review->update($request->validated());

        return redirect()->route('books.show', $review->book)
            ->with('success', 'レビューを更新しました。');
    }

    /**
     * レビューを削除する。
     */
    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $book = $review->book;

        $review->delete();

        return redirect()->route('books.show', $book)
            ->with('success', 'レビューを削除しました。');
    }
}
