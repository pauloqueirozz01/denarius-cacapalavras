<?php

namespace Tests\Feature\Actions\Game;

use App\Actions\Game\FindGameSessionWordAction;
use App\Enums\GameSessionStatus;
use App\Enums\WordDirection;
use App\Exceptions\InvalidGameSessionStateException;
use App\Exceptions\InvalidWordSelectionException;
use App\Models\FinancialTerm;
use App\Models\GameSession;
use App\Models\GameSessionWord;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class FindGameSessionWordActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_marks_a_valid_forward_selection_as_found(): void
    {
        $this->travelTo('2026-10-06 12:00:00');
        [$user, $session, $word] = $this->pendingWordSession(totalWords: 2);

        $result = app(FindGameSessionWordAction::class)->execute($user, $session, 0, 0, 0, 4);

        $this->assertTrue($result->wasNewlyFound);
        $this->assertFalse($result->completedSession);
        $this->assertTrue($result->word->is_found);
        $this->assertSame('2026-10-06 12:00:00', $result->word->found_at->toDateTimeString());
        $this->assertSame(1, $result->session->found_words_count);
        $this->assertSame(100, $result->pointsAwarded);
        $this->assertSame(100, $result->wordPoints);
        $this->assertSame(0, $result->completionBonus);
        $this->assertSame(0, $result->speedBonus);
        $this->assertSame(100, $result->session->score);
        $this->assertSame(GameSessionStatus::Active, $result->session->status);
        $this->assertDatabaseHas('game_session_words', [
            'id' => $word->id,
            'is_found' => true,
        ]);
    }

    public function test_accepts_the_same_placement_selected_in_reverse(): void
    {
        [$user, $session] = $this->pendingWordSession(totalWords: 2);

        $result = app(FindGameSessionWordAction::class)->execute($user, $session, 0, 4, 0, 0);

        $this->assertTrue($result->wasNewlyFound);
        $this->assertSame('JUROS', $result->word->normalized_term);
        $this->assertSame(1, $result->session->found_words_count);
    }

    public function test_rejects_a_selection_that_does_not_match_any_placement(): void
    {
        [$user, $session, $word] = $this->pendingWordSession(totalWords: 2);

        try {
            app(FindGameSessionWordAction::class)->execute($user, $session, 1, 0, 1, 4);
            $this->fail('Era esperada uma rejeição para a seleção inexistente.');
        } catch (InvalidWordSelectionException $exception) {
            $this->assertStringContainsString('não corresponde', $exception->getMessage());
        }

        $this->assertFalse($word->refresh()->is_found);
        $this->assertSame(0, $session->refresh()->found_words_count);
        $this->assertSame(0, $session->score);
        $this->assertSame(GameSessionStatus::Active, $session->status);
    }

    public function test_rejects_coordinates_outside_the_grid(): void
    {
        [$user, $session] = $this->pendingWordSession(totalWords: 2);

        $this->expectException(InvalidWordSelectionException::class);
        $this->expectExceptionMessage('fora dos limites');

        app(FindGameSessionWordAction::class)->execute($user, $session, -1, 0, 0, 4);
    }

    public function test_duplicate_selection_is_idempotent_even_with_a_stale_session_instance(): void
    {
        [$user, $session, $word] = $this->pendingWordSession(totalWords: 2);
        $action = app(FindGameSessionWordAction::class);

        $firstResult = $action->execute($user, $session, 0, 0, 0, 4);
        $firstFoundAt = $firstResult->word->found_at->toISOString();
        $secondResult = $action->execute($user, $session, 0, 0, 0, 4);

        $this->assertTrue($firstResult->wasNewlyFound);
        $this->assertFalse($secondResult->wasNewlyFound);
        $this->assertFalse($secondResult->completedSession);
        $this->assertSame(0, $secondResult->pointsAwarded);
        $this->assertSame(1, $secondResult->session->found_words_count);
        $this->assertSame(100, $secondResult->session->score);
        $this->assertSame($firstFoundAt, $word->refresh()->found_at->toISOString());
        $this->assertDatabaseCount('game_session_words', 1);
    }

    public function test_finding_the_last_word_completes_the_session_with_server_duration(): void
    {
        $this->travelTo('2026-10-06 12:01:05');
        [$user, $session] = $this->pendingWordSession(
            totalWords: 1,
            startedAt: now()->subSeconds(65),
        );

        $result = app(FindGameSessionWordAction::class)->execute($user, $session, 0, 0, 0, 4);

        $this->assertTrue($result->completedSession);
        $this->assertSame(GameSessionStatus::Completed, $result->session->status);
        $this->assertSame(1, $result->session->found_words_count);
        $this->assertSame('2026-10-06 12:01:05', $result->session->finished_at->toDateTimeString());
        $this->assertSame(65, $result->session->duration_seconds);
        $this->assertSame(1100, $result->pointsAwarded);
        $this->assertSame(100, $result->wordPoints);
        $this->assertSame(500, $result->completionBonus);
        $this->assertSame(500, $result->speedBonus);
        $this->assertSame(1100, $result->session->score);
    }

    public function test_recalculates_the_authoritative_counter_from_found_word_rows(): void
    {
        [$user, $session, $pendingWord] = $this->pendingWordSession(totalWords: 2);
        $foundTerm = FinancialTerm::factory()->active()->create([
            'term' => 'Pix',
            'description' => 'Pix.',
        ]);
        GameSessionWord::factory()
            ->for($session)
            ->for($foundTerm, 'financialTerm')
            ->found()
            ->create([
                'original_term' => 'Pix',
                'normalized_term' => 'PIX',
                'start_row' => 1,
                'start_column' => 0,
                'end_row' => 1,
                'end_column' => 2,
            ]);
        $session->score = 100;
        $session->save();

        $result = app(FindGameSessionWordAction::class)->execute(
            $user,
            $session,
            $pendingWord->start_row,
            $pendingWord->start_column,
            $pendingWord->end_row,
            $pendingWord->end_column,
        );

        $this->assertSame(2, $result->session->found_words_count);
        $this->assertSame(GameSessionStatus::Completed, $result->session->status);
        $this->assertSame(1200, $result->session->score);
    }

    public function test_completed_session_rejects_word_selection(): void
    {
        $user = User::factory()->create();
        $session = GameSession::factory()->for($user)->completed()->create();
        $word = $this->createWord($session, found: true);

        $this->expectException(InvalidGameSessionStateException::class);
        $this->expectExceptionMessage("estado 'completed'");

        app(FindGameSessionWordAction::class)->execute(
            $user,
            $session,
            $word->start_row,
            $word->start_column,
            $word->end_row,
            $word->end_column,
        );
    }

    public function test_abandoned_session_rejects_word_selection(): void
    {
        $user = User::factory()->create();
        $session = GameSession::factory()->for($user)->abandoned()->create();
        $word = $this->createWord($session);

        $this->expectException(InvalidGameSessionStateException::class);
        $this->expectExceptionMessage("estado 'abandoned'");

        app(FindGameSessionWordAction::class)->execute(
            $user,
            $session,
            $word->start_row,
            $word->start_column,
            $word->end_row,
            $word->end_column,
        );
    }

    public function test_user_cannot_mark_a_word_in_another_users_session(): void
    {
        [$owner, $session, $word] = $this->pendingWordSession(totalWords: 2);
        $otherUser = User::factory()->create();

        try {
            app(FindGameSessionWordAction::class)->execute(
                $otherUser,
                $session,
                $word->start_row,
                $word->start_column,
                $word->end_row,
                $word->end_column,
            );
            $this->fail('Era esperada uma falha de autorização.');
        } catch (AuthorizationException) {
            $this->assertFalse($word->refresh()->is_found);
            $this->assertSame(0, $owner->gameSessions()->findOrFail($session->id)->found_words_count);
            $this->assertSame(0, $session->refresh()->score);
        }
    }

    public function test_uses_the_scoring_snapshot_saved_with_the_session(): void
    {
        [$user, $session] = $this->pendingWordSession(totalWords: 2, scoringConfiguration: [
            'points_per_word' => 75,
            'completion_bonus' => 400,
            'speed_bonus_tiers' => [
                ['up_to_seconds' => 120, 'points' => 250],
            ],
        ]);

        $result = app(FindGameSessionWordAction::class)->execute($user, $session, 0, 0, 0, 4);

        $this->assertSame(75, $result->pointsAwarded);
        $this->assertSame(75, $result->session->score);
    }

    public function test_rolls_back_the_found_word_when_scoring_configuration_is_invalid(): void
    {
        [$user, $session, $word] = $this->pendingWordSession(totalWords: 2, scoringConfiguration: [
            'points_per_word' => -1,
            'completion_bonus' => 500,
            'speed_bonus_tiers' => [
                ['up_to_seconds' => 120, 'points' => 500],
            ],
        ]);

        try {
            app(FindGameSessionWordAction::class)->execute($user, $session, 0, 0, 0, 4);
            $this->fail('Era esperada uma rejeição para a configuração de pontuação inválida.');
        } catch (InvalidArgumentException) {
            $this->assertFalse($word->refresh()->is_found);
            $this->assertNull($word->found_at);
            $this->assertSame(0, $session->refresh()->found_words_count);
            $this->assertSame(0, $session->score);
        }
    }

    /**
     * @param  array<string, mixed>|null  $scoringConfiguration
     * @return array{User, GameSession, GameSessionWord}
     */
    private function pendingWordSession(
        int $totalWords,
        ?CarbonInterface $startedAt = null,
        ?array $scoringConfiguration = null,
    ): array {
        $user = User::factory()->create();
        $session = GameSession::factory()->for($user)->active()->create([
            'total_words' => $totalWords,
            'started_at' => $startedAt ?? now()->subMinute(),
            'generation_config' => [
                'rows' => 3,
                'columns' => 5,
                'word_count' => $totalWords,
                'scoring' => $scoringConfiguration ?? config('denarius.scoring'),
            ],
        ]);
        $word = $this->createWord($session);

        return [$user, $session, $word];
    }

    private function createWord(GameSession $session, bool $found = false): GameSessionWord
    {
        $term = FinancialTerm::factory()->active()->create([
            'term' => 'Juros',
            'description' => 'Juros.',
        ]);
        $factory = GameSessionWord::factory()
            ->for($session)
            ->for($term, 'financialTerm');

        return ($found ? $factory->found() : $factory->pending())->create([
            'original_term' => 'Juros',
            'normalized_term' => 'JUROS',
            'start_row' => 0,
            'start_column' => 0,
            'end_row' => 0,
            'end_column' => 4,
            'direction' => WordDirection::Right,
        ]);
    }
}
