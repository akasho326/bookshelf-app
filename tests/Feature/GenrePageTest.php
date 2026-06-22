<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenrePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証済みユーザーはジャンル一覧画面を表示できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.index'));

        $response->assertOk();
        $response->assertViewIs('genres.index');
        $response->assertViewHas('genres');
    }

    public function test_一覧画面にジャンル名と紐づく書籍数も表示される(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $book = Book::factory()->create();
        $book->genres()->attach($genre->id);

        $response = $this->actingAs($user)->get(route('genres.index'));

        $response->assertOk();
        $response->assertViewIs('genres.index');
        $response->assertViewHas('genres');
        $response->assertSee('ミステリー');
        $response->assertSee('1');
    }

    public function test_ゲストユーザーはジャンル編集画面を表示できない(): void
    {
        $genre = Genre::factory()->create();

        $response = $this->get(route('genres.edit', $genre));

        $response->assertRedirect(route('login'));
    }

    public function test_認証済みユーザーはジャンル詳細画面を表示できる(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.show', $genre));

        $response->assertOk();
        $response->assertViewIs('genres.show');
        $response->assertViewHas('genre');
        $response->assertViewHas('books');
    }

    public function test_詳細画面でジャンル名と紐づく書籍も表示される(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $book = Book::factory()->create([
            'title' => 'テスト書籍',
        ]);

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($user)->get(route('genres.show', $genre));

        $response->assertOk();
        $response->assertViewIs('genres.show');
        $response->assertViewHas('genre');
        $response->assertViewHas('books');
        $response->assertSee('ミステリー');
        $response->assertSee('テスト書籍');
    }

    public function test_詳細画面でページネーションされた書籍を表示する(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create([
            'name' => 'ミステリー',
        ]);

        $books = Book::factory()->count(15)->create();

        $books->each(fn ($book) => $book->genres()->attach($genre->id));

        $response = $this->actingAs($user)->get(route('genres.show', $genre));

        $response->assertOk();
        $response->assertViewIs('genres.show');

        $this->assertEquals(10, $response->viewData('books')->count());
    }
}
