<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanDeadlineNotification;
use Illuminate\Console\Command;

class SendReadingPlanDeadlineNotifications extends Command
{
    protected $signature = 'app:send-reading-plan-deadline-notifications';

    protected $description = '読書期限が近い読書予定の通知を送信します';

    public function handle(): int
    {
        $readingPlans = ReadingPlan::with(['user', 'book'])
            ->where('status', '!=', ReadingPlanStatus::Completed)
            ->whereDate('target_date', '<=', today()->addDays(3))
            ->whereDate('target_date', '>=', today())
            ->get();

        $notifiedCount = 0;

        foreach ($readingPlans as $readingPlan) {
            $alreadyNotified = $readingPlan->user->notifications()
                ->where('type', ReadingPlanDeadlineNotification::class)
                ->where('data->reading_plan_id', $readingPlan->id)
                ->exists();

            if ($alreadyNotified) {
                continue;
            }

            $readingPlan->user->notify(
                new ReadingPlanDeadlineNotification($readingPlan)
            );

            $notifiedCount++;
        }

        $this->info("{$notifiedCount}件の通知を送信しました。");

        return Command::SUCCESS;
    }
}
