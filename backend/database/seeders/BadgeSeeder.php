<?php

namespace Database\Seeders;

use App\Models\Badge;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BadgeSeeder extends Seeder
{
    public function run(): void
    {
        $badges = [
            [
                'code'        => 'first_chapter',
                'name'        => 'First Step',
                'description' => 'Complete your first chapter.',
                'icon'        => '📖',
                'criteria'    => ['type' => 'chapter_count', 'threshold' => 1],
            ],
            [
                'code'        => 'five_chapters',
                'name'        => 'Committed Learner',
                'description' => 'Complete 5 chapters.',
                'icon'        => '🎯',
                'criteria'    => ['type' => 'chapter_count', 'threshold' => 5],
            ],
            [
                'code'        => 'perfect_score',
                'name'        => 'Perfectionist',
                'description' => 'Score 100% on any quiz.',
                'icon'        => '💯',
                'criteria'    => ['type' => 'perfect_score', 'threshold' => 100],
            ],
            [
                'code'        => 'streak_3',
                'name'        => 'On a Roll',
                'description' => 'Maintain a 3-day learning streak.',
                'icon'        => '🔥',
                'criteria'    => ['type' => 'streak_days', 'threshold' => 3],
            ],
            [
                'code'        => 'streak_7',
                'name'        => 'Week Warrior',
                'description' => 'Maintain a 7-day learning streak.',
                'icon'        => '⚡',
                'criteria'    => ['type' => 'streak_days', 'threshold' => 7],
            ],
            [
                'code'        => 'streak_30',
                'name'        => 'Unstoppable',
                'description' => 'Maintain a 30-day learning streak.',
                'icon'        => '🏆',
                'criteria'    => ['type' => 'streak_days', 'threshold' => 30],
            ],
            [
                'code'        => 'book_complete',
                'name'        => 'Smart Adama Master',
                'description' => 'Complete the entire Smart Adama book.',
                'icon'        => '🌟',
                'criteria'    => ['type' => 'book_complete', 'threshold' => 1],
            ],
            [
                'code'        => 'quiz_ace',
                'name'        => 'Quiz Ace',
                'description' => 'Pass 10 quizzes.',
                'icon'        => '🎓',
                'criteria'    => ['type' => 'quiz_passed_count', 'threshold' => 10],
            ],
        ];

        foreach ($badges as $badge) {
            Badge::updateOrCreate(
                ['code' => $badge['code']],
                array_merge($badge, ['id' => (string) Str::uuid()])
            );
        }
    }
}
