<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\Genre;
use App\Models\Review;
use App\Models\ReviewLike;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証済みユーザーが書籍を作成できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->post('/books', [
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'description' => 'テスト説明文',
            'image_url' => 'https://example.com/image.jpg',
            'genres' => [$genre->id],
        ]);

        $this->assertDatabaseHas('books', [
            'user_id' => $user->id,
            'title' => 'テスト書籍',
            'author' => 'テスト著者',
            'isbn' => '1234567890123',
        ]);

        $book = Book::first();

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);

        $response->assertRedirect(route('books.show', $book));
    }

    public function test_バリデーションエラー時は書籍が保存されない(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/books', [
            'title' => '',
            'author' => '',
            'isbn' => '123',
            'published_date' => '2030-01-01',
            'description' => '',
            'image_url' => 'not-url',
            'genres' => [],
        ]);

        $response->assertSessionHasErrors([
            'title',
            'author',
            'isbn',
            'published_date',
            'image_url',
            'genres',
        ]);

        $this->assertDatabaseCount('books', 0);
    }

    public function test_未認証ユーザーは書籍を作成できない(): void
    {
        $genre = Genre::factory()->create();

        $response = $this->post(route('books.store'), [
            'title' => 'テスト本',
            'author' => '著者',
            'isbn' => '1234567890123',
            'published_date' => '2026-01-01',
            'genres' => [$genre->id],
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseMissing('books', [
            'title' => 'テスト本',
        ]);
    }

    public function test_作成者のみが書籍編集画面を表示できる(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('books.edit', $book));

        $response->assertStatus(200);
        $response->assertViewIs('books.edit');
        $response->assertSee($book->title);
    }

    public function test_作成者以外は書籍編集画面を表示できない(): void
    {
        $owner = User::factory()->create();

        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->get(route('books.edit', $book));

        $response->assertForbidden();
    }

    public function test_作成者は書籍を更新できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->put(route('books.update', $book), [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => '9876543210123',
            'published_date' => '2026-01-01',
            'description' => '更新後説明文',
            'image_url' => 'https://example.com/updated.jpg',
            'genres' => [$genre->id],
        ]);

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => '9876543210123',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);

        $response->assertRedirect(route('books.show', $book));
    }

    public function test_作成者以外は書籍を更新できない(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
            'title' => '更新前タイトル',
        ]);

        $response = $this->actingAs($otherUser)
            ->put(route('books.update', $book), [
                'title' => '不正な更新タイトル',
                'author' => '不正な更新著者',
                'isbn' => '9876543210123',
                'published_date' => '2026-01-01',
                'description' => '不正な更新',
                'image_url' => 'https://example.com/updated.jpg',
                'genres' => [$genre->id],
            ]);

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新前タイトル',
        ]);
    }

    public function test_作成者は書籍を削除でき関連データも削除される(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $user->id,
        ]);

        $book->genres()->attach($genre->id);

        $review = Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $user->id,
        ]);

        $reviewLike = ReviewLike::factory()->create([
            'review_id' => $review->id,
            'user_id' => $user->id,
        ]);

        $favorite = Favorite::factory()->create([
            'book_id' => $book->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->delete(route('books.destroy', $book));

        $response->assertRedirect(route('books.index'));

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);

        $this->assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);

        $this->assertDatabaseMissing('review_likes', [
            'id' => $reviewLike->id,
        ]);

        $this->assertDatabaseMissing('favorites', [
            'id' => $favorite->id,
        ]);

        $this->assertDatabaseMissing('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_作成者以外は書籍を削除できない(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $book = Book::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->delete(route('books.destroy', $book));

        $response->assertForbidden();

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
        ]);
    }

    public function test_isbn検索で書籍情報を取得できる(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'www.googleapis.com/*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'リーダブルコード',
                            'authors' => [
                                'Dustin Boswell',
                                'Trevor Foucher',
                            ],
                            'publishedDate' => '2012-06-23',
                            'description' => 'より良いコードを書くための実践的な解説です。',
                            'imageLinks' => [
                                'thumbnail' => 'https://example.com/book.jpg',
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('books.search-by-isbn', [
                'isbn' => '9784873115658',
            ]));

        $response->assertOk();
        $response->assertJson([
            'title' => 'リーダブルコード',
            'author' => 'Dustin Boswell, Trevor Foucher',
            'isbn' => '9784873115658',
            'published_date' => '2012-06-23',
            'description' => 'より良いコードを書くための実践的な解説です。',
            'image_url' => 'https://example.com/book.jpg',
        ]);

        Http::assertSent(function ($request) {
            return str_contains(
                $request->url(),
                'www.googleapis.com/books/v1/volumes'
            )
                && $request['q'] === 'isbn:9784873115658'
                && $request['key'] === config('services.google.books_api_key');
        });
    }

    public function test_isbnが13桁でない場合は422エラーを返す(): void
    {
        $user = User::factory()->create();

        Http::fake();

        $response = $this->actingAs($user)
            ->getJson(route('books.search-by-isbn', [
                'isbn' => '123',
            ]));

        $response->assertUnprocessable();
        $response->assertJson([
            'error' => 'ISBNは13桁の数字で入力してください。',
        ]);

        Http::assertNothingSent();
    }

    public function test_isbn検索で該当書籍がない場合は404エラーを返す(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'www.googleapis.com/*' => Http::response([
                'items' => [],
            ], 200),
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('books.search-by-isbn', [
                'isbn' => '9784873115658',
            ]));

        $response->assertNotFound();
        $response->assertJson([
            'error' => '該当する書籍が見つかりませんでした。',
        ]);
    }

    public function test_google_books_apiの利用上限時は429エラーを返す(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'www.googleapis.com/*' => Http::response([], 429),
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('books.search-by-isbn', [
                'isbn' => '9784873115658',
            ]));

        $response->assertStatus(429);
        $response->assertJson([
            'error' => 'Google Books APIの利用上限に達しました。時間をおいて再度お試しください。',
        ]);
    }

    public function test_google_books_apiの取得失敗時は500エラーを返す(): void
    {
        $user = User::factory()->create();

        Http::fake([
            'www.googleapis.com/*' => Http::response([], 500),
        ]);

        $response = $this->actingAs($user)
            ->getJson(route('books.search-by-isbn', [
                'isbn' => '9784873115658',
            ]));

        $response->assertInternalServerError();
        $response->assertJson([
            'error' => '書籍情報の取得に失敗しました。',
        ]);
    }
}
