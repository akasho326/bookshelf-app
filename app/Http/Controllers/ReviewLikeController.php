<?php

namespace App\Http\Controllers;

use App\Models\Review;
use App\Models\ReviewLike;
use Illuminate\Http\RedirectResponse;

class ReviewLikeController extends Controller
{
    /**
     * レビューのいいねを切り替える。
     */
    public function toggle(Review $review): RedirectResponse
    {
        // 既にいいねしているか確認
        $reviewLike = ReviewLike::where('user_id', auth()->id())
            ->where('review_id', $review->id)
            ->first();

        if ($reviewLike) {
            $reviewLike->delete();

            return redirect()->route('books.show', $review->book);
        }

        ReviewLike::create([
            'user_id' => auth()->id(),
            'review_id' => $review->id,
        ]);

        return redirect()->route('books.show', $review->book);
    }
}
