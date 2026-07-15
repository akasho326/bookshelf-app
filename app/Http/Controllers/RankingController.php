<?php

namespace App\Http\Controllers;

use App\Models\Book;
use Illuminate\View\View;

class RankingController extends Controller
{
    /**
     * 書籍ランキングを表示する。
     */
    public function index(): View
    {
        // 平均評価の高い書籍を上位10件取得
        $rankedBooks = Book::withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->whereHas('reviews')
            ->orderByDesc('reviews_avg_rating')
            ->limit(10)
            ->get();

        return view('ranking.index', compact('rankedBooks'));
    }
}
