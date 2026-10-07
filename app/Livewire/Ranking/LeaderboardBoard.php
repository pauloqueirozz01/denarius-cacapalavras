<?php

namespace App\Livewire\Ranking;

use App\Data\LeaderboardEntry;
use App\Services\RankingService;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

class LeaderboardBoard extends Component
{
    use WithPagination;

    public function render(): View
    {
        $entries = null;
        $playerPosition = null;
        $hasError = false;
        $userId = (int) Auth::id();

        try {
            $ranking = app(RankingService::class);
            $perPage = min(
                max((int) config('denarius.leaderboard.per_page', 20), 1),
                (int) config('denarius.leaderboard.maximum_per_page', 50),
            );
            $entries = $ranking->paginate($perPage);
            $playerPosition = $ranking->positionForUser($userId);
        } catch (Throwable $exception) {
            report($exception);
            $hasError = true;
        }

        return view('livewire.ranking.leaderboard-board', [
            'entries' => $entries,
            'playerPosition' => $playerPosition,
            'playerVisibleOnCurrentPage' => $playerPosition instanceof LeaderboardEntry
                && $entries instanceof LengthAwarePaginator
                && $entries->getCollection()->contains(
                    fn (LeaderboardEntry $entry): bool => $entry->userId === $userId,
                ),
            'hasError' => $hasError,
            'pollInterval' => (int) config('denarius.leaderboard.poll_interval_seconds', 5),
        ]);
    }
}
