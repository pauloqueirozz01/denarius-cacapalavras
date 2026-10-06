<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Tests\TestCase;

class UserSecurityTest extends TestCase
{
    public function test_sensitive_authentication_attributes_are_not_serialized(): void
    {
        $user = User::factory()->participant()->make();

        $serialized = $user->toArray();

        $this->assertArrayNotHasKey('password', $serialized);
        $this->assertArrayNotHasKey('remember_token', $serialized);
    }

    public function test_role_helpers_use_enum_cast(): void
    {
        $admin = User::factory()->admin()->make();
        $participant = User::factory()->participant()->make();

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isParticipant());
        $this->assertSame(UserRole::Participant, $participant->role);
        $this->assertTrue($participant->isParticipant());
        $this->assertFalse($participant->isAdmin());
    }
}
