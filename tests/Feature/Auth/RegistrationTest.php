<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_can_create_a_participant_account(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Ana Silva',
            'email' => 'ANA@EXAMPLE.COM',
            'password' => 'Financeiro123',
            'password_confirmation' => 'Financeiro123',
        ]);

        $response->assertRedirectToRoute('game');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'name' => 'Ana Silva',
            'email' => 'ana@example.com',
            'role' => UserRole::Participant->value,
        ]);
        $this->assertTrue(Hash::check('Financeiro123', User::query()->sole()->password));
    }

    public function test_public_registration_cannot_choose_admin_role(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => 'Participante',
            'email' => 'participant@example.com',
            'password' => 'Financeiro123',
            'password_confirmation' => 'Financeiro123',
            'role' => UserRole::Admin->value,
        ]);

        $response->assertRedirectToRoute('game');
        $this->assertSame(UserRole::Participant, User::query()->sole()->role);
    }

    public function test_registration_rejects_an_existing_email(): void
    {
        User::factory()->participant()->create(['email' => 'used@example.com']);

        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Outro participante',
            'email' => 'USED@example.com',
            'password' => 'Financeiro123',
            'password_confirmation' => 'Financeiro123',
        ]);

        $response
            ->assertRedirectToRoute('register')
            ->assertSessionHasErrors(['email' => 'Este e-mail já está cadastrado.']);
        $this->assertGuest();
    }

    public function test_registration_requires_password_confirmation(): void
    {
        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Participante',
            'email' => 'participant@example.com',
            'password' => 'Financeiro123',
            'password_confirmation' => 'OutraSenha123',
        ]);

        $response
            ->assertRedirectToRoute('register')
            ->assertSessionHasErrors(['password' => 'A confirmação da senha não confere.']);
        $this->assertDatabaseMissing('users', ['email' => 'participant@example.com']);
    }

    public function test_registration_rejects_a_weak_password(): void
    {
        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Participante',
            'email' => 'participant@example.com',
            'password' => 'senhafraca',
            'password_confirmation' => 'senhafraca',
        ]);

        $response
            ->assertRedirectToRoute('register')
            ->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'participant@example.com']);
    }

    public function test_registration_endpoint_is_rate_limited(): void
    {
        $attempts = config('denarius.auth.registration.max_attempts');

        for ($attempt = 0; $attempt < $attempts; $attempt++) {
            $this->post(route('register.store'), [])->assertSessionHasErrors();
        }

        $this->post(route('register.store'), [])->assertTooManyRequests();
    }
}
