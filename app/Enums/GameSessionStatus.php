<?php

namespace App\Enums;

enum GameSessionStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Abandoned = 'abandoned';

    public function isFinal(): bool
    {
        return $this !== self::Active;
    }

    public function canTransitionTo(self $status): bool
    {
        return $this === self::Active && in_array($status, [self::Completed, self::Abandoned], true);
    }
}
