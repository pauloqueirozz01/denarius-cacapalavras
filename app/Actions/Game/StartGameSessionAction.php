<?php

namespace App\Actions\Game;

use App\Data\WordPlacement;
use App\Enums\GameSessionStatus;
use App\Exceptions\ActiveGameSessionExistsException;
use App\Exceptions\InvalidGameSessionSnapshotException;
use App\Models\GameSession;
use App\Models\GameSessionWord;
use App\Models\User;
use App\Services\GameSessionSnapshotValidator;
use App\Services\ScoreCalculator;
use App\Services\WordSearchGeneratorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StartGameSessionAction
{
    public function __construct(
        private readonly WordSearchGeneratorService $wordSearchGenerator,
        private readonly GameSessionSnapshotValidator $snapshotValidator,
        private readonly ScoreCalculator $scoreCalculator,
    ) {}

    /**
     * @throws ActiveGameSessionExistsException
     * @throws InvalidGameSessionSnapshotException
     */
    public function execute(
        User $user,
        ?int $rows = null,
        ?int $columns = null,
        ?int $wordCount = null,
    ): GameSession {
        Gate::forUser($user)->authorize('create', GameSession::class);

        if ($this->userHasActiveSession($user)) {
            throw ActiveGameSessionExistsException::forUser((int) $user->getKey());
        }

        $result = $this->wordSearchGenerator->generate(
            rows: $rows,
            columns: $columns,
            wordCount: $wordCount,
        );
        $this->snapshotValidator->validate($result);

        return DB::transaction(function () use ($user, $result): GameSession {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($this->userHasActiveSession($lockedUser)) {
                throw ActiveGameSessionExistsException::forUser((int) $lockedUser->getKey());
            }

            $startedAt = now();
            $session = new GameSession;
            $session->user_id = $lockedUser->getKey();
            $session->status = GameSessionStatus::Active;
            $session->grid = $result->grid;
            $session->rows = $result->rows;
            $session->columns = $result->columns;
            $session->total_words = count($result->placements);
            $session->found_words_count = 0;
            $session->score = 0;
            $session->generation_config = [
                'rows' => $result->rows,
                'columns' => $result->columns,
                'word_count' => count($result->placements),
                'scoring' => $this->scoreCalculator->configuration(),
            ];
            $session->started_at = $startedAt;
            $session->save();

            $wordRows = array_map(
                static fn (WordPlacement $placement): array => [
                    'game_session_id' => $session->getKey(),
                    'financial_term_id' => $placement->financialTermId,
                    'original_term' => $placement->originalTerm,
                    'normalized_term' => $placement->normalizedTerm,
                    'start_row' => $placement->start->row,
                    'start_column' => $placement->start->column,
                    'end_row' => $placement->end->row,
                    'end_column' => $placement->end->column,
                    'direction' => $placement->direction->value,
                    'is_found' => false,
                    'found_at' => null,
                    'created_at' => $startedAt,
                    'updated_at' => $startedAt,
                ],
                $result->placements,
            );

            if (! GameSessionWord::query()->insert($wordRows)) {
                throw InvalidGameSessionSnapshotException::invalid(
                    'não foi possível persistir todas as palavras.',
                );
            }

            return $session->load('words');
        }, 3);
    }

    private function userHasActiveSession(User $user): bool
    {
        return GameSession::query()
            ->whereBelongsTo($user)
            ->active()
            ->exists();
    }
}
