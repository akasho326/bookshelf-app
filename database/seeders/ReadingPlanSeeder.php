<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReadingPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $mainUser = User::first();
        $otherUser = User::skip(1)->first();

        $books = Book::take(8)->get();

        if (! $mainUser || ! $otherUser || $books->count() < 8) {
            return;
        }

        // シナリオ1：期限切れ・未着手
        // 通知対象や「期限を過ぎても読めていない予定」の確認用。
        ReadingPlan::create([
            'user_id' => $mainUser->id,
            'book_id' => $books[0]->id,
            'target_date' => today()->subDays(1),
            'status' => 'not_started',
            'completed_at' => null,
        ]);

        // シナリオ2：今日が期限・読書中
        // 今日読む予定として、一覧表示や当日通知の確認用。
        ReadingPlan::create([
            'user_id' => $mainUser->id,
            'book_id' => $books[1]->id,
            'target_date' => today(),
            'status' => 'reading',
            'completed_at' => null,
        ]);

        // シナリオ3：未来の予定・未着手
        // 通知対象外の通常予定として表示確認用。
        ReadingPlan::create([
            'user_id' => $mainUser->id,
            'book_id' => $books[2]->id,
            'target_date' => today()->addDays(3),
            'status' => 'not_started',
            'completed_at' => null,
        ]);

        // シナリオ4：読了済み
        // 読了処理済みの表示、completed_at、読書レポート集計の確認用。
        ReadingPlan::create([
            'user_id' => $mainUser->id,
            'book_id' => $books[3]->id,
            'target_date' => today()->subDays(5),
            'status' => 'completed',
            'completed_at' => now()->subDays(2),
        ]);

        // シナリオ5：未来の予定・読書中
        // 読書中だが期限前の予定として、状態表示の確認用。
        ReadingPlan::create([
            'user_id' => $mainUser->id,
            'book_id' => $books[4]->id,
            'target_date' => today()->addDays(7),
            'status' => 'reading',
            'completed_at' => null,
        ]);

        // シナリオ6：期限切れ・読書中
        // 読書中のまま期限を過ぎた予定として、警告・通知確認用。
        ReadingPlan::create([
            'user_id' => $mainUser->id,
            'book_id' => $books[5]->id,
            'target_date' => today()->subDays(3),
            'status' => 'reading',
            'completed_at' => null,
        ]);

        // シナリオ7：別ユーザーの読書予定
        // 認可確認用。mainUserでログインしたときに編集・削除できないことを確認する。
        ReadingPlan::create([
            'user_id' => $otherUser->id,
            'book_id' => $books[6]->id,
            'target_date' => today()->addDays(2),
            'status' => 'not_started',
            'completed_at' => null,
        ]);

        // シナリオ8：別ユーザーの読了済み予定
        // 認可確認用。他ユーザーの読了データが自分のレポートに混ざらないことを確認する。
        ReadingPlan::create([
            'user_id' => $otherUser->id,
            'book_id' => $books[7]->id,
            'target_date' => today()->subDays(10),
            'status' => 'completed',
            'completed_at' => now()->subDays(1),
        ]);
    }
}
