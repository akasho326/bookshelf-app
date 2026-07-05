<?php

namespace App\Notifications;

use App\Models\ReadingPlan;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReadingPlanDeadlineNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        private readonly ReadingPlan $readingPlan,
        private readonly string $timing,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $title = match ($this->timing) {
            'three_days_before' => '読書期限3日前のお知らせ',
            'on_due_date' => '読書期限当日のお知らせ',
            'three_days_after' => '読書期限超過のお知らせ',
            default => '読書期限のお知らせ',
        };

        $body = match ($this->timing) {
            'three_days_before' => "「{$this->readingPlan->book->title}」の読書期限まであと3日です。",
            'on_due_date' => "「{$this->readingPlan->book->title}」の読書期限は今日です。",
            'three_days_after' => "「{$this->readingPlan->book->title}」の読書期限を3日過ぎています。",
            default => "「{$this->readingPlan->book->title}」の読書期限が近付いてます。",
        };

        return [
            'reading_plan_id' => $this->readingPlan->id,
            'book_id' => $this->readingPlan->book_id,
            'book_title' => $this->readingPlan->book->title,
            'target_date' => $this->readingPlan->target_date->format('Y-m-d'),
            'timing' => $this->timing,
            'title' => $title,
            'body' => $body,
        ];
    }
}
