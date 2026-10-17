<?php

namespace App\Actions\Game;

use App\Data\WordSelectionResult;
use App\Exceptions\InvalidWordSelectionException;
use App\Models\GameSession;
use App\Models\GameSessionWord;
use App\Models\User;
use App\Services\CachedLeaderboard;
use App\Services\ScoreCalculator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class FindGameSessionWordAction
{
    public function __construct(
        private readonly ScoreCalculator $scoreCalculator,
    ) {}

    public function execute(
        User $user,
        GameSession $session,
        int $startRow,
        int $startColumn,
        int $endRow,
        int $endColumn,
    ): WordSelectionResult {
        Gate::forUser($user)->authorize('markWord', $session);

        $result = DB::transaction(function () use (
            $session,
            $startRow,
            $startColumn,
            $endRow,
            $endColumn,
        ): WordSelectionResult {
            $lockedSession = GameSession::query()
                ->whereKey($session->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedSession->assertActive();
            $this->assertCoordinatesInsideGrid(
                session: $lockedSession,
                startRow: $startRow,
                startColumn: $startColumn,
                endRow: $endRow,
                endColumn: $endColumn,
            );

            $word = $this->findMatchingWord(
                session: $lockedSession,
                startRow: $startRow,
                startColumn: $startColumn,
                endRow: $endRow,
                endColumn: $endColumn,
            );

            if ($word === null) {
                throw InvalidWordSelectionException::notFound();
            }

            if ($word->is_found) {
                return new WordSelectionResult(
                    session: $lockedSession,
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
            $word->save();

            $foundWordsCount = GameSessionWord::query()
                ->whereBelongsTo($lockedSession)
                ->where('is_found', true)
                ->count();
            $lockedSession->found_words_count = $foundWordsCount;
            $completedSession = $foundWordsCount === $lockedSession->total_words;

            if ($completedSession) {
                $lockedSession->complete($foundAt);
            }

            $scoreCalculator = $this->scoreCalculatorFor($lockedSession);
            $scoreAward = $scoreCalculator->awardForFoundWord(
                completesSession: $completedSession,
                durationSeconds: $lockedSession->duration_seconds,
            );
            $lockedSession->score += $scoreAward->total();

            $lockedSession->save();

            return new WordSelectionResult(
                session: $lockedSession,
                word: $word,
                wasNewlyFound: true,
                completedSession: $completedSession,
                pointsAwarded: $scoreAward->total(),
                wordPoints: $scoreAward->wordPoints,
                completionBonus: $scoreAward->completionBonus,
                speedBonus: $scoreAward->speedBonus,
            );
        }, 3);

        if ($result->completedSession) {
            CachedLeaderboard::invalidate();
        }

        return $result;
    }

    private function scoreCalculatorFor(GameSession $session): ScoreCalculator
    {
        $configuration = $session->generation_config['scoring'] ?? null;

        return is_array($configuration)
            ? new ScoreCalculator($configuration)
            : $this->scoreCalculator;
    }

    private function findMatchingWord(
        GameSession $session,
        int $startRow,
        int $startColumn,
        int $endRow,
        int $endColumn,
    ): ?GameSessionWord {
        return GameSessionWord::query()
            ->whereBelongsTo($session)
            ->where(function (Builder $query) use (
                $startRow,
                $startColumn,
                $endRow,
                $endColumn,
            ): void {
                $query->where(function (Builder $forward) use (
                    $startRow,
                    $startColumn,
                    $endRow,
                    $endColumn,
                ): void {
                    $forward
                        ->where('start_row', $startRow)
                        ->where('start_column', $startColumn)
                        ->where('end_row', $endRow)
                        ->where('end_column', $endColumn);
                })->orWhere(function (Builder $reverse) use (
                    $startRow,
                    $startColumn,
                    $endRow,
                    $endColumn,
                ): void {
                    $reverse
                        ->where('start_row', $endRow)
                        ->where('start_column', $endColumn)
                        ->where('end_row', $startRow)
                        ->where('end_column', $startColumn);
                });
            })
            ->lockForUpdate()
            ->first();
    }

    private function assertCoordinatesInsideGrid(
        GameSession $session,
        int $startRow,
        int $startColumn,
        int $endRow,
        int $endColumn,
    ): void {
        foreach ([[$startRow, $startColumn], [$endRow, $endColumn]] as [$row, $column]) {
            if ($row < 0 || $row >= $session->rows || $column < 0 || $column >= $session->columns) {
                throw InvalidWordSelectionException::outsideGrid();
            }
        }
    }
}
