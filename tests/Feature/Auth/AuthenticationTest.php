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

    public function test_many_people_on_the_same_network_can_log_in(): void
    {
        $users = User::factory()->participant()->count(10)->create(['password' => 'Financeiro123']);

        foreach ($users as $user) {
            $this->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'Financeiro123',
            ])->assertRedirectToRoute('game');

            auth()->logout();
        }
    }

    public function test_ip_ceiling_blocks_a_login_flood_across_different_emails(): void
    {
        config(['denarius.auth.login.ip_max_attempts' => 3]);
        User::factory()->participant()->create([
            'email' => 'player@example.com',
            'password' => 'Financeiro123',
        ]);

        for ($attempt = 1; $attempt <= 3; $attempt++) {
            $this->post(route('login.store'), [
                'email' => "tentativa{$attempt}@example.com",
                'password' => 'SenhaIncorreta123',
            ])->assertSessionHasErrors('email');
        }

        $response = $this->from(route('login'))->post(route('login.store'), [
            'email' => 'player@example.com',
            'password' => 'Financeiro123',
        ]);

        $response
            ->assertRedirectToRoute('login')
            ->assertSessionHasInput('email', 'player@example.com');
        $this->assertMatchesRegularExpression(
            '/^Muitas tentativas\. Tente novamente em \d+ segundos\.$/',
            session('errors')->first('email'),
        );
        $this->assertGuest();
    }

    public function test_repeated_login_post_after_success_is_sent_to_the_game(): void
    {
        $user = User::factory()->participant()->create([
            'email' => 'player@example.com',
            'password' => 'Financeiro123',
        ]);
        $credentials = ['email' => 'player@example.com', 'password' => 'Financeiro123'];

        $this->post(route('login.store'), $credentials)->assertRedirectToRoute('game');
        $this->post(route('login.store'), $credentials)->assertRedirectToRoute('game');

        $this->assertAuthenticatedAs($user);
    }

    public function test_second_logout_click_with_an_expired_token_returns_home(): void
    {
        $this->app->detectEnvironment(fn (): string => 'local');
        $user = User::factory()->participant()->create();
        $this->actingAs($user)->withSession(['_token' => 'token-da-pagina']);

        $this->post(route('logout'), ['_token' => 'token-da-pagina'])->assertRedirectToRoute('home');
        $this->post(route('logout'), ['_token' => 'token-da-pagina'])->assertRedirectToRoute('home');

        $this->assertGuest();
    }

    public function test_expired_session_on_login_goes_back_to_the_form(): void
    {
        $this->app->detectEnvironment(fn (): string => 'local');

        $this->post(route('login.store'), [
            'email' => 'player@example.com',
            'password' => 'Financeiro123',
        ])
            ->assertRedirectToRoute('login')
            ->assertSessionHasInput('email', 'player@example.com')
            ->assertSessionMissing('_old_input.password')
            ->assertSessionHasErrors(['email' => 'Sua sessão expirou. Confira os dados e envie novamente.']);
    }

    public function test_login_form_disables_the_button_while_submitting(): void
    {
        $this->get(route('login'))
            ->assertSeeHtml('data-submit-once')
            ->assertSeeHtml('data-submitting-label="Entrando…"');
    }
}
