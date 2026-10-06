<?php

namespace Database\Factories;

use App\Enums\GameSessionStatus;
use App\Models\GameSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameSession>
 */
class GameSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startedAt = now()->subMinute();

        return [
            'user_id' => User::factory(),
            'status' => GameSessionStatus::Active,
            'grid' => [
                ['J', 'U', 'R', 'O', 'S'],
                ['A', 'B', 'C', 'D', 'E'],
                ['F', 'G', 'H', 'I', 'K'],
            ],
            'rows' => 3,
            'columns' => 5,
            'total_words' => 1,
            'found_words_count' => 0,
            'generation_config' => [
                'rows' => 3,
                'columns' => 5,
                'word_count' => 1,
            ],
            'started_at' => $startedAt,
            'finished_at' => null,
            'duration_seconds' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => GameSessionStatus::Active,
            'found_words_count' => 0,
            'finished_at' => null,
            'duration_seconds' => null,
        ]);
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes): array {
            $startedAt = $attributes['started_at'] ?? now()->subMinute();

            return [
                'status' => GameSessionStatus::Completed,
                'found_words_count' => $attributes['total_words'] ?? 1,
                'finished_at' => $startedAt->copy()->addMinute(),
                'duration_seconds' => 60,
            ];
        });
    }

    public function abandoned(): static
    {
        return $this->state(function (array $attributes): array {
            $startedAt = $attributes['started_at'] ?? now()->subMinute();

            return [
                'status' => GameSessionStatus::Abandoned,
                'finished_at' => $startedAt->copy()->addSeconds(30),
                'duration_seconds' => 30,
            ];
        });
    }
}
