<?php

namespace Tests\Feature\Models;

use App\Enums\GameSessionStatus;
use App\Exceptions\InvalidGameSessionSnapshotException;
use App\Exceptions\InvalidGameSessionStateException;
use App\Models\GameSession;
use App\Models\User;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GameSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_snapshot_state_and_relationships(): void
    {
        $user = User::factory()->create();
        $session = GameSession::factory()->for($user)->active()->create();

        $this->assertSame(GameSessionStatus::Active, $session->status);
        $this->assertIsArray($session->grid);
        $this->assertIsArray($session->generation_config);
        $this->assertSame($user->id, $session->user->id);
        $this->assertTrue($user->gameSessions->contains($session));
    }

    public function test_incomplete_session_cannot_be_completed(): void
    {
        $session = GameSession::factory()->active()->create([
            'total_words' => 2,
            'found_words_count' => 1,
        ]);

        $this->expectException(InvalidGameSessionStateException::class);
        $this->expectExceptionMessage('1 de 2 palavras');

        $session->complete(now());
    }

    public function test_completed_session_cannot_transition_back_to_active(): void
    {
        $session = GameSession::factory()->completed()->create();
        $session->status = GameSessionStatus::Active;

        $this->expectException(InvalidGameSessionStateException::class);

        $session->save();
    }

    public function test_completed_session_cannot_transition_to_abandoned(): void
    {
        $session = GameSession::factory()->completed()->create();

        $this->expectException(InvalidGameSessionStateException::class);

        $session->abandon(now());
    }

    public function test_abandoned_session_cannot_transition_to_completed(): void
    {
        $session = GameSession::factory()->abandoned()->create();

        $this->expectException(InvalidGameSessionStateException::class);

        $session->complete(now());
    }

    public function test_snapshot_cannot_be_changed_after_session_creation(): void
    {
        $session = GameSession::factory()->active()->create();
        $session->grid = [['X']];

        $this->expectException(InvalidGameSessionSnapshotException::class);
        $this->expectExceptionMessage('imutável');

        $session->save();
    }

    public function test_found_count_cannot_exceed_total_words(): void
    {
        $session = GameSession::factory()->active()->create();
        $session->found_words_count = 2;

        $this->expectException(InvalidGameSessionSnapshotException::class);
        $this->expectExceptionMessage('found_words_count');

        $session->save();
    }

    public function test_server_controlled_fields_are_not_mass_assignable(): void
    {
        $this->expectException(MassAssignmentException::class);

        GameSession::query()->create([
            'user_id' => 999,
            'status' => GameSessionStatus::Completed,
            'grid' => [['X']],
            'rows' => 1,
            'columns' => 1,
            'total_words' => 1,
            'found_words_count' => 1,
            'generation_config' => [],
            'started_at' => now(),
            'finished_at' => now(),
            'duration_seconds' => 0,
        ]);

    }
}
