<?php

namespace App\Console\Commands;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Notifications\ReadingPlanDeadlineNotification;
use Illuminate\Console\Command;

class SendReadingPlanDeadlineNotifications extends Command
{
    protected $signature = 'app:send-reading-plan-deadline-notifications';

    protected $description = '読書計画の日次処理と期限通知を実行します';

    public function handle(): int
    {
        $notifiedCount = 0;

        // 期限3日前通知
        $threeDaysBeforePlans = ReadingPlan::with(['user', 'book'])
            ->where('status', ReadingPlanStatus::Reading)
            ->whereDate('target_date', today()->addDays(3))
            ->get();

        foreach ($threeDaysBeforePlans as $readingPlan) {
            if ($this->alreadyNotified($readingPlan, 'three_days_before')) {
                continue;
            }

            $readingPlan->user->notify(
                new ReadingPlanDeadlineNotification($readingPlan, 'three_days_before')
            );

            $notifiedCount++;
        }

        // 期限当日通知
        $dueDatePlans = ReadingPlan::with(['user', 'book'])
            ->where('status', ReadingPlanStatus::Reading)
            ->whereDate('target_date', today())
            ->get();

        foreach ($dueDatePlans as $readingPlan) {
            if ($this->alreadyNotified($readingPlan, 'on_due_date')) {
                continue;
            }

            $readingPlan->user->notify(
                new ReadingPlanDeadlineNotification($readingPlan, 'on_due_date')
            );

            $notifiedCount++;
        }

        // 期限切れに変更
        $expiredCount = ReadingPlan::where('status', ReadingPlanStatus::Reading)
            ->whereDate('target_date', '<', today())
            ->update([
                'status' => ReadingPlanStatus::Expired,
            ]);

        // 期限3日後通知
        $threeDaysAfterPlans = ReadingPlan::with(['user', 'book'])
            ->where('status', ReadingPlanStatus::Expired)
            ->whereDate('target_date', today()->subDays(3))
            ->get();

        foreach ($threeDaysAfterPlans as $readingPlan) {
            if ($this->alreadyNotified($readingPlan, 'three_days_after')) {
                continue;
            }

            $readingPlan->user->notify(
                new ReadingPlanDeadlineNotification($readingPlan, 'three_days_after')
            );

            $notifiedCount++;
        }

        $this->info("{$notifiedCount}件の通知を送信しました。");
        $this->info("{$expiredCount}件の読書計画を期限切れに変更しました。");

        return Command::SUCCESS;
    }

    private function alreadyNotified(ReadingPlan $readingPlan, string $timing): bool
    {
        return $readingPlan->user->notifications()
            ->where('type', ReadingPlanDeadlineNotification::class)
            ->where('data->reading_plan_id', $readingPlan->id)
            ->where('data->timing', $timing)
            ->exists();
    }
}
