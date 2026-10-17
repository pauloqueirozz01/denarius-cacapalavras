<?php

namespace App\Data;

readonly class LeaderboardEntry
{
    public function __construct(
        public int $position,
        public int $userId,
        public int $sessionId,
        public string $name,
        public int $score,
        public ?int $durationSeconds,
        public ?string $finishedAt,
    ) {}
}
