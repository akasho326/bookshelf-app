<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証済みユーザーがジャンルを作成できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/genres', [
            'name' => 'テスト',
        ]);

        $this->assertDatabaseHas('genres', [
            'name' => 'テスト',
        ]);

        $response->assertRedirect(route('genres.index'));
    }

    public function test_バリデーションエラー時はジャンルが保存されない(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/genres', [
            'name' => '',
        ]);

        $response->assertSessionHasErrors([
            'name',
        ]);

        $this->assertDatabaseCount('genres', 0);
    }

    public function test_未認証ユーザーはジャンルを作成できない(): void
    {
        $response = $this->post(route('genres.store'), [
            'name' => 'ミステリー',
        ]);

        $response->assertRedirect(route('login'));

        $this->assertDatabaseCount('genres', 0);
    }

    public function test_認証済みユーザーはジャンル編集画面を表示できる(): void
    {
        $user = User::factory()->create();

        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->get(route('genres.edit', $genre));

        $response->assertStatus(200);
        $response->assertViewIs('genres.edit');
        $response->assertSee($genre->name);
    }

    public function test_認証済みユーザーはジャンルを更新できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)->put(route('genres.update', $genre), [
            'name' => '更新後テスト',
        ]);

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '更新後テスト',
        ]);

        $response->assertRedirect(route('genres.index'));
    }

    public function test_認証済みユーザーはジャンルを削除できる(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();

        $response = $this->actingAs($user)
            ->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('genres.index'));

        $this->assertDatabaseMissing('genres', [
            'id' => $genre->id,
        ]);
    }

    public function test_書籍に紐づくジャンルは削除できない(): void
    {
        $user = User::factory()->create();
        $genre = Genre::factory()->create();
        $book = Book::factory()->create();

        $book->genres()->attach($genre->id);

        $response = $this->actingAs($user)
            ->delete(route('genres.destroy', $genre));

        $response->assertRedirect(route('genres.index'));

        $response->assertSessionHas('error', '書籍に紐づいたジャンルは削除できません。');

        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
        ]);
    }
}
