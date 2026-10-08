<?php

namespace App\Actions\Game;

use App\Enums\GameSessionStatus;
use App\Exceptions\InvalidGameSessionSnapshotException;
use App\Models\GameSession;
use App\Models\GameSessionWord;
use App\Models\User;
use App\Services\GuestGameStore;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Moves the visitor's game into the account that just signed in.
 *
 * Completed games enter the ranking with the score and duration measured while
 * playing as a guest. Active games continue in the account unless it already
 * has its own active game. Abandoned games are discarded.
 */
class ClaimGuestGameAction
{
    public function __construct(private readonly GuestGameStore $guestGameStore) {}

    /**
     * @throws InvalidGameSessionSnapshotException
     */
    public function execute(User $user): ?GameSession
    {
        $guestGame = $this->guestGameStore->current();
        $this->guestGameStore->forget();

        if ($guestGame === null || $guestGame->status === GameSessionStatus::Abandoned) {
            return null;
        }

        Gate::forUser($user)->authorize('create', GameSession::class);

        return DB::transaction(function () use ($user, $guestGame): ?GameSession {
            $lockedUser = User::query()
                ->whereKey($user->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $guestGame->isActive()
                && GameSession::query()->whereBelongsTo($lockedUser)->active()->exists()
            ) {
                return null;
            }

            $claimedAt = now();
            $session = new GameSession;
            $session->setRawAttributes(Arr::except($guestGame->getAttributes(), ['id', 'user_id']));
            $session->user_id = $lockedUser->getKey();
            $session->save();

            $wordRows = $guestGame->words
                ->map(static fn (GameSessionWord $word): array => [
                    ...Arr::except($word->getAttributes(), ['id', 'game_session_id', 'created_at', 'updated_at']),
                    'game_session_id' => $session->getKey(),
                    'created_at' => $claimedAt,
                    'updated_at' => $claimedAt,
                ])
                ->all();

            if ($wordRows === [] || ! GameSessionWord::query()->insert($wordRows)) {
                throw InvalidGameSessionSnapshotException::invalid(
                    'não foi possível persistir as palavras da partida do visitante.',
                );
            }

            return $session->load('words');
        }, 3);
    }
}
