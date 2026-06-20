<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewLikeTest extends TestCase
{
    use RefreshDatabase;

    public function test_いいねから紐づくレビューを取得できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $user->id,
        ]);

        $reviewLike = ReviewLike::factory()->create([
            'review_id' => $review->id,
            'user_id' => $user->id,
        ]);

        $reviewLike->load('review');

        $this->assertTrue(
            $reviewLike->review->is($review)
        );
    }

    public function test_いいねから紐づくユーザーを取得できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $review = Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $user->id,
        ]);

        $reviewLike = ReviewLike::factory()->create([
            'review_id' => $review->id,
            'user_id' => $user->id,
        ]);

        $reviewLike->load('user');

        $this->assertTrue(
            $reviewLike->user->is($user)
        );
    }
}
