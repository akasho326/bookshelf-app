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

    /**
     * 読書計画の期限通知送信と期限切れ更新を実行する。
     */
    public function handle(): int
    {
        // 今回送信した通知件数を記録
        $notifiedCount = 0;

        // 期限が3日後で、読書中の読書計画を取得
        $threeDaysBeforePlans = ReadingPlan::with(['user', 'book'])
            ->where('status', ReadingPlanStatus::Reading)
            ->whereDate('target_date', today()->addDays(3))
            ->get();

        // 期限3日前の通知を送信
        foreach ($threeDaysBeforePlans as $readingPlan) {
            // 同じ読書計画・通知タイミングで送信済みの場合は処理しない
            if ($this->alreadyNotified($readingPlan, 'three_days_before')) {
                continue;
            }

            $readingPlan->user->notify(
                new ReadingPlanDeadlineNotification($readingPlan, 'three_days_before')
            );

            $notifiedCount++;
        }

        // 期限が当日で、読書中の読書計画を取得
        $dueDatePlans = ReadingPlan::with(['user', 'book'])
            ->where('status', ReadingPlanStatus::Reading)
            ->whereDate('target_date', today())
            ->get();

        // 期限当日の通知を送信
        foreach ($dueDatePlans as $readingPlan) {
            // 同じ読書計画・通知タイミングで送信済みの場合は処理しない
            if ($this->alreadyNotified($readingPlan, 'on_due_date')) {
                continue;
            }

            $readingPlan->user->notify(
                new ReadingPlanDeadlineNotification($readingPlan, 'on_due_date')
            );

            $notifiedCount++;
        }

        // 期限を過ぎた読書中の計画を期限切れ状態へ更新
        $expiredCount = ReadingPlan::where('status', ReadingPlanStatus::Reading)
            ->whereDate('target_date', '<', today())
            ->update([
                'status' => ReadingPlanStatus::Expired,
            ]);

        // 期限から3日経過した、期限切れの読書計画を取得
        $threeDaysAfterPlans = ReadingPlan::with(['user', 'book'])
            ->where('status', ReadingPlanStatus::Expired)
            ->whereDate('target_date', today()->subDays(3))
            ->get();

        // 期限3日後の通知を送信
        foreach ($threeDaysAfterPlans as $readingPlan) {
            if ($this->alreadyNotified($readingPlan, 'three_days_after')) {
                continue;
            }

            $readingPlan->user->notify(
                new ReadingPlanDeadlineNotification($readingPlan, 'three_days_after')
            );

            $notifiedCount++;
        }

        // コマンド実行結果をコンソールへ表示
        $this->info("{$notifiedCount}件の通知を送信しました。");
        $this->info("{$expiredCount}件の読書計画を期限切れに変更しました。");

        return Command::SUCCESS;
    }

    /**
     * 指定した読書計画と通知タイミングで通知済みか確認する。
     */
    private function alreadyNotified(ReadingPlan $readingPlan, string $timing): bool
    {
        // 通知テーブルから同じ読書計画・タイミングの通知を検索
        return $readingPlan->user->notifications()
            ->where('type', ReadingPlanDeadlineNotification::class)
            ->where('data->reading_plan_id', $readingPlan->id)
            ->where('data->timing', $timing)
            ->exists();
    }
}
