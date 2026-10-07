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
        $offset = ($currentPage - 1) * $perPage;
        $items = $this->bestParticipantsQuery()
            ->orderByDesc('score')
            ->orderByRaw('(duration_seconds IS NULL) ASC')
            ->orderBy('duration_seconds')
            ->orderByRaw('(finished_at IS NULL) ASC')
            ->orderBy('finished_at')
            ->orderBy('session_id')
            ->forPage($currentPage, $perPage)
            ->get()
            ->values()
            ->map(fn (object $row, int $index): LeaderboardEntry => $this->entryFromRow($row, $offset + $index + 1));
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
        $row = $this->bestParticipantsQuery()
            ->where('sessions.user_id', $userId)
            ->first();

        return $row === null ? null : $this->entryFromRow($row, $this->participantsAheadOf($row) + 1);
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
        return (int) $this->bestParticipantsQuery()->count();
    }

    /**
     * Selects exactly one completed session per participant: the one for which no
     * other eligible session sorts ahead under the ranking's total order.
     */
    private function bestParticipantsQuery(): Builder
    {
        return DB::table('game_sessions as sessions')
            ->join('users', 'users.id', '=', 'sessions.user_id')
            ->where('sessions.status', GameSessionStatus::Completed->value)
            ->where('users.role', 'participant')
            ->whereNotExists(function (Builder $candidates): void {
                $candidates->selectRaw('1')
                    ->from('game_sessions as candidates')
                    ->whereColumn('candidates.user_id', 'sessions.user_id')
                    ->where('candidates.status', GameSessionStatus::Completed->value)
                    ->where(function (Builder $query): void {
                        $this->whereCandidateSessionAhead($query);
                    });
            })
            ->select([
                'sessions.id as session_id',
                'sessions.user_id',
                'users.name',
                'sessions.score',
                'sessions.duration_seconds',
                'sessions.finished_at',
            ]);
    }

    private function whereCandidateSessionAhead(Builder $query): void
    {
        $query->whereColumn('candidates.score', '>', 'sessions.score')
            ->orWhere(function (Builder $query): void {
                $this->whereSessionScoreEqual($query);
                $query->whereRaw('(candidates.duration_seconds IS NULL) < (sessions.duration_seconds IS NULL)');
            })
            ->orWhere(function (Builder $query): void {
                $this->whereSessionScoreEqual($query);
                $query->whereRaw('(candidates.duration_seconds IS NULL) = (sessions.duration_seconds IS NULL)')
                    ->whereColumn('candidates.duration_seconds', '<', 'sessions.duration_seconds');
            })
            ->orWhere(function (Builder $query): void {
                $this->whereSessionScoreEqual($query);
                $this->whereSessionDurationsEqual($query);
                $query->whereRaw('(candidates.finished_at IS NULL) < (sessions.finished_at IS NULL)');
            })
            ->orWhere(function (Builder $query): void {
                $this->whereSessionScoreEqual($query);
                $this->whereSessionDurationsEqual($query);
                $query->whereRaw('(candidates.finished_at IS NULL) = (sessions.finished_at IS NULL)')
                    ->whereColumn('candidates.finished_at', '<', 'sessions.finished_at');
            })
            ->orWhere(function (Builder $query): void {
                $this->whereSessionScoreEqual($query);
                $this->whereSessionDurationsEqual($query);
                $this->whereSessionFinishTimesEqual($query);
                $query->whereColumn('candidates.id', '<', 'sessions.id');
            });
    }

    private function whereSessionScoreEqual(Builder $query): void
    {
        $query->whereColumn('candidates.score', '=', 'sessions.score');
    }

    private function whereSessionDurationsEqual(Builder $query): void
    {
        $query->where(function (Builder $query): void {
            $query->whereColumn('candidates.duration_seconds', '=', 'sessions.duration_seconds')
                ->orWhere(function (Builder $query): void {
                    $query->whereNull('candidates.duration_seconds')
                        ->whereNull('sessions.duration_seconds');
                });
        });
    }

    private function whereSessionFinishTimesEqual(Builder $query): void
    {
        $query->where(function (Builder $query): void {
            $query->whereColumn('candidates.finished_at', '=', 'sessions.finished_at')
                ->orWhere(function (Builder $query): void {
                    $query->whereNull('candidates.finished_at')
                        ->whereNull('sessions.finished_at');
                });
        });
    }

    private function participantsAheadOf(object $row): int
    {
        $query = DB::query()->fromSub($this->bestParticipantsQuery(), 'leaders');

        $query->where('leaders.score', '>', (int) $row->score)
            ->orWhere(function (Builder $query) use ($row): void {
                $query->where('leaders.score', '=', (int) $row->score)
                    ->where(function (Builder $query) use ($row): void {
                        $this->whereDurationAheadOf($query, $row->duration_seconds);
                    });
            })
            ->orWhere(function (Builder $query) use ($row): void {
                $query->where('leaders.score', '=', (int) $row->score)
                    ->where(function (Builder $query) use ($row): void {
                        $this->whereDurationEqualTo($query, $row->duration_seconds);
                    })
                    ->where(function (Builder $query) use ($row): void {
                        $this->whereFinishTimeAheadOf($query, $row->finished_at);
                    });
            })
            ->orWhere(function (Builder $query) use ($row): void {
                $query->where('leaders.score', '=', (int) $row->score)
                    ->where(function (Builder $query) use ($row): void {
                        $this->whereDurationEqualTo($query, $row->duration_seconds);
                    })
                    ->where(function (Builder $query) use ($row): void {
                        $this->whereFinishTimeEqualTo($query, $row->finished_at);
                    })
                    ->where('leaders.session_id', '<', (int) $row->session_id);
            });

        return (int) $query->count();
    }

    private function whereDurationAheadOf(Builder $query, mixed $durationSeconds): void
    {
        if ($durationSeconds === null) {
            $query->whereNotNull('leaders.duration_seconds');

            return;
        }

        $query->whereNotNull('leaders.duration_seconds')
            ->where('leaders.duration_seconds', '<', (int) $durationSeconds);
    }

    private function whereDurationEqualTo(Builder $query, mixed $durationSeconds): void
    {
        if ($durationSeconds === null) {
            $query->whereNull('leaders.duration_seconds');

            return;
        }

        $query->where('leaders.duration_seconds', '=', (int) $durationSeconds);
    }

    private function whereFinishTimeAheadOf(Builder $query, mixed $finishedAt): void
    {
        if ($finishedAt === null) {
            $query->whereNotNull('leaders.finished_at');

            return;
        }

        $query->whereNotNull('leaders.finished_at')
            ->where('leaders.finished_at', '<', (string) $finishedAt);
    }

    private function whereFinishTimeEqualTo(Builder $query, mixed $finishedAt): void
    {
        if ($finishedAt === null) {
            $query->whereNull('leaders.finished_at');

            return;
        }

        $query->where('leaders.finished_at', '=', (string) $finishedAt);
    }

    private function entryFromRow(object $row, int $position): LeaderboardEntry
    {
        return new LeaderboardEntry(
            position: $position,
            userId: (int) $row->user_id,
            sessionId: (int) $row->session_id,
            name: (string) $row->name,
            score: (int) $row->score,
            durationSeconds: $row->duration_seconds === null ? null : (int) $row->duration_seconds,
            finishedAt: $row->finished_at === null ? null : (string) $row->finished_at,
        );
    }
}
