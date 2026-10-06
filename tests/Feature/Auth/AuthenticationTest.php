<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_valid_credentials_authenticate_user_and_regenerate_session(): void
    {
        $user = User::factory()->participant()->create([
            'email' => 'player@example.com',
            'password' => 'Financeiro123',
        ]);
        $this->withSession(['marker' => 'preserved']);
        $previousSessionId = session()->getId();

        $response = $this->post(route('login.store'), [
            'email' => 'PLAYER@example.com',
            'password' => 'Financeiro123',
        ]);

        $response
            ->assertRedirectToRoute('game')
            ->assertSessionHas('marker', 'preserved');
        $this->assertAuthenticatedAs($user);
        $this->assertNotSame($previousSessionId, session()->getId());
    }

    public function test_invalid_credentials_do_not_authenticate_and_use_generic_message(): void
    {
        User::factory()->participant()->create(['email' => 'player@example.com']);

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'player@example.com',
            'password' => 'SenhaIncorreta123',
        ]);

        $response
            ->assertRedirectToRoute('login')
            ->assertSessionHasErrors([
                'email' => 'Não foi possível entrar com as credenciais informadas.',
            ]);
        $this->assertGuest();
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->participant()->create();
        $this->actingAs($user);

        $response = $this->post(route('logout'));

        $response->assertRedirectToRoute('home');
        $this->assertGuest();
    }

    public function test_login_is_blocked_after_configured_failed_attempts(): void
    {
        User::factory()->participant()->create(['email' => 'player@example.com']);
        $maxAttempts = config('denarius.auth.login.max_attempts');

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $this->post(route('login.store'), [
                'email' => 'player@example.com',
                'password' => 'SenhaIncorreta123',
            ])->assertSessionHasErrors('email');
        }

        $response = $this->post(route('login.store'), [
            'email' => 'player@example.com',
            'password' => 'Financeiro123',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString('Muitas tentativas.', session('errors')->first('email'));
        $this->assertGuest();
        $this->assertTrue(RateLimiter::tooManyAttempts(
            'player@example.com|127.0.0.1',
            $maxAttempts,
        ));
    }

    public function test_authenticated_user_is_redirected_away_from_login(): void
    {
        $user = User::factory()->participant()->create();

        $response = $this->actingAs($user)->get(route('login'));

        $response->assertRedirectToRoute('game');
    }
}
