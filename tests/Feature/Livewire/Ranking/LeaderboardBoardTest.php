<?php

namespace Tests\Feature\Livewire\Ranking;

use App\Enums\GameSessionStatus;
use App\Livewire\Ranking\LeaderboardBoard;
use App\Models\GameSession;
use App\Models\User;
use App\Services\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class LeaderboardBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_and_authenticated_player_sees_empty_ranking(): void
    {
        $this->get(route('ranking'))->assertRedirectToRoute('login');

        $user = User::factory()->participant()->create();
        $this->actingAs($user)
            ->get(route('ranking'))
            ->assertSee('Ranking financeiro')
            ->assertSee('Ainda não há partidas concluídas')
            ->assertSee('Voltar ao jogo');
    }

    public function test_rank_page_shows_podium_player_highlight_poll_interval_and_no_sensitive_fields(): void
    {
        $player = User::factory()->participant()->create(['name' => 'Minha posição']);
        $otherPlayer = User::factory()->participant()->create(['name' => 'Outra pessoa']);
        $this->completedSession($otherPlayer, ['score' => 1200]);
        $this->completedSession($player, ['score' => 1000]);
        GameSession::factory()->for($player)->active()->create(['score' => 999999]);

        $html = Livewire::actingAs($player)->test(LeaderboardBoard::class)->html();

        $this->assertStringContainsString('wire:poll.5s', $html);
        $this->assertStringContainsString('Atualização automática a cada 5 segundos', $html);
        $this->assertStringContainsString('1º lugar', $html);
        $this->assertStringContainsString('Minha posição', $html);
        $this->assertStringContainsString('1.000', $html);
        $this->assertStringContainsString('wire:loading', $html);
        $this->assertStringNotContainsString($player->email, $html);
        $this->assertStringNotContainsString('999999', $html);
    }

    public function test_rank_updates_on_refresh_without_writing_or_starting_a_game(): void
    {
        $viewer = User::factory()->participant()->create();
        $component = Livewire::actingAs($viewer)->test(LeaderboardBoard::class)
            ->assertSee('Ainda não há partidas concluídas');
        $winner = User::factory()->participant()->create(['name' => 'Novo primeiro']);
        $session = $this->completedSession($winner, ['score' => 1500]);
        $updatedAt = $session->updated_at->toISOString();

        $component->call('$refresh')->assertSee('Novo primeiro');

        $this->assertSame(1, GameSession::query()->count());
        $this->assertSame(GameSessionStatus::Completed, $session->refresh()->status);
        $this->assertSame($updatedAt, $session->updated_at->toISOString());
    }

    public function test_shows_own_position_separately_when_player_is_outside_current_page(): void
    {
        config(['denarius.leaderboard.per_page' => 5]);
        $users = User::factory()->participant()->count(7)->create();
        foreach ($users as $index => $user) {
            $this->completedSession($user, ['score' => 1000 - $index]);
        }
        $viewer = $users[6];

        Livewire::actingAs($viewer)
            ->test(LeaderboardBoard::class)
            ->assertSee('Sua melhor posição')
            ->assertSee('7º lugar')
            ->assertSee($viewer->name);
    }

    public function test_renders_a_recoverable_error_if_ranking_query_fails(): void
    {
        $user = User::factory()->participant()->create();
        $ranking = Mockery::mock(RankingService::class);
        $ranking->shouldReceive('paginate')->once()->andThrow(new \RuntimeException('database unavailable'));
        $this->app->instance(RankingService::class, $ranking);

        Livewire::actingAs($user)
            ->test(LeaderboardBoard::class)
            ->assertSee('Ranking temporariamente indisponível')
            ->assertSee('Atualizar agora');
    }

    public function test_escapes_player_names_and_only_renders_public_ranking_fields(): void
    {
        $user = User::factory()->participant()->create([
            'name' => '<script>alert("ranking")</script>',
            'email' => 'private@example.test',
        ]);
        $this->completedSession($user, ['score' => 900]);

        $html = Livewire::actingAs($user)->test(LeaderboardBoard::class)->html();

        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert("ranking")</script>', $html);
        $this->assertStringNotContainsString('private@example.test', $html);
        $this->assertStringNotContainsString('game_sessions', $html);
    }

    public function test_does_not_render_internal_session_identifiers(): void
    {
        $user = User::factory()->participant()->create();
        $this->completedSession($user, ['id' => 818181, 'score' => 900]);

        $html = Livewire::actingAs($user)->test(LeaderboardBoard::class)->html();

        $this->assertStringNotContainsString('818181', $html);
    }

    public function test_pagination_preserves_total_order_and_is_available_for_large_rankings(): void
    {
        $users = User::factory()->participant()->count(22)->create();
        foreach ($users as $index => $user) {
            $this->completedSession($user, ['score' => 1000 - $index]);
        }

        $this->actingAs($users[21])
            ->get(route('ranking'))
            ->assertSee('Paginação do ranking')
            ->assertSee('Sua melhor posição')
            ->assertSee('22º lugar');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function completedSession(User $user, array $attributes = []): GameSession
    {
        $startedAt = now()->subMinutes(2);
        $attributes = array_merge([
            'score' => 100,
            'total_words' => 1,
            'found_words_count' => 1,
            'duration_seconds' => 60,
            'started_at' => $startedAt,
            'finished_at' => $startedAt->copy()->addSeconds(60),
        ], $attributes);

        return GameSession::factory()->for($user)->completed()->create($attributes);
    }
}
