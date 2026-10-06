<?php

namespace Tests\Feature\Models;

use App\Enums\WordDirection;
use App\Exceptions\InvalidGameSessionSnapshotException;
use App\Exceptions\InvalidGameSessionStateException;
use App\Models\FinancialTerm;
use App\Models\GameSession;
use App\Models\GameSessionWord;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameSessionWordTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_state_and_exposes_snapshot_relationships(): void
    {
        $session = GameSession::factory()->active()->create();
        $term = FinancialTerm::factory()->active()->create([
            'term' => 'Crédito',
            'description' => 'Crédito.',
        ]);
        $word = $this->createWord($session, $term);

        $this->assertSame(WordDirection::Right, $word->direction);
        $this->assertFalse($word->is_found);
        $this->assertNull($word->found_at);
        $this->assertSame($session->id, $word->gameSession->id);
        $this->assertSame($term->id, $word->financialTerm->id);
    }

    public function test_snapshot_survives_term_renaming_and_deletion(): void
    {
        $session = GameSession::factory()->active()->create();
        $term = FinancialTerm::factory()->active()->create([
            'term' => 'Crédito',
            'description' => 'Crédito.',
        ]);
        $word = $this->createWord($session, $term);

        $term->update(['term' => 'Crédito Bancário']);

        $this->assertSame('Crédito', $word->refresh()->original_term);
        $this->assertSame('CREDITO', $word->normalized_term);
        $this->assertSame('Crédito Bancário', $word->financialTerm->term);

        $term->delete();

        $this->assertNull($word->refresh()->financial_term_id);
        $this->assertSame('Crédito', $word->original_term);
        $this->assertSame('CREDITO', $word->normalized_term);
    }

    public function test_placement_snapshot_cannot_be_changed(): void
    {
        $session = GameSession::factory()->active()->create();
        $term = FinancialTerm::factory()->active()->create();
        $word = $this->createWord($session, $term);
        $word->start_column = 1;

        $this->expectException(InvalidGameSessionSnapshotException::class);
        $this->expectExceptionMessage('imutável');

        $word->save();
    }

    public function test_found_word_cannot_return_to_pending(): void
    {
        $session = GameSession::factory()->active()->create();
        $term = FinancialTerm::factory()->active()->create();
        $word = $this->createWord($session, $term, found: true);
        $word->is_found = false;
        $word->found_at = null;

        $this->expectException(InvalidGameSessionStateException::class);

        $word->save();
    }

    public function test_server_controlled_word_fields_are_not_mass_assignable(): void
    {
        $this->expectException(MassAssignmentException::class);

        GameSessionWord::query()->create([
            'game_session_id' => 999,
            'original_term' => 'Fraude',
            'normalized_term' => 'FRAUDE',
            'start_row' => 0,
            'start_column' => 0,
            'end_row' => 0,
            'end_column' => 5,
            'direction' => WordDirection::Right,
            'is_found' => true,
            'found_at' => now(),
        ]);
    }

    private function createWord(
        GameSession $session,
        FinancialTerm $term,
        bool $found = false,
    ): GameSessionWord {
        $factory = GameSessionWord::factory()
            ->for($session)
            ->for($term, 'financialTerm');

        return ($found ? $factory->found() : $factory->pending())->create([
            'original_term' => 'Crédito',
            'normalized_term' => 'CREDITO',
            'start_row' => 0,
            'start_column' => 0,
            'end_row' => 0,
            'end_column' => 6,
            'direction' => WordDirection::Right,
        ]);
    }
}
