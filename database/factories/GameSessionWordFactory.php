<?php

namespace Database\Factories;

use App\Enums\WordDirection;
use App\Models\FinancialTerm;
use App\Models\GameSession;
use App\Models\GameSessionWord;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GameSessionWord>
 */
class GameSessionWordFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'game_session_id' => GameSession::factory(),
            'financial_term_id' => FinancialTerm::factory()->active()->state([
                'term' => 'Juros',
                'description' => 'Valor pago ou recebido pelo uso do dinheiro.',
            ]),
            'original_term' => 'Juros',
            'normalized_term' => 'JUROS',
            'start_row' => 0,
            'start_column' => 0,
            'end_row' => 0,
            'end_column' => 4,
            'direction' => WordDirection::Right,
            'is_found' => false,
            'found_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_found' => false,
            'found_at' => null,
        ]);
    }

    public function found(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_found' => true,
            'found_at' => now(),
        ]);
    }
}
