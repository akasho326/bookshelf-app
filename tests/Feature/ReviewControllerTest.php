<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証済みユーザーがレビューを投稿できる(): void
    {
        $owner = User::factory()->create();

        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->post(route('reviews.store', $book), [
                'book_id' => $book->id,
                'rating' => 5,
                'comment' => '最高のレビューです',
            ]);

        $this->assertDatabaseHas('reviews', [
            'book_id' => $book->id,
            'user_id' => $otherUser->id,
            'rating' => 5,
            'comment' => '最高のレビューです',
        ]);

        $response->assertRedirect(route('books.show', $book));
    }

    public function test_バリデーションエラー時はレビューが保存されない(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('reviews.store', $book), [
                'book_id' => $book->id,
                'rating' => '',
                'comment' => '',
            ]);

        $response->assertSessionHasErrors([
            'rating',
            'comment',
        ]);

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_作成者のみがレビュー編集画面を表示できる(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reviews.edit', $review));

        $response->assertOk();
        $response->assertViewIs('reviews.edit');
        $response->assertSee($review->comment);
    }

    public function test_作成者以外はレビュー編集画面を表示できない(): void
    {
        $owner = User::factory()->create();

        $otherUser = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->get(route('reviews.edit', $review));

        $response->assertForbidden();
    }

    public function test_作成者はレビューを更新できる(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->put(route('reviews.update', $review), [
            'rating' => 3,
            'comment' => '更新後レビューです',
        ]);

        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 3,
            'comment' => '更新後レビューです',
        ]);

        $response->assertRedirect(route('books.show', $review->book));
    }

    public function test_作成者はレビューを削除でき関連データも削除される(): void
    {
        $user = User::factory()->create();

        $review = Review::factory()->create([
            'user_id' => $user->id,
        ]);

        $book = $review->book;

        $reviewLike = ReviewLike::factory()->create([
            'review_id' => $review->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->delete(route('reviews.destroy', $review));

        $response->assertRedirect(route('books.show', $book));

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $this->assertDatabaseMissing('review_likes', [
            'id' => $reviewLike->id,
        ]);
    }
}
