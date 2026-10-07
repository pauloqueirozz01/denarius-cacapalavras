<?php

namespace App\Services;

use App\Data\LeaderboardEntry;
use App\Enums\GameSessionStatus;
use Illuminate\Database\Query\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator as PaginatorResolver;
use Illuminate\Support\Facades\DB;

class RankingService
{
    public function paginate(?int $perPage = null, ?int $page = null): LengthAwarePaginator
    {
        $maximumPerPage = max((int) config('denarius.leaderboard.maximum_per_page', 50), 1);
        $perPage = min(max($perPage ?? (int) config('denarius.leaderboard.per_page', 20), 1), $maximumPerPage);
        $currentPage = max($page ?? PaginatorResolver::resolveCurrentPage('page'), 1);
        $items = $this->rankedParticipantsQuery()
            ->forPage($currentPage, $perPage)
            ->get()
            ->map(fn (object $row): LeaderboardEntry => $this->entryFromRow($row));
        $paginator = new LengthAwarePaginator(
            items: $items,
            total: $this->totalParticipants(),
            perPage: $perPage,
            currentPage: $currentPage,
            options: [
                'path' => PaginatorResolver::resolveCurrentPath(),
                'pageName' => 'page',
            ],
        );

        return $paginator;
    }

    public function positionForUser(int $userId): ?LeaderboardEntry
    {
        $row = $this->rankedParticipantsQuery()
            ->where('user_id', $userId)
            ->first();

        return $row === null ? null : $this->entryFromRow($row);
    }

    public function positionForCompletedSession(int $sessionId): ?LeaderboardEntry
    {
        $userId = DB::table('game_sessions')
            ->where('id', $sessionId)
            ->where('status', GameSessionStatus::Completed->value)
            ->value('user_id');

        return $userId === null ? null : $this->positionForUser((int) $userId);
    }

    public function totalParticipants(): int
    {
        return (int) DB::table('game_sessions as sessions')
            ->join('users', 'users.id', '=', 'sessions.user_id')
            ->where('sessions.status', GameSessionStatus::Completed->value)
            ->where('users.role', 'participant')
            ->distinct()
            ->count('sessions.user_id');
    }

    private function rankedParticipantsQuery(): Builder
    {
        $rankedAttempts = DB::table('game_sessions as sessions')
            ->join('users', 'users.id', '=', 'sessions.user_id')
            ->where('sessions.status', GameSessionStatus::Completed->value)
            ->where('users.role', 'participant')
            ->select([
                'sessions.id as session_id',
                'sessions.user_id',
                'users.name',
                'sessions.score',
                'sessions.duration_seconds',
                'sessions.finished_at',
            ])
            ->selectRaw(<<<'SQL'
                ROW_NUMBER() OVER (
                    PARTITION BY sessions.user_id
                    ORDER BY
                        sessions.score DESC,
                        (sessions.duration_seconds IS NULL) ASC,
                        sessions.duration_seconds ASC,
                        (sessions.finished_at IS NULL) ASC,
                        sessions.finished_at ASC,
                        sessions.id ASC
                ) AS personal_attempt_rank
            SQL);

        $bestAttempts = DB::query()
            ->fromSub($rankedAttempts, 'ranked_attempts')
            ->where('personal_attempt_rank', 1)
            ->select([
                'session_id',
                'user_id',
                'name',
                'score',
                'duration_seconds',
                'finished_at',
            ]);

        $globallyRanked = DB::query()
            ->fromSub($bestAttempts, 'best_attempts')
            ->select([
                'session_id',
                'user_id',
                'name',
                'score',
                'duration_seconds',
                'finished_at',
            ])
            ->selectRaw(<<<'SQL'
                ROW_NUMBER() OVER (
                    ORDER BY
                        score DESC,
                        (duration_seconds IS NULL) ASC,
                        duration_seconds ASC,
                        (finished_at IS NULL) ASC,
                        finished_at ASC,
                        session_id ASC
                ) AS position
            SQL);

        return DB::query()
            ->fromSub($globallyRanked, 'leaderboard')
            ->select([
                'position',
                'user_id',
                'session_id',
                'name',
                'score',
                'duration_seconds',
                'finished_at',
            ])
            ->orderBy('position');
    }

    private function entryFromRow(object $row): LeaderboardEntry
    {
        return new LeaderboardEntry(
            position: (int) $row->position,
            userId: (int) $row->user_id,
            sessionId: (int) $row->session_id,
            name: (string) $row->name,
            score: (int) $row->score,
            durationSeconds: $row->duration_seconds === null ? null : (int) $row->duration_seconds,
            finishedAt: $row->finished_at === null ? null : (string) $row->finished_at,
        );
    }
}
