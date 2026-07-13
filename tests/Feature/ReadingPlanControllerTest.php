<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingPlanControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_認証済みユーザーは読書計画一覧画面を表示できる(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('reading-plans.index'));

        $response->assertOk();
        $response->assertViewIs('reading-plans.index');
        $response->assertViewHas('readingPlans');
    }

    public function test_未認証ユーザーは読書計画一覧画面にアクセスできない(): void
    {
        $response = $this->get(route('reading-plans.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_認証済みユーザーは読書計画を作成できる(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => $book->id,
                'target_date' => today()->addDays(3)->toDateString(),
            ]);

        $this->assertDatabaseHas('reading_plans', [
            'user_id' => $user->id,
            'book_id' => $book->id,
            'status' => ReadingPlanStatus::Reading->value,
            'completed_at' => null,
        ]);

        $response->assertRedirect(route('reading-plans.index'));
    }

    public function test_読書計画作成時のバリデーションエラー時は保存されない(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('reading-plans.store'), [
                'book_id' => '',
                'target_date' => '',
            ]);

        $response->assertSessionHasErrors([
            'book_id',
            'target_date',
        ]);

        $this->assertDatabaseCount('reading_plans', 0);
    }

    public function test_作成者は読書計画編集画面を表示できる(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.edit', $readingPlan));

        $response->assertOk();
        $response->assertViewIs('reading-plans.edit');
        $response->assertViewHas('readingPlan');
    }

    public function test_作成者以外は読書計画編集画面を表示できない(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->get(route('reading-plans.edit', $readingPlan));

        $response->assertForbidden();
    }

    public function test_作成者は読書計画を更新できる(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => today()->addDays(3),
            'status' => ReadingPlanStatus::Reading,
        ]);

        $newTargetDate = today()->addDays(7)->toDateString();

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => $newTargetDate,
                'status' => ReadingPlanStatus::Reading->value,
            ]);

        $response->assertRedirect(route('reading-plans.index'));

        $readingPlan->refresh();

        $this->assertSame(
            $newTargetDate,
            $readingPlan->target_date->toDateString()
        );

        $this->assertSame(
            ReadingPlanStatus::Reading,
            $readingPlan->status
        );
    }

    public function test_作成者以外は読書計画を更新できない(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $owner->id,
            'target_date' => today()->addDays(3),
            'status' => ReadingPlanStatus::Reading,
        ]);

        $originalTargetDate = $readingPlan->target_date->toDateString();

        $response = $this->actingAs($otherUser)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => today()->addDays(10)->toDateString(),
                'status' => ReadingPlanStatus::Reading->value,
            ]);

        $response->assertForbidden();

        $readingPlan->refresh();

        $this->assertSame(
            $originalTargetDate,
            $readingPlan->target_date->toDateString()
        );
    }

    public function test_作成者は読書計画を削除できる(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)
            ->delete(route('reading-plans.destroy', $readingPlan));

        $response->assertRedirect(route('reading-plans.index'));

        $this->assertDatabaseMissing('reading_plans', [
            'id' => $readingPlan->id,
        ]);
    }

    public function test_作成者以外は読書計画を削除できない(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($otherUser)
            ->delete(route('reading-plans.destroy', $readingPlan));

        $response->assertForbidden();

        $this->assertDatabaseHas('reading_plans', [
            'id' => $readingPlan->id,
        ]);
    }

    public function test_作成者は読書計画を読了にできる(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Reading,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($user)
            ->post(route('reading-plans.complete', $readingPlan));

        $response->assertRedirect(route('reading-plans.index'));

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $readingPlan->status
        );

        $this->assertNotNull($readingPlan->completed_at);
    }

    public function test_作成者以外は読書計画を読了にできない(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $owner->id,
            'status' => ReadingPlanStatus::Reading,
            'completed_at' => null,
        ]);

        $response = $this->actingAs($otherUser)
            ->post(route('reading-plans.complete', $readingPlan));

        $response->assertForbidden();

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Reading,
            $readingPlan->status
        );

        $this->assertNull($readingPlan->completed_at);
    }

    public function test_一覧画面でステータスを絞り込める(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Reading,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.index', [
                'status' => ReadingPlanStatus::Reading->value,
            ]));

        $response->assertOk();
        $response->assertViewIs('reading-plans.index');

        $readingPlans = $response->viewData('readingPlans');

        $this->assertCount(1, $readingPlans);
        $this->assertSame($readingPlan->id, $readingPlans->first()->id);
    }

    public function test_一覧画面には自分の読書計画だけが表示される(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownReadingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
        ]);

        ReadingPlan::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($user)
            ->get(route('reading-plans.index'));

        $response->assertOk();

        $readingPlans = $response->viewData('readingPlans');

        $this->assertCount(1, $readingPlans);
        $this->assertSame($ownReadingPlan->id, $readingPlans->first()->id);
    }

    public function test_読書計画の期限を今日以降の日付に変更できる(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => today()->addDays(3),
            'status' => ReadingPlanStatus::Reading,
        ]);

        $newTargetDate = today()->addDays(10)->toDateString();

        $response = $this->actingAs($user)
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => $newTargetDate,
                'status' => ReadingPlanStatus::Reading->value,
            ]);

        $response->assertRedirect(route('reading-plans.index'));
        $response->assertSessionHasNoErrors();

        $readingPlan->refresh();

        $this->assertSame(
            $newTargetDate,
            $readingPlan->target_date->toDateString()
        );
    }

    public function test_読書計画の期限を過去の日付には変更できない(): void
    {
        $user = User::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'target_date' => today()->addDays(3),
            'status' => ReadingPlanStatus::Reading,
        ]);

        $originalTargetDate = $readingPlan->target_date->toDateString();

        $response = $this->actingAs($user)
            ->from(route('reading-plans.edit', $readingPlan))
            ->put(route('reading-plans.update', $readingPlan), [
                'target_date' => today()->subDay()->toDateString(),
                'status' => ReadingPlanStatus::Reading->value,
            ]);

        $response->assertRedirect(route('reading-plans.edit', $readingPlan));
        $response->assertSessionHasErrors('target_date');

        $readingPlan->refresh();

        $this->assertSame(
            $originalTargetDate,
            $readingPlan->target_date->toDateString()
        );
    }
}
