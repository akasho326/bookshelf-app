<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $reviews = Review::all();

        // 各レビューに0～3人のユーザーがいいね（自分のレビューを除く）
        foreach ($reviews as $review) {
            $likeUserIds = $users
                ->where('id', '!=', $review->user_id)
                ->random(rand(0, 3))
                ->pluck('id');

            $review->likedByUsers()->syncWithoutDetaching($likeUserIds);
        }
    }
}
