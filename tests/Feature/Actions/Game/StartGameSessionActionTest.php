<?php

namespace Tests\Feature\Actions\Game;

use App\Actions\Game\StartGameSessionAction;
use App\Data\SelectedFinancialTerm;
use App\Data\WordCoordinate;
use App\Data\WordPlacement;
use App\Data\WordSearchResult;
use App\Enums\GameSessionStatus;
use App\Enums\WordDirection;
use App\Exceptions\ActiveGameSessionExistsException;
use App\Exceptions\InvalidGameSessionSnapshotException;
use App\Models\FinancialTerm;
use App\Models\GameSession;
use App\Models\User;
use App\Services\GameSessionSnapshotValidator;
use App\Services\WordSearchGeneratorService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Random\Engine\Mt19937;
use Random\Randomizer;
use Tests\TestCase;

class StartGameSessionActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_an_active_session_with_an_atomic_snapshot(): void
    {
        $this->travelTo('2026-10-06 12:00:00');
        $user = User::factory()->participant()->create();
        [$credit, $interest] = $this->financialTerms();
        $result = $this->resultFor($credit, $interest);

        $session = $this->actionReturning($result)->execute(
            user: $user,
            rows: 2,
            columns: 7,
            wordCount: 2,
        );

        $this->assertModelExists($session);
        $this->assertSame($user->id, $session->user_id);
        $this->assertSame(GameSessionStatus::Active, $session->status);
        $this->assertSame($result->grid, $session->grid);
        $this->assertSame(2, $session->rows);
        $this->assertSame(7, $session->columns);
        $this->assertSame(2, $session->total_words);
        $this->assertSame(0, $session->found_words_count);
        $this->assertSame('2026-10-06 12:00:00', $session->started_at->toDateTimeString());
        $this->assertNull($session->finished_at);
        $this->assertNull($session->duration_seconds);
        $this->assertSame(['rows' => 2, 'columns' => 7, 'word_count' => 2], $session->generation_config);
        $this->assertCount(2, $session->words);
        $this->assertDatabaseCount('game_session_words', 2);
    }

    public function test_persists_every_placement_as_an_auditable_word_snapshot(): void
    {
        $user = User::factory()->create();
        [$credit, $interest] = $this->financialTerms();
        $result = $this->resultFor($credit, $interest);

        $session = $this->actionReturning($result)->execute($user, rows: 2, columns: 7, wordCount: 2);
        $words = $session->words->keyBy('normalized_term');

        $creditSnapshot = $words->get('CREDITO');
        $this->assertSame($credit->id, $creditSnapshot->financial_term_id);
        $this->assertSame('Crédito', $creditSnapshot->original_term);
        $this->assertSame(0, $creditSnapshot->start_row);
        $this->assertSame(0, $creditSnapshot->start_column);
        $this->assertSame(0, $creditSnapshot->end_row);
        $this->assertSame(6, $creditSnapshot->end_column);
        $this->assertSame(WordDirection::Right, $creditSnapshot->direction);
        $this->assertFalse($creditSnapshot->is_found);
        $this->assertNull($creditSnapshot->found_at);

        $interestSnapshot = $words->get('JUROS');
        $this->assertSame($interest->id, $interestSnapshot->financial_term_id);
        $this->assertSame('Juros', $interestSnapshot->original_term);
        $this->assertSame(1, $interestSnapshot->start_row);
        $this->assertSame(0, $interestSnapshot->start_column);
        $this->assertSame(1, $interestSnapshot->end_row);
        $this->assertSame(4, $interestSnapshot->end_column);
    }

    public function test_real_generator_creates_a_persisted_session_from_active_catalog(): void
    {
        $user = User::factory()->create();
        $this->financialTerms();
        FinancialTerm::factory()->active()->create([
            'term' => 'Ação',
            'description' => 'Ação.',
        ]);
        $generator = new WordSearchGeneratorService(new Randomizer(new Mt19937(5050)));
        $action = new StartGameSessionAction($generator, new GameSessionSnapshotValidator);

        $session = $action->execute($user, rows: 10, columns: 10, wordCount: 3);

        $this->assertSame(3, $session->total_words);
        $this->assertCount(3, $session->words);
        $this->assertSame($session->total_words, $session->words()->count());
        $this->assertSame(10, count($session->grid));
        $this->assertSame(10, count($session->grid[0]));
    }

    public function test_rejects_a_second_active_session_for_the_same_user(): void
    {
        $user = User::factory()->create();
        GameSession::factory()->for($user)->active()->create();
        $generator = Mockery::mock(WordSearchGeneratorService::class);
        $generator->shouldNotReceive('generate');
        $action = new StartGameSessionAction($generator, new GameSessionSnapshotValidator);

        try {
            $action->execute($user);
            $this->fail('Era esperada uma rejeição para a segunda partida ativa.');
        } catch (ActiveGameSessionExistsException $exception) {
            $this->assertStringContainsString('já possui uma partida ativa', $exception->getMessage());
        }

        $this->assertDatabaseCount('game_sessions', 1);
    }

    public function test_rolls_back_the_session_when_word_persistence_fails(): void
    {
        $user = User::factory()->create();
        [$credit, $interest] = $this->financialTerms();
        $result = $this->resultFor($credit, $interest, secondFinancialTermId: 999999);

        try {
            $this->actionReturning($result)->execute($user, rows: 2, columns: 7, wordCount: 2);
            $this->fail('Era esperada uma falha de integridade referencial.');
        } catch (QueryException) {
            $this->assertDatabaseCount('game_sessions', 0);
            $this->assertDatabaseCount('game_session_words', 0);
        }
    }

    public function test_rejects_a_snapshot_that_does_not_match_the_grid(): void
    {
        $user = User::factory()->create();
        [$credit, $interest] = $this->financialTerms();
        $validResult = $this->resultFor($credit, $interest);
        $invalidGrid = $validResult->grid;
        $invalidGrid[0][0] = 'X';
        $invalidResult = new WordSearchResult(
            grid: $invalidGrid,
            rows: $validResult->rows,
            columns: $validResult->columns,
            placements: $validResult->placements,
            selectedTerms: $validResult->selectedTerms,
        );

        try {
            $this->actionReturning($invalidResult)->execute($user, rows: 2, columns: 7, wordCount: 2);
            $this->fail('Era esperada uma rejeição para o snapshot inconsistente.');
        } catch (InvalidGameSessionSnapshotException $exception) {
            $this->assertStringContainsString('não pode ser reconstruído', $exception->getMessage());
        }

        $this->assertDatabaseCount('game_sessions', 0);
    }

    /**
     * @return array{FinancialTerm, FinancialTerm}
     */
    private function financialTerms(): array
    {
        return [
            FinancialTerm::factory()->active()->create([
                'term' => 'Crédito',
                'description' => 'Crédito.',
            ]),
            FinancialTerm::factory()->active()->create([
                'term' => 'Juros',
                'description' => 'Juros.',
            ]),
        ];
    }

    private function resultFor(
        FinancialTerm $credit,
        FinancialTerm $interest,
        ?int $secondFinancialTermId = null,
    ): WordSearchResult {
        $resolvedSecondId = $secondFinancialTermId ?? $interest->id;

        return new WordSearchResult(
            grid: [
                ['C', 'R', 'E', 'D', 'I', 'T', 'O'],
                ['J', 'U', 'R', 'O', 'S', 'A', 'B'],
            ],
            rows: 2,
            columns: 7,
            placements: [
                new WordPlacement(
                    financialTermId: $credit->id,
                    originalTerm: 'Crédito',
                    normalizedTerm: 'CREDITO',
                    start: new WordCoordinate(0, 0),
                    end: new WordCoordinate(0, 6),
                    direction: WordDirection::Right,
                ),
                new WordPlacement(
                    financialTermId: $resolvedSecondId,
                    originalTerm: 'Juros',
                    normalizedTerm: 'JUROS',
                    start: new WordCoordinate(1, 0),
                    end: new WordCoordinate(1, 4),
                    direction: WordDirection::Right,
                ),
            ],
            selectedTerms: [
                new SelectedFinancialTerm($credit->id, 'Crédito', 'CREDITO'),
                new SelectedFinancialTerm($resolvedSecondId, 'Juros', 'JUROS'),
            ],
        );
    }

    private function actionReturning(WordSearchResult $result): StartGameSessionAction
    {
        $generator = Mockery::mock(WordSearchGeneratorService::class);
        $generator->shouldReceive('generate')->once()->andReturn($result);

        return new StartGameSessionAction($generator, new GameSessionSnapshotValidator);
    }
}
