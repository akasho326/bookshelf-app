<?php

namespace Database\Seeders;

use App\Enums\ReadingPlanStatus;
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

        $books = Book::take(7)->get();

        if (! $mainUser || ! $otherUser || $books->count() < 7) {
            return;
        }

        $scenarios = [

            // シナリオ1：期限3日前・読書中
            // 3日前通知の確認用。
            [
                'user_id' => $mainUser->id,
                'book_id' => $books[0]->id,
                'target_date' => today()->addDays(3),
                'status' => ReadingPlanStatus::Reading,
                'completed_at' => null,
            ],

            // シナリオ2：今日が期限・読書中
            // 当日通知の確認用。
            [
                'user_id' => $mainUser->id,
                'book_id' => $books[1]->id,
                'target_date' => today(),
                'status' => ReadingPlanStatus::Reading,
                'completed_at' => null,
            ],

            // シナリオ3：期限切れ・読書中
            // 日次バッチによるExpiredへの自動更新確認用。
            [
                'user_id' => $mainUser->id,
                'book_id' => $books[2]->id,
                'target_date' => today()->subDay(),
                'status' => ReadingPlanStatus::Reading,
                'completed_at' => null,
            ],

            // シナリオ4：期限切れ（Expired）
            // Expired状態・期限切れ3日後通知の確認用。
            [
                'user_id' => $mainUser->id,
                'book_id' => $books[3]->id,
                'target_date' => today()->subDays(3),
                'status' => ReadingPlanStatus::Expired,
                'completed_at' => null,
            ],

            // シナリオ5：読了済み
            // 読了表示・completed_at・読書レポート集計の確認用。
            [
                'user_id' => $mainUser->id,
                'book_id' => $books[4]->id,
                'target_date' => today()->subDays(5),
                'status' => ReadingPlanStatus::Completed,
                'completed_at' => now()->subDays(2),
            ],

            // シナリオ6：別ユーザーの読書予定
            // 認可確認用。編集・削除できないことの確認用。
            [
                'user_id' => $otherUser->id,
                'book_id' => $books[5]->id,
                'target_date' => today()->addDays(2),
                'status' => ReadingPlanStatus::Reading,
                'completed_at' => null,
            ],

            // シナリオ7：別ユーザーの読了済み
            // 認可・読書レポート集計対象外の確認用。
            [
                'user_id' => $otherUser->id,
                'book_id' => $books[6]->id,
                'target_date' => today()->subDays(10),
                'status' => ReadingPlanStatus::Completed,
                'completed_at' => now()->subDay(),
            ],
        ];

        foreach ($scenarios as $scenario) {
            ReadingPlan::create($scenario);
        }
    }
}
