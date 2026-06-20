<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_お気に入りから紐づくユーザーを取得できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $favorite = Favorite::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $favorite->load('user');

        $this->assertTrue(
            $favorite->user->is($user)
        );
    }

    public function test_お気に入りから紐づく書籍を取得できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $favorite = Favorite::create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $favorite->load('book');

        $this->assertTrue(
            $favorite->book->is($book)
        );
    }
}
