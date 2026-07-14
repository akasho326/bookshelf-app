<?php

namespace Tests\Feature;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use App\Notifications\ReadingPlanDeadlineNotification;
use Illuminate\Console\Command;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendReadingPlanDeadlineNotificationsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_期限3日前の読書計画に通知を送信する(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->addDays(3),
            'status' => ReadingPlanStatus::Reading,
        ]);

        $this->artisan('app:send-reading-plan-deadline-notifications')
            ->assertExitCode(Command::SUCCESS);

        Notification::assertSentTo(
            $user,
            ReadingPlanDeadlineNotification::class,
            function (ReadingPlanDeadlineNotification $notification) use ($user, $readingPlan) {
                $data = $notification->toArray($user);

                return $data['reading_plan_id'] === $readingPlan->id
                    && $data['timing'] === 'three_days_before'
                    && $data['title'] === '読書期限3日前のお知らせ';
            }
        );
    }

    public function test_期限当日の読書計画に通知を送信する(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today(),
            'status' => ReadingPlanStatus::Reading,
        ]);

        $this->artisan('app:send-reading-plan-deadline-notifications')
            ->assertExitCode(Command::SUCCESS);

        Notification::assertSentTo(
            $user,
            ReadingPlanDeadlineNotification::class,
            function (ReadingPlanDeadlineNotification $notification) use ($user, $readingPlan) {
                $data = $notification->toArray($user);

                return $data['reading_plan_id'] === $readingPlan->id
                    && $data['timing'] === 'on_due_date'
                    && $data['title'] === '読書期限当日のお知らせ';
            }
        );
    }

    public function test_期限を3日過ぎた読書計画に通知を送信する(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $book = Book::factory()->create();

        $readingPlan = ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->subDays(3),
            'status' => ReadingPlanStatus::Expired,
        ]);

        $this->artisan('app:send-reading-plan-deadline-notifications')
            ->assertExitCode(Command::SUCCESS);

        Notification::assertSentTo(
            $user,
            ReadingPlanDeadlineNotification::class,
            function (ReadingPlanDeadlineNotification $notification) use ($user, $readingPlan) {
                $data = $notification->toArray($user);

                return $data['reading_plan_id'] === $readingPlan->id
                    && $data['timing'] === 'three_days_after'
                    && $data['title'] === '読書期限超過のお知らせ';
            }
        );
    }

    public function test_通知対象外の読書計画には通知を送信しない(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $book = Book::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->addDays(7),
            'status' => ReadingPlanStatus::Reading,
        ]);

        $this->artisan('app:send-reading-plan-deadline-notifications')
            ->assertExitCode(Command::SUCCESS);

        Notification::assertNothingSent();
    }

    public function test_期限を過ぎた読書中の計画を期限切れに変更する(): void
    {
        $readingPlan = ReadingPlan::factory()->create([
            'target_date' => today()->subDay(),
            'status' => ReadingPlanStatus::Reading,
            'completed_at' => null,
        ]);

        $this->artisan('app:send-reading-plan-deadline-notifications')
            ->assertExitCode(Command::SUCCESS);

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Expired,
            $readingPlan->status
        );
    }

    public function test_期限当日と未来の読書計画は期限切れに変更しない(): void
    {
        $dueTodayPlan = ReadingPlan::factory()->create([
            'target_date' => today(),
            'status' => ReadingPlanStatus::Reading,
        ]);

        $futurePlan = ReadingPlan::factory()->create([
            'target_date' => today()->addDays(3),
            'status' => ReadingPlanStatus::Reading,
        ]);

        $this->artisan('app:send-reading-plan-deadline-notifications')
            ->assertExitCode(Command::SUCCESS);

        $this->assertSame(
            ReadingPlanStatus::Reading,
            $dueTodayPlan->fresh()->status
        );

        $this->assertSame(
            ReadingPlanStatus::Reading,
            $futurePlan->fresh()->status
        );
    }

    public function test_読了済みの計画は期限を過ぎても期限切れに変更しない(): void
    {
        $readingPlan = ReadingPlan::factory()->create([
            'target_date' => today()->subDays(5),
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => now()->subDay(),
        ]);

        $this->artisan('app:send-reading-plan-deadline-notifications')
            ->assertExitCode(Command::SUCCESS);

        $readingPlan->refresh();

        $this->assertSame(
            ReadingPlanStatus::Completed,
            $readingPlan->status
        );

        $this->assertNotNull($readingPlan->completed_at);
    }

    public function test_同じタイミングの通知を二重に送信しない(): void
    {
        $user = User::factory()->create();
        $book = Book::factory()->create();

        ReadingPlan::factory()->create([
            'user_id' => $user->id,
            'book_id' => $book->id,
            'target_date' => today()->addDays(3),
            'status' => ReadingPlanStatus::Reading,
        ]);

        $this->artisan('app:send-reading-plan-deadline-notifications')
            ->assertSuccessful();

        $this->artisan('app:send-reading-plan-deadline-notifications')
            ->assertSuccessful();

        $this->assertSame(1, $user->notifications()->count());
    }
}
