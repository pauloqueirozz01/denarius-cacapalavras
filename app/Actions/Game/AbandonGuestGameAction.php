<?php

namespace App\Actions\Game;

use App\Exceptions\InvalidGameSessionStateException;
use App\Models\GameSession;
use App\Services\GuestGameStore;

class AbandonGuestGameAction
{
    public function __construct(private readonly GuestGameStore $guestGameStore) {}

    /**
     * @throws InvalidGameSessionStateException
     */
    public function execute(GameSession $session): GameSession
    {
        $session->abandon(now());
        $this->guestGameStore->store($session);

        return $session;
    }
}
