<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_ユーザーから紐づくお気に入り書籍を取得できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        Favorite::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $user->load('favoriteBooks');

        $this->assertCount(1, $user->favoriteBooks);
        $this->assertTrue(
            $user->favoriteBooks->contains($book)
        );
    }

    public function test_ユーザーからいいねしたレビューを取得できる(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        ReviewLike::create([
            'user_id' => $user->id,
            'review_id' => $review->id,
        ]);

        $user->load('likedReviews');

        $this->assertCount(1, $user->likedReviews);
        $this->assertTrue(
            $user->likedReviews->contains($review)
        );
    }
}
