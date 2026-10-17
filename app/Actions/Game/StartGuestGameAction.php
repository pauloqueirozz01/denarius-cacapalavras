<?php

namespace App\Actions\Game;

use App\Data\WordPlacement;
use App\Enums\GameSessionStatus;
use App\Exceptions\ActiveGameSessionExistsException;
use App\Exceptions\InvalidGameSessionSnapshotException;
use App\Models\GameSession;
use App\Models\GameSessionWord;
use App\Services\GameSessionSnapshotValidator;
use App\Services\GuestGameStore;
use App\Services\ScoreCalculator;
use App\Services\WordSearchGeneratorService;
use Illuminate\Database\Eloquent\Collection;

class StartGuestGameAction
{
    public function __construct(
        private readonly WordSearchGeneratorService $wordSearchGenerator,
        private readonly GameSessionSnapshotValidator $snapshotValidator,
        private readonly ScoreCalculator $scoreCalculator,
        private readonly GuestGameStore $guestGameStore,
    ) {}

    /**
     * @throws ActiveGameSessionExistsException
     * @throws InvalidGameSessionSnapshotException
     */
    public function execute(): GameSession
    {
        if ($this->guestGameStore->current()?->isActive() === true) {
            throw ActiveGameSessionExistsException::forGuest();
        }

        $result = $this->wordSearchGenerator->generate();
        $this->snapshotValidator->validate($result);

        $startedAt = now();
        $session = new GameSession;
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

        $words = array_map(
            static function (WordPlacement $placement, int $index): GameSessionWord {
                $word = new GameSessionWord;
                $word->id = $index + 1;
                $word->financial_term_id = $placement->financialTermId;
                $word->original_term = $placement->originalTerm;
                $word->normalized_term = $placement->normalizedTerm;
                $word->start_row = $placement->start->row;
                $word->start_column = $placement->start->column;
                $word->end_row = $placement->end->row;
                $word->end_column = $placement->end->column;
                $word->direction = $placement->direction;
                $word->is_found = false;
                $word->found_at = null;

                return $word;
            },
            $result->placements,
            array_keys($result->placements),
        );
        $session->setRelation('words', new Collection($words));

        $this->guestGameStore->store($session);

        return $session;
    }
}
