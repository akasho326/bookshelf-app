<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    public function test_一つの書籍から紐づくユーザーを取得できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->for($user)->create();

        $this->assertTrue($book->user->is($user));
    }

    public function test_書籍とジャンルは多対多の関係で取得できる(): void
    {
        $book = Book::factory()->create();
        $genres = Genre::factory()->count(2)->create();

        $book->genres()->attach($genres->pluck('id'));

        $book->load('genres');

        $this->assertCount(2, $book->genres);
        $this->assertTrue($book->genres->pluck('id')->contains($genres->first()->id));
    }
}
