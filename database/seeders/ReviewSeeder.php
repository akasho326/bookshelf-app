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

        $comments = [
            'とても読みやすく、学びの多い一冊でした。',
            '内容が整理されていて理解しやすかったです。',
            '具体例が多く、実践に活かしやすいと感じました。',
            '少し難しい部分もありましたが、読み応えがありました。',
            'もう一度読み返したいと思える内容でした。',
        ];

        foreach ($books as $index => $book) {
            // 32件にするため、最後の書籍だけレビューを2件にする
            $reviewCount = $index === 10 ? 2 : 3;

            $reviewUsers = $users->shuffle()->take($reviewCount);

            foreach ($reviewUsers as $user) {
                Review::create([
                    'book_id' => $book->id,
                    'user_id' => $user->id,
                    'rating' => $faker->numberBetween(3, 5),
                    'comment' => $faker->randomElement($comments),
                ]);
            }
        }
    }
}
