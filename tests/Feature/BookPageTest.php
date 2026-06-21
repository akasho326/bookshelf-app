<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_書籍一覧画面を表示できる(): void
    {
        $response = $this->get(route('books.index'));

        $response->assertOk();
        $response->assertViewIs('books.index');
        $response->assertViewHas('books');
    }

    public function test_一覧画面にタイトルとジャンル名も表示される(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => 'テスト書籍',
        ]);

        $book->genres()->attach($genre->id);

        $response = $this->get(route('books.index'));

        $response->assertOk();
        $response->assertViewIs('books.index');
        $response->assertViewHas('books');
        $response->assertSee('テスト書籍');
        $response->assertSee('ミステリー');
    }

    public function test_一覧画面でページネーションされた書籍一覧を表示する(): void
    {
        Book::factory()->count(15)->create();

        $response = $this->get(route('books.index'));

        $response->assertOk();
        $response->assertViewIs('books.index');
        $this->assertEquals(10, $response->viewData('books')->count());
    }

    public function test_書籍詳細画面を表示できる(): void
    {
        $book = Book::factory()->create();

        $response = $this->get(route('books.show', $book));

        $response->assertOk();
        $response->assertViewIs('books.show');
        $response->assertViewHas('book');
    }

    public function test_詳細画面でタイトル・著者・_isb_n・出版日・説明・画像・ジャンル・レビュー・いいね数も表示される(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $book = Book::factory()->create([
            'user_id' => $user->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'description' => 'テスト説明文',
            'image_url' => 'https://example.com/image.jpg',
        ]);

        $book->genres()->attach($genre->id);

        $review = Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $user->id,
            'rating' => 5,
            'comment' => '最高のレビューです',
        ]);

        ReviewLike::factory()->create([
            'review_id' => $review->id,
            'user_id' => $user->id,
        ]);

        $response = $this->get(route('books.show', $book));

        $response->assertOk();
        $response->assertViewIs('books.show');
        $response->assertViewHas('book');

        $response->assertSee('テスト書籍');
        $response->assertSee('テスト著者');
        $response->assertSee('1234567890123');
        $response->assertSee('2026-01-01');
        $response->assertSee('テスト説明文');
        $response->assertSee('https://example.com/image.jpg');
        $response->assertSee('ミステリー');
        $response->assertSee('最高のレビューです');
        $response->assertSee('1');
    }
}
