<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\Review;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function index(): View
    {
        $user = auth()->user();

        $reviews = Review::with('book.genres')
            ->where('user_id', $user->id)
            ->get();

        $completedPlans = ReadingPlan::where('user_id', $user->id)
            ->where('status', ReadingPlanStatus::Completed)
            ->get();

        $ratingDistribution = collect(range(1, 5))
            ->map(function (int $rating) use ($reviews) {
                return $reviews
                    ->filter(fn ($review) => $review->rating === $rating)
                    ->count();
            });

        $topRatedBooks = $reviews
            ->filter(fn ($review) => $review->rating >= 4)
            ->sortByDesc('rating')
            ->take(5)
            ->map(fn ($review) => [
                'id' => $review->book->id,
                'title' => $review->book->title,
                'author' => $review->book->author,
                'rating' => $review->rating,
            ])
            ->values();

        $genreRatings = $reviews
            ->flatMap(function ($review) {
                return $review->book->genres->map(function ($genre) use ($review) {
                    return [
                        'id' => $genre->id,
                        'name' => $genre->name,
                        'rating' => $review->rating,
                    ];
                });
            })
            ->groupBy('id')
            ->map(function ($items) {
                return [
                    'id' => $items->first()['id'],
                    'name' => $items->first()['name'],
                    'count' => $items->count(),
                    'average_rating' => $items->avg('rating'),
                ];
            })
            ->sortByDesc('average_rating')
            ->take(5)
            ->values();

        $stats = [
            'summary' => [
                'total_reviews' => $reviews->count(),
                'books_read' => $completedPlans->count(),
                'average_rating' => $reviews->avg('rating') ?? 0,
            ],
            'rating_distribution' => $ratingDistribution,
            'top_rated_books' => $topRatedBooks,
            'genre_ratings' => $genreRatings,
        ];

        return view('reports.index', compact('stats'));
    }
}
