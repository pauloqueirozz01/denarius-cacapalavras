<?php

namespace App\Services;

use App\Data\LeaderboardEntry;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator as PaginatorResolver;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

/**
 * Serves the public leaderboard from one short-lived snapshot of the whole ranking.
 *
 * Every open leaderboard polls; caching the ordered ranking once per TTL keeps the
 * heavy query at one run per TTL for everyone, and both the page and each player's
 * position are read from the same snapshot. The snapshot holds plain arrays because
 * the cache refuses to unserialize objects (`cache.serializable_classes`).
 */
class CachedLeaderboard
{
    public const CACHE_KEY = 'leaderboard:snapshot';

    public function __construct(private readonly RankingService $ranking) {}

    public function page(int $perPage, ?int $page = null): LengthAwarePaginator
    {
        $entries = $this->entries();
        $currentPage = max($page ?? PaginatorResolver::resolveCurrentPage('page'), 1);

        return new LengthAwarePaginator(
            items: $entries->forPage($currentPage, $perPage)->values(),
            total: $entries->count(),
            perPage: $perPage,
            currentPage: $currentPage,
            options: [
                'path' => PaginatorResolver::resolveCurrentPath(),
                'pageName' => 'page',
            ],
        );
    }

    public function positionForUser(int $userId): ?LeaderboardEntry
    {
        return $this->entries()->first(fn (LeaderboardEntry $entry): bool => $entry->userId === $userId);
    }

    /**
     * @return Collection<int, LeaderboardEntry>
     */
    private function entries(): Collection
    {
        /** @var list<array{position: int, userId: int, sessionId: int, name: string, score: int, durationSeconds: ?int, finishedAt: ?string}> $snapshot */
        $snapshot = Cache::remember(
            self::CACHE_KEY,
            max((int) config('denarius.leaderboard.cache_seconds', 10), 1),
            fn (): array => $this->ranking->orderedEntries()
                ->map(fn (LeaderboardEntry $entry): array => get_object_vars($entry))
                ->all(),
        );

        return collect($snapshot)->map(fn (array $entry): LeaderboardEntry => new LeaderboardEntry(...$entry));
    }
}
