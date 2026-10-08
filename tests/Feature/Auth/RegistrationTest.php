<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
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
            'password' => '123imagina',
            'password_confirmation' => '123imagina',
        ]);

        $response
            ->assertRedirectToRoute('register')
            ->assertSessionHasErrors([
                'password' => 'A senha deve conter pelo menos uma letra maiúscula e uma minúscula.',
            ]);
        $this->assertDatabaseMissing('users', ['email' => 'participant@example.com']);
    }

    public function test_registration_explains_when_password_has_no_number(): void
    {
        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Participante',
            'email' => 'participant@example.com',
            'password' => 'SenhaFraca',
            'password_confirmation' => 'SenhaFraca',
        ]);

        $response
            ->assertRedirectToRoute('register')
            ->assertSessionHasErrors([
                'password' => 'A senha deve conter pelo menos um número.',
            ]);
        $this->assertDatabaseMissing('users', ['email' => 'participant@example.com']);
    }

    public function test_registration_explains_password_minimum_length(): void
    {
        $response = $this->from(route('register'))->post(route('register.store'), [
            'name' => 'Participante',
            'email' => 'participant@example.com',
            'password' => 'Ab1',
            'password_confirmation' => 'Ab1',
        ]);

        $response
            ->assertRedirectToRoute('register')
            ->assertSessionHasErrors([
                'password' => 'A senha deve ter pelo menos 8 caracteres.',
            ]);
        $this->assertDatabaseMissing('users', ['email' => 'participant@example.com']);
    }

    public function test_ten_people_on_the_same_network_can_register_within_one_minute(): void
    {
        for ($person = 1; $person <= 10; $person++) {
            $this->post(route('register.store'), $this->registrationData("pessoa{$person}@example.com"))
                ->assertRedirectToRoute('game');

            auth()->logout();
        }

        $this->assertSame(10, User::query()->count());
    }

    public function test_same_email_above_the_limit_goes_back_to_the_form_with_a_ptbr_message(): void
    {
        $maxAttempts = config('denarius.auth.registration.email_max_attempts');

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $this->from(route('register'))
                ->post(route('register.store'), [...$this->registrationData('ana@example.com'), 'password_confirmation' => 'Diferente123'])
                ->assertSessionHasErrors('password');
        }

        $response = $this->from(route('register'))
            ->post(route('register.store'), $this->registrationData('ana@example.com'));

        $response
            ->assertRedirectToRoute('register')
            ->assertSessionHasInput('name', 'Ana Silva')
            ->assertSessionHasInput('email', 'ana@example.com')
            ->assertSessionMissing('_old_input.password');
        $this->assertMatchesRegularExpression(
            '/^Muitas tentativas de cadastro\. Tente novamente em \d+ segundos\.$/',
            session('errors')->first('email'),
        );
        $this->assertDatabaseMissing('users', ['email' => 'ana@example.com']);
    }

    public function test_flood_above_the_ip_ceiling_gets_the_ptbr_429_page(): void
    {
        config(['denarius.auth.registration.ip_max_attempts' => 3]);

        for ($person = 1; $person <= 3; $person++) {
            $this->post(route('register.store'), ['email' => "flood{$person}@example.com"])->assertSessionHasErrors();
        }

        $this->post(route('register.store'), ['email' => 'flood4@example.com'])
            ->assertTooManyRequests()
            ->assertHeader('Retry-After')
            ->assertSee('Muitas tentativas em pouco tempo')
            ->assertSee('segundos e tente de novo')
            ->assertDontSee('Too Many Requests');
    }

    public function test_duplicate_email_race_is_a_validation_error_instead_of_a_server_error(): void
    {
        User::creating(function (User $user): void {
            if (! DB::table('users')->where('email', $user->email)->exists()) {
                DB::table('users')->insert([
                    'name' => 'Envio concorrente',
                    'email' => $user->email,
                    'password' => Hash::make('Financeiro123'),
                    'role' => UserRole::Participant->value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $response = $this->from(route('register'))
            ->post(route('register.store'), $this->registrationData('corrida@example.com'));

        $response
            ->assertRedirectToRoute('register')
            ->assertSessionHasErrors(['email' => 'Este e-mail já está cadastrado.']);
        $this->assertGuest();
        $this->assertSame(1, User::query()->where('email', 'corrida@example.com')->count());
    }

    public function test_repeated_registration_post_after_success_is_sent_to_the_game(): void
    {
        $this->post(route('register.store'), $this->registrationData('dupla@example.com'))
            ->assertRedirectToRoute('game');

        $this->post(route('register.store'), $this->registrationData('dupla@example.com'))
            ->assertRedirectToRoute('game');

        $this->assertSame(1, User::query()->where('email', 'dupla@example.com')->count());
    }

    public function test_expired_session_on_registration_goes_back_to_the_form_with_the_typed_data(): void
    {
        $this->app->detectEnvironment(fn (): string => 'local');

        $response = $this->post(route('register.store'), $this->registrationData('expirou@example.com'));

        $response
            ->assertRedirectToRoute('register')
            ->assertSessionHasInput('email', 'expirou@example.com')
            ->assertSessionMissing('_old_input.password')
            ->assertSessionHasErrors(['email' => 'Sua sessão expirou. Confira os dados e envie novamente.']);
        $this->assertDatabaseMissing('users', ['email' => 'expirou@example.com']);
    }

    public function test_registration_form_disables_the_button_while_submitting(): void
    {
        $this->get(route('register'))
            ->assertSeeHtml('data-submit-once')
            ->assertSeeHtml('data-submitting-label="Criando conta…"');
    }

    /**
     * @return array{name: string, email: string, password: string, password_confirmation: string}
     */
    private function registrationData(string $email): array
    {
        return [
            'name' => 'Ana Silva',
            'email' => $email,
            'password' => 'Financeiro123',
            'password_confirmation' => 'Financeiro123',
        ];
    }
}
