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

    public function test_未認証ユーザーは書籍登録画面にアクセスするとログイン画面へリダイレクトされる(): void
    {
        $response = $this->get(route('books.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_一覧画面でキーワードで書籍を検索できる(): void
    {
        Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        Book::factory()->create([
            'title' => 'PHP実践',
            'author' => '佐藤花子',
        ]);

        $response = $this->get(route('books.index', [
            'keyword' => 'Laravel',
        ]));

        $response->assertOk();
        $response->assertViewIs('books.index');

        $books = $response->viewData('books');

        $this->assertCount(1, $books);
        $this->assertEquals('Laravel入門', $books->first()->title);
    }

    public function test_一覧画面でジャンルで書籍を絞り込める(): void
    {
        $targetGenre = Genre::factory()->create();
        $otherGenre = Genre::factory()->create();

        $targetBook = Book::factory()->create([
            'title' => '対象ジャンルの本',
        ]);

        $otherBook = Book::factory()->create([
            'title' => '別ジャンルの本',
        ]);

        $targetBook->genres()->attach($targetGenre);
        $otherBook->genres()->attach($otherGenre);

        $response = $this->get(route('books.index', [
            'genre' => $targetGenre->id,
        ]));

        $response->assertOk();
        $response->assertViewIs('books.index');

        $books = $response->viewData('books');

        $this->assertCount(1, $books);
        $this->assertEquals('対象ジャンルの本', $books->first()->title);
    }

    public function test_一覧画面のソートで新しい順に書籍を並び替えられる(): void
    {
        Book::factory()->create([
            'title' => '古い本',
            'created_at' => now()->subDay(),
        ]);

        Book::factory()->create([
            'title' => '新しい本',
            'created_at' => now(),
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'newest',
        ]));

        $response->assertOk();
        $response->assertViewIs('books.index');

        $books = $response->viewData('books');

        $this->assertEquals('新しい本', $books->first()->title);
    }

    public function test_一覧画面のソートで古い順に書籍を並び替えられる(): void
    {
        Book::factory()->create([
            'title' => '古い本',
            'created_at' => now()->subDay(),
        ]);

        Book::factory()->create([
            'title' => '新しい本',
            'created_at' => now(),
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'oldest',
        ]));

        $response->assertOk();
        $response->assertViewIs('books.index');

        $books = $response->viewData('books');

        $this->assertEquals('古い本', $books->first()->title);
    }

    public function test_一覧画面のソートでタイトル順に書籍を並び替えられる(): void
    {
        Book::factory()->create([
            'title' => 'C言語',
        ]);

        Book::factory()->create([
            'title' => 'Laravel',
        ]);

        Book::factory()->create([
            'title' => 'PHP',
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'title',
        ]));

        $response->assertOk();
        $response->assertViewIs('books.index');

        $books = $response->viewData('books');

        $this->assertEquals(
            ['C言語', 'Laravel', 'PHP'],
            $books->pluck('title')->values()->all()
        );
    }

    public function test_一覧画面のソートで評価順に書籍を並び替えられる(): void
    {
        $highBook = Book::factory()->create([
            'title' => 'Laravel入門',
        ]);

        $lowBook = Book::factory()->create([
            'title' => 'PHP実践',
        ]);

        Review::factory()->create([
            'book_id' => $highBook->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $lowBook->id,
            'rating' => 2,
        ]);

        $response = $this->get(route('books.index', [
            'sort' => 'rating',
        ]));

        $response->assertOk();
        $response->assertViewIs('books.index');

        $books = $response->viewData('books');

        $this->assertEquals(
            ['Laravel入門', 'PHP実践'],
            $books->pluck('title')->values()->all()
        );
    }
}
