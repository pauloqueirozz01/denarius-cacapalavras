<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_can_open_the_game_without_login(): void
    {
        $this->get(route('game'))
            ->assertOk()
            ->assertSee('Olá, visitante')
            ->assertSee('Você pode jogar sem cadastro.');
    }

    public function test_guest_is_redirected_to_login_from_ranking(): void
    {
        $this->get(route('ranking'))->assertRedirectToRoute('login');
    }

    public function test_guest_cannot_open_the_admin_panel(): void
    {
        $this->get(route('filament.admin.pages.dashboard'))
            ->assertRedirectToRoute('filament.admin.auth.login');
    }

    public function test_participant_can_access_game(): void
    {
        $participant = User::factory()->participant()->create();

        $response = $this->actingAs($participant)->get(route('game'));

        $response
            ->assertSee('Seu desafio começa agora.')
            ->assertSee('Iniciar partida');
    }

    public function test_game_escapes_participant_name(): void
    {
        $participant = User::factory()->participant()->create([
            'name' => '<script>alert("xss")</script>',
        ]);

        $response = $this->actingAs($participant)->get(route('game'));

        $response
            ->assertSee('&lt;script&gt;', escape: false)
            ->assertDontSee('<script>', escape: false);
    }

    public function test_participant_cannot_access_admin_panel(): void
    {
        $participant = User::factory()->participant()->create();

        $response = $this->actingAs($participant)->get(route('filament.admin.pages.dashboard'));

        $response->assertForbidden();
    }

    public function test_admin_can_access_admin_panel(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('filament.admin.pages.dashboard'));

        $response->assertOk();
    }
}
