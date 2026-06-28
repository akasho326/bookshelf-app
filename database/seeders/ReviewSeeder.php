<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('ja_JP');

        $users = User::all();
        $books = Book::all();

        if ($users->isEmpty() || $books->isEmpty()) {
            return;
        }

        $commentsByRating = [
            1 => [
                '期待していた内容とは少し違いました。',
                '説明が少なく、理解しづらい部分がありました。',
            ],
            2 => [
                '参考になる部分もありましたが、全体的には物足りなさを感じました。',
                '内容は悪くないものの、少し読みづらかったです。',
            ],
            3 => [
                '標準的な内容で、基本を確認するには良い一冊でした。',
                'ところどころ参考になる内容がありました。',
            ],
            4 => [
                '内容が整理されていて理解しやすかったです。',
                '具体例が多く、実践に活かしやすいと感じました。',
            ],
            5 => [
                'とても読みやすく、学びの多い一冊でした。',
                'もう一度読み返したいと思える内容でした。',
            ],
        ];

        foreach ($books as $book) {
            $reviewCount = $faker->numberBetween(2, 4);

            $reviewUsers = $users->shuffle()->take($reviewCount);

            foreach ($reviewUsers as $user) {
                $rating = $faker->numberBetween(1, 5);

                Review::create([
                    'book_id' => $book->id,
                    'user_id' => $user->id,
                    'rating' => $rating,
                    'comment' => $faker->randomElement($commentsByRating[$rating]),
                ]);
            }
        }
    }
}
