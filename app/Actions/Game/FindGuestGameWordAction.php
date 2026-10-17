<?php

namespace App\Actions\Game;

use App\Data\WordSelectionResult;
use App\Exceptions\InvalidGameSessionStateException;
use App\Exceptions\InvalidWordSelectionException;
use App\Models\GameSession;
use App\Models\GameSessionWord;
use App\Services\GuestGameStore;
use App\Services\ScoreCalculator;

class FindGuestGameWordAction
{
    public function __construct(
        private readonly ScoreCalculator $scoreCalculator,
        private readonly GuestGameStore $guestGameStore,
    ) {}

    /**
     * @throws InvalidGameSessionStateException
     * @throws InvalidWordSelectionException
     */
    public function execute(
        GameSession $session,
        int $startRow,
        int $startColumn,
        int $endRow,
        int $endColumn,
    ): WordSelectionResult {
        $session->assertActive();

        foreach ([[$startRow, $startColumn], [$endRow, $endColumn]] as [$row, $column]) {
            if ($row < 0 || $row >= $session->rows || $column < 0 || $column >= $session->columns) {
                throw InvalidWordSelectionException::outsideGrid();
            }
        }

        $word = $session->words->first(
            static fn (GameSessionWord $word): bool => (
                $word->start_row === $startRow
                && $word->start_column === $startColumn
                && $word->end_row === $endRow
                && $word->end_column === $endColumn
            ) || (
                $word->start_row === $endRow
                && $word->start_column === $endColumn
                && $word->end_row === $startRow
                && $word->end_column === $startColumn
            ),
        );

        if ($word === null) {
            throw InvalidWordSelectionException::notFound();
        }

        if ($word->is_found) {
            return new WordSelectionResult(
                session: $session,
                word: $word,
                wasNewlyFound: false,
                completedSession: false,
                pointsAwarded: 0,
                wordPoints: 0,
                completionBonus: 0,
                speedBonus: 0,
            );
        }

        $foundAt = now();
        $word->is_found = true;
        $word->found_at = $foundAt;

        $session->found_words_count = $session->words->where('is_found', true)->count();
        $completedSession = $session->found_words_count === $session->total_words;

        if ($completedSession) {
            $session->complete($foundAt);
        }

        $scoringConfiguration = $session->generation_config['scoring'] ?? null;
        $scoreCalculator = is_array($scoringConfiguration)
            ? new ScoreCalculator($scoringConfiguration)
            : $this->scoreCalculator;
        $scoreAward = $scoreCalculator->awardForFoundWord(
            completesSession: $completedSession,
            durationSeconds: $session->duration_seconds,
        );
        $session->score += $scoreAward->total();

        $this->guestGameStore->store($session);

        return new WordSelectionResult(
            session: $session,
            word: $word,
            wasNewlyFound: true,
            completedSession: $completedSession,
            pointsAwarded: $scoreAward->total(),
            wordPoints: $scoreAward->wordPoints,
            completionBonus: $scoreAward->completionBonus,
            speedBonus: $scoreAward->speedBonus,
        );
    }
}
