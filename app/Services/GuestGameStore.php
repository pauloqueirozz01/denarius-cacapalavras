<?php

namespace App\Services;

use App\Models\GameSession;
use App\Models\GameSessionWord;
use Illuminate\Contracts\Session\Session;
use Illuminate\Database\Eloquent\Collection;

/**
 * Keeps the visitor's game in the server-side session, never in the database.
 *
 * The game is rehydrated as unsaved models so the board, scoring and state
 * rules are the same ones used by registered players.
 */
class GuestGameStore
{
    private const SESSION_KEY = 'guest_game';

    public function __construct(private readonly Session $session) {}

    public function current(): ?GameSession
    {
        $payload = $this->session->get(self::SESSION_KEY);

        if (! is_array($payload) || ! is_array($payload['session'] ?? null) || ! is_array($payload['words'] ?? null)) {
            return null;
        }

        $gameSession = (new GameSession)->setRawAttributes($payload['session']);
        $words = array_map(
            static fn (array $attributes): GameSessionWord => (new GameSessionWord)->setRawAttributes($attributes),
            array_values(array_filter($payload['words'], is_array(...))),
        );

        return $gameSession->setRelation('words', new Collection($words));
    }

    public function store(GameSession $gameSession): void
    {
        $this->session->put(self::SESSION_KEY, [
            'session' => $gameSession->getAttributes(),
            'words' => $gameSession->words
                ->map(static fn (GameSessionWord $word): array => $word->getAttributes())
                ->values()
                ->all(),
        ]);
    }

    public function forget(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }
}
