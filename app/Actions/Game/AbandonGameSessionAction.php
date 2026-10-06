<?php

namespace App\Actions\Game;

use App\Models\GameSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AbandonGameSessionAction
{
    public function execute(User $user, GameSession $session): GameSession
    {
        Gate::forUser($user)->authorize('abandon', $session);

        return DB::transaction(function () use ($session): GameSession {
            $lockedSession = GameSession::query()
                ->whereKey($session->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedSession->abandon(now());
            $lockedSession->save();

            return $lockedSession;
        }, 3);
    }
}
