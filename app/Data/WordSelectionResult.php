<?php

namespace App\Data;

use App\Models\GameSession;
use App\Models\GameSessionWord;

readonly class WordSelectionResult
{
    public function __construct(
        public GameSession $session,
        public GameSessionWord $word,
        public bool $wasNewlyFound,
        public bool $completedSession,
        public int $pointsAwarded,
        public int $wordPoints,
        public int $completionBonus,
        public int $speedBonus,
    ) {}
}
