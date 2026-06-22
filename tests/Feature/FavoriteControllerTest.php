<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証済みユーザーはお気に入りを追加できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('favorites.toggle', $book));

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response->assertRedirect(route('books.show', $book));
    }

    public function test_お気に入り追加済みなら解除できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $favorite = Favorite::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($user)
            ->post(route('favorites.toggle', $book));

        $this->assertDatabaseMissing('favorites', [
            'id' => $favorite->id,
        ]);

        $response->assertRedirect(route('books.show', $book));
    }

    public function test_お気に入りは追加→解除→再追加できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        // 追加
        $this->actingAs($user)
            ->post(route('favorites.toggle', $book));

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // 解除
        $this->actingAs($user)
            ->post(route('favorites.toggle', $book));

        $this->assertDatabaseMissing('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);

        // 再追加
        $this->actingAs($user)
            ->post(route('favorites.toggle', $book));

        $this->assertDatabaseHas('favorites', [
            'user_id' => $user->id,
            'book_id' => $book->id,
        ]);
    }

    public function test_認証済みユーザーはお気に入り一覧画面を表示できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('favorites.index'));

        $response->assertOk();
        $response->assertViewIs('favorites.index');
        $response->assertViewHas('books');
    }

    public function test_一覧画面にお気に入り書籍が表示される(): void
    {
        $user = User::factory()->create();

        $book = Book::factory()->create([
            'title' => 'お気に入り書籍',
        ]);

        Favorite::factory()->create([
            'book_id' => $book->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('favorites.index'));

        $response->assertOk();
        $response->assertViewIs('favorites.index');
        $response->assertViewHas('books');
        $response->assertSee('お気に入り書籍');
    }

    public function test_一覧画面でページネーションされた書籍を表示する(): void
    {
        $user = User::factory()->create();

        $books = Book::factory()->count(15)->create();

        $books->each(function ($book) use ($user) {
            Favorite::factory()->create([
                'book_id' => $book->id,
                'user_id' => $user->id,
            ]);
        });

        $response = $this->actingAs($user)
            ->get(route('favorites.index'));

        $response->assertOk();
        $response->assertViewIs('favorites.index');

        $this->assertEquals(10, $response->viewData('books')->count());
    }
}
