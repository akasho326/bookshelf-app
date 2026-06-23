<?php

namespace Tests\Feature\Api;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiBookTest extends TestCase
{
    use RefreshDatabase;

    public function test_書籍一覧を_jso_n形式で取得できる(): void
    {
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'title' => 'APIテストの本',
        ]);

        $book->genres()->attach($genre);

        $response = $this->getJson('/api/v1/books');

        $response->assertOk();
        $response->assertJsonPath('data.0.title', 'APIテストの本');
        $response->assertJsonPath('data.0.genres.0.name', $genre->name);
    }

    public function test_指定がない場合は20件ずつページネーションされる(): void
    {
        Book::factory()->count(25)->create();

        $response = $this->getJson('/api/v1/books');

        $response->assertOk();
        $response->assertJsonCount(20, 'data');
        $response->assertJsonPath('meta.per_page', 20);
        $response->assertJsonPath('meta.total', 25);
    }

    public function test_per_pageを指定してページネーションできる(): void
    {
        Book::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/books?per_page=2');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonPath('meta.per_page', 2);
        $response->assertJsonPath('meta.total', 3);
    }

    public function test_キーワードで書籍を検索できる(): void
    {
        Book::factory()->create([
            'title' => 'Laravel入門',
            'author' => '山田太郎',
        ]);

        Book::factory()->create([
            'title' => 'PHP実践',
            'author' => '佐藤花子',
        ]);

        $response = $this->getJson('/api/v1/books?keyword=Laravel');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.title', 'Laravel入門');
    }

    public function test_ジャンルで書籍を絞り込める(): void
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

        $response = $this->getJson("/api/v1/books?genre_id={$targetGenre->id}");

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.title', '対象ジャンルの本');
    }

    public function test_書籍詳細を_jso_n形式で取得できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $book = Book::factory()->create([
            'title' => '詳細取得の本',
        ]);

        $book->genres()->attach($genre);

        Review::factory()->create([
            'book_id' => $book->id,
            'user_id' => $user->id,
            'rating' => 5,
            'comment' => '最高でした',
        ]);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertOk();
        $response->assertJsonPath('data.title', '詳細取得の本');
        $response->assertJsonPath('data.genres.0.name', $genre->name);
        $response->assertJsonPath('data.reviews.0.comment', '最高でした');
        $response->assertJsonPath('data.reviews_count', 1);
        $response->assertJsonPath('data.reviews_avg_rating', 5);
    }

    public function test_存在しない書籍_i_dでは404エラーを返す(): void
    {
        $response = $this->getJson('/api/v1/books/999999');

        $response->assertNotFound();
    }

    public function test_書籍作成_ap_iで書籍を作成できる(): void
    {
        User::factory()->create(['id' => 1]);
        $genre = Genre::factory()->create();

        $response = $this->postJson('/api/v1/books', [
            'title' => 'API作成の本',
            'author' => 'API著者',
            'isbn' => '1234567890123',
            'published_date' => '2024-01-01',
            'description' => '説明文です',
            'image_url' => 'https://example.com/book.jpg',
            'genres' => [$genre->id],
        ]);

        $response->assertCreated();
        $response->assertJsonPath('data.title', 'API作成の本');

        $this->assertDatabaseHas('books', [
            'title' => 'API作成の本',
            'author' => 'API著者',
            'isbn' => '1234567890123',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $response->json('data.id'),
            'genre_id' => $genre->id,
        ]);
    }

    public function test_書籍作成のバリデーションエラー時は422エラーを返す(): void
    {
        $response = $this->postJson('/api/v1/books', [
            'title' => '',
            'author' => '',
            'isbn' => '',
            'published_date' => '',
            'genres' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'title',
            'author',
            'isbn',
            'published_date',
            'genres',
        ]);
    }

    public function test_書籍更新_ap_iで書籍を更新できる(): void
    {
        $genre = Genre::factory()->create();
        $book = Book::factory()->create();

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => $book->isbn,
            'published_date' => '2024-01-01',
            'description' => '更新後説明',
            'image_url' => 'https://example.com/updated.jpg',
            'genres' => [$genre->id],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.title', '更新後タイトル');

        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後タイトル',
        ]);

        $this->assertDatabaseHas('book_genre', [
            'book_id' => $book->id,
            'genre_id' => $genre->id,
        ]);
    }

    public function test_存在しない書籍_i_dでは更新時に404エラーを返す(): void
    {
        $genre = Genre::factory()->create();

        $response = $this->putJson('/api/v1/books/999999', [
            'title' => '更新後タイトル',
            'author' => '更新後著者',
            'isbn' => '1234567890123',
            'published_date' => '2024-01-01',
            'description' => '更新後説明',
            'image_url' => 'https://example.com/updated.jpg',
            'genres' => [$genre->id],
        ]);

        $response->assertNotFound();
    }

    public function test_書籍更新時のバリデーションエラー時は422エラーを返す(): void
    {
        $book = Book::factory()->create();

        $response = $this->putJson("/api/v1/books/{$book->id}", [
            'title' => '',
            'author' => '',
            'isbn' => '',
            'published_date' => '',
            'description' => '',
            'image_url' => 'invalid-url',
            'genres' => [],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors([
            'title',
            'author',
            'isbn',
            'published_date',
            'image_url',
            'genres',
        ]);
    }

    public function test_書籍削除_ap_iで書籍を削除できる(): void
    {
        $book = Book::factory()->create();

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('books', [
            'id' => $book->id,
        ]);
    }

    public function test_存在しない書籍_i_dでは削除時に404エラーを返す(): void
    {
        $response = $this->deleteJson('/api/v1/books/999999');

        $response->assertNotFound();
    }
}
