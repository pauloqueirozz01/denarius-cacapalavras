<?php

namespace App\Policies;

use App\Models\GameSession;
use App\Models\User;

class GameSessionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, GameSession $gameSession): bool
    {
        return $user->isAdmin() || $this->owns($user, $gameSession);
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->exists;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, GameSession $gameSession): bool
    {
        return $this->owns($user, $gameSession);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, GameSession $gameSession): bool
    {
        return false;
    }

    public function markWord(User $user, GameSession $gameSession): bool
    {
        return $this->owns($user, $gameSession);
    }

    public function abandon(User $user, GameSession $gameSession): bool
    {
        return $this->owns($user, $gameSession);
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, GameSession $gameSession): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, GameSession $gameSession): bool
    {
        return false;
    }

    private function owns(User $user, GameSession $gameSession): bool
    {
        return $gameSession->user_id === $user->getKey();
    }
}
