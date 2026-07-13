<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証済みユーザーはマイ読書レポート画面を表示できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();
        $response->assertViewIs('reports.index');
        $response->assertViewHas('stats');
    }

    public function test_未認証ユーザーはマイ読書レポート画面にアクセスできない(): void
    {
        $response = $this->get(route('reports.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_マイ読書レポートの集計結果が正しい(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $highBook = Book::factory()->create([
            'title' => '高評価の本',
            'author' => '高評価著者',
        ]);

        $mediumBook = Book::factory()->create([
            'title' => '中評価の本',
            'author' => '中評価著者',
        ]);

        $otherBook = Book::factory()->create([
            'title' => '他ユーザーの本',
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $highBook->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $mediumBook->id,
            'rating' => 3,
        ]);

        Review::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $otherBook->id,
            'rating' => 4,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $highBook->id,
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $mediumBook->id,
            'status' => ReadingPlanStatus::Reading,
            'completed_at' => null,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
            'book_id' => $otherBook->id,
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();

        $stats = $response->viewData('stats');

        $this->assertSame(2, $stats['summary']['total_reviews']);
        $this->assertSame(1, $stats['summary']['books_read']);
        $this->assertEquals(4.0, $stats['summary']['average_rating']);

        $this->assertEquals(
            [0, 0, 1, 0, 1],
            $stats['rating_distribution']->values()->all()
        );

        $this->assertCount(1, $stats['top_rated_books']);
        $this->assertSame('高評価の本', $stats['top_rated_books']->first()['title']);
        $this->assertSame(5, $stats['top_rated_books']->first()['rating']);
    }

    public function test_ジャンル別評価が平均評価の高い順に集計される(): void
    {
        $user = User::factory()->create();

        $technicalGenre = Genre::factory()->create([
            'name' => '技術書',
        ]);

        $novelGenre = Genre::factory()->create([
            'name' => '小説',
        ]);

        $technicalBook = Book::factory()->create();
        $novelBook = Book::factory()->create();

        $technicalBook->genres()->attach($technicalGenre);
        $novelBook->genres()->attach($novelGenre);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $technicalBook->id,
            'rating' => 5,
        ]);

        Review::factory()->create([
            'user_id' => $user->id,
            'book_id' => $novelBook->id,
            'rating' => 3,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reports.index'));

        $response->assertOk();
        $response->assertViewIs('reports.index');

        $stats = $response->viewData('stats');
        $genreRatings = $stats['genre_ratings'];

        $this->assertSame('技術書', $genreRatings->first()['name']);
        $this->assertEquals(5.0, $genreRatings->first()['average_rating']);
        $this->assertSame(1, $genreRatings->first()['count']);
    }
}
