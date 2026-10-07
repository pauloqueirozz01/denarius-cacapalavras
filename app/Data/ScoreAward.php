<?php

namespace App\Data;

readonly class ScoreAward
{
    public function __construct(
        public int $wordPoints,
        public int $completionBonus,
        public int $speedBonus,
    ) {}

    public function total(): int
    {
        return $this->wordPoints + $this->completionBonus + $this->speedBonus;
    }
}
