<?php

namespace Tests\Feature\Actions\Game;

use App\Actions\Game\AbandonGameSessionAction;
use App\Enums\GameSessionStatus;
use App\Exceptions\InvalidGameSessionStateException;
use App\Models\GameSession;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbandonGameSessionActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_session_can_be_abandoned_with_server_duration(): void
    {
        $this->travelTo('2026-10-06 12:00:45');
        $user = User::factory()->create();
        $session = GameSession::factory()->for($user)->active()->create([
            'started_at' => now()->subSeconds(45),
            'score' => 200,
        ]);

        $abandonedSession = (new AbandonGameSessionAction)->execute($user, $session);

        $this->assertSame(GameSessionStatus::Abandoned, $abandonedSession->status);
        $this->assertSame('2026-10-06 12:00:45', $abandonedSession->finished_at->toDateTimeString());
        $this->assertSame(45, $abandonedSession->duration_seconds);
        $this->assertSame(200, $abandonedSession->score);
    }

    public function test_completed_session_cannot_be_abandoned(): void
    {
        $user = User::factory()->create();
        $session = GameSession::factory()->for($user)->completed()->create();

        $this->expectException(InvalidGameSessionStateException::class);
        $this->expectExceptionMessage("estado 'completed'");

        (new AbandonGameSessionAction)->execute($user, $session);
    }

    public function test_abandoned_session_cannot_be_abandoned_again(): void
    {
        $user = User::factory()->create();
        $session = GameSession::factory()->for($user)->abandoned()->create();

        $this->expectException(InvalidGameSessionStateException::class);
        $this->expectExceptionMessage("estado 'abandoned'");

        (new AbandonGameSessionAction)->execute($user, $session);
    }

    public function test_user_cannot_abandon_another_users_session(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $session = GameSession::factory()->for($owner)->active()->create();

        try {
            (new AbandonGameSessionAction)->execute($otherUser, $session);
            $this->fail('Era esperada uma falha de autorização.');
        } catch (AuthorizationException) {
            $this->assertSame(GameSessionStatus::Active, $session->refresh()->status);
            $this->assertNull($session->finished_at);
        }

    }
}
