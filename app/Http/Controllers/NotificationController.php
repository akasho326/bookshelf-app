<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * 通知一覧を表示する。
     */
    public function index(): View
    {
        $notifications = auth()->user()
            ->notifications()
            ->latest()
            ->paginate(10);

        return view('notifications.index', compact('notifications'));
    }

    /**
     * 通知を既読にする。
     */
    public function markAsRead(DatabaseNotification $notification): RedirectResponse
    {
        // 自分宛ての通知のみ既読にする
        abort_unless($notification->notifiable_id === auth()->id(), 403);

        $notification->markAsRead();

        return back()->with('success', '通知を既読にしました。');
    }
}
