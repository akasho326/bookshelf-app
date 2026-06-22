<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_ランキング一覧画面を表示できる(): void
    {
        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $response->assertViewIs('ranking.index');
        $response->assertViewHas('rankedBooks');
    }

    public function test_平均評価が高い順に書籍が表示される(): void
    {
        $user = User::factory()->create();

        $highBook = Book::factory()->create([
            'title' => '高評価の本',
        ]);

        $lowBook = Book::factory()->create([
            'title' => '低評価の本',
        ]);

        Review::factory()->create([
            'book_id' => $highBook->id,
            'user_id' => $user->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'book_id' => $lowBook->id,
            'user_id' => User::factory()->create()->id,
            'rating' => 2,
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $response->assertSeeInOrder([
            '高評価の本',
            '低評価の本',
        ]);
    }

    public function test_ランキング画面でレビューがない書籍は表示されない(): void
    {
        $reviewedBook = Book::factory()->create([
            'title' => 'レビューありの本',
        ]);

        Book::factory()->create([
            'title' => 'レビューなしの本',
        ]);

        Review::factory()->create([
            'book_id' => $reviewedBook->id,
            'user_id' => User::factory()->create()->id,
            'rating' => 4,
        ]);

        $response = $this->get(route('ranking.index'));

        $response->assertOk();
        $response->assertSee('レビューありの本');
        $response->assertDontSee('レビューなしの本');
    }
}
