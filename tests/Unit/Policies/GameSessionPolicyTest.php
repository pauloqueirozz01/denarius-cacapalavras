<?php

namespace Tests\Unit\Policies;

use App\Models\GameSession;
use App\Models\User;
use App\Policies\GameSessionPolicy;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class GameSessionPolicyTest extends TestCase
{
    #[DataProvider('ownedSessionAbilities')]
    public function test_owner_is_allowed_to_use_session_abilities(string $ability): void
    {
        $user = User::factory()->make(['id' => 10]);
        $session = GameSession::factory()->make(['user_id' => 10]);
        $policy = new GameSessionPolicy;

        $this->assertTrue($policy->{$ability}($user, $session));
    }

    #[DataProvider('ownedSessionAbilities')]
    public function test_other_user_is_forbidden_from_session_abilities(string $ability): void
    {
        $user = User::factory()->make(['id' => 20]);
        $session = GameSession::factory()->make(['user_id' => 10]);
        $policy = new GameSessionPolicy;

        $this->assertFalse($policy->{$ability}($user, $session));
    }

    public function test_persisted_user_can_create_and_list_own_sessions(): void
    {
        $user = User::factory()->make(['id' => 10]);
        $user->exists = true;
        $policy = new GameSessionPolicy;

        $this->assertTrue($policy->create($user));
        $this->assertTrue($policy->viewAny($user));
    }

    public function test_sessions_cannot_be_deleted_through_the_policy(): void
    {
        $user = User::factory()->make(['id' => 10]);
        $session = GameSession::factory()->make(['user_id' => 10]);
        $policy = new GameSessionPolicy;

        $this->assertFalse($policy->delete($user, $session));
        $this->assertFalse($policy->restore($user, $session));
        $this->assertFalse($policy->forceDelete($user, $session));
    }

    /**
     * @return array<string, array{string}>
     */
    public static function ownedSessionAbilities(): array
    {
        return [
            'view' => ['view'],
            'update' => ['update'],
            'mark word' => ['markWord'],
            'abandon' => ['abandon'],
        ];
    }
}
