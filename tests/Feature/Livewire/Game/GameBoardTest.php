<?php

namespace Tests\Feature\Livewire\Game;

use App\Enums\GameSessionStatus;
use App\Enums\WordDirection;
use App\Livewire\Game\GameBoard;
use App\Models\FinancialTerm;
use App\Models\GameSession;
use App\Models\GameSessionWord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GameBoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_the_game_page(): void
    {
        $this->get(route('game'))->assertRedirectToRoute('login');
    }

    public function test_authenticated_user_without_a_session_can_open_the_start_screen(): void
    {
        $user = User::factory()->create(['name' => 'Paulo']);

        $this->actingAs($user)
            ->get(route('game'))
            ->assertSeeLivewire(GameBoard::class)
            ->assertSee('Seu desafio começa agora.')
            ->assertSee('Iniciar partida');
    }

    public function test_user_can_start_a_server_generated_session(): void
    {
        config(['denarius.word_search.word_count' => 1]);
        $user = User::factory()->create();
        FinancialTerm::factory()->active()->create([
            'term' => 'Juros',
            'description' => 'Valor pago ou recebido pelo uso do dinheiro.',
        ]);

        Livewire::actingAs($user)
            ->test(GameBoard::class)
            ->call('startGame')
            ->assertSee('Sua partida começou')
            ->assertSee('Tabuleiro')
            ->assertSee('Juros');

        $session = GameSession::query()->whereBelongsTo($user)->sole();
        $this->assertSame(GameSessionStatus::Active, $session->status);
        $this->assertSame(1, $session->words()->count());
    }

    public function test_active_session_renders_the_persisted_grid_and_original_terms_without_pending_placements(): void
    {
        $user = User::factory()->create();
        [$session] = $this->createActiveSessionWithWords($user);

        $component = Livewire::actingAs($user)->test(GameBoard::class);
        $html = $component->html();

        $component
            ->assertSee('Juros')
            ->assertSee('Pix')
            ->assertSee('0 de 2 palavras encontradas')
            ->assertSee('Pontuação')
            ->assertSeeHtml('aria-label="Tabuleiro de caça-palavras com 3 linhas e 5 colunas"');
        $this->assertSame(15, substr_count($html, 'data-word-cell'));
        $this->assertStringNotContainsString('data-start-row', $html);
        $this->assertStringNotContainsString('RIGHT', $html);
        $this->assertStringContainsString((string) ($session->started_at->getTimestamp() * 1000), $html);
    }

    public function test_found_word_is_marked_and_its_cells_are_highlighted(): void
    {
        $user = User::factory()->create();
        $this->createActiveSessionWithWords($user, firstWordFound: true);

        Livewire::actingAs($user)
            ->test(GameBoard::class)
            ->assertSee('1 de 2 palavras encontradas')
            ->assertSeeHtml('letra J, encontrada')
            ->assertSeeHtml('decoration-emerald-300/70');
    }

    public function test_valid_selection_updates_the_interface_from_the_backend_result(): void
    {
        $this->travelTo('2026-10-06 12:01:00');
        $user = User::factory()->create();
        [$session, $firstWord] = $this->createActiveSessionWithWords($user);

        Livewire::actingAs($user)
            ->test(GameBoard::class)
            ->call('selectWord', 0, 0, 0, 4)
            ->assertSee('Boa! +100 pontos por Juros.')
            ->assertSee('1 de 2 palavras encontradas')
            ->assertSeeHtml('letra J, encontrada');

        $this->assertTrue($firstWord->refresh()->is_found);
        $this->assertSame(1, $session->refresh()->found_words_count);
        $this->assertSame(100, $session->score);
    }

    public function test_invalid_and_manipulated_coordinates_do_not_change_progress(): void
    {
        $user = User::factory()->create();
        [$session, $firstWord] = $this->createActiveSessionWithWords($user);

        Livewire::actingAs($user)
            ->test(GameBoard::class)
            ->call('selectWord', 0, 0, 2, 3)
            ->assertSee('não corresponde')
            ->call('selectWord', -1, 0, 0, 4)
            ->assertSee('coordenadas da seleção são inválidas');

        $this->assertFalse($firstWord->refresh()->is_found);
        $this->assertSame(0, $session->refresh()->found_words_count);
        $this->assertSame(0, $session->score);
    }

    public function test_reverse_selection_and_duplicate_request_are_integrated_idempotently(): void
    {
        $user = User::factory()->create();
        [$session, $firstWord] = $this->createActiveSessionWithWords($user);
        $component = Livewire::actingAs($user)->test(GameBoard::class);

        $component
            ->call('selectWord', 0, 4, 0, 0)
            ->assertSee('Boa! +100 pontos por Juros.')
            ->call('selectWord', 0, 4, 0, 0)
            ->assertSee('Você já encontrou essa palavra.');

        $this->assertSame(1, $session->refresh()->found_words_count);
        $this->assertSame(100, $session->score);
        $this->assertNotNull($firstWord->refresh()->found_at);
    }

    public function test_selection_rate_limit_blocks_abuse_without_changing_the_session(): void
    {
        config(['denarius.game_interface.selection.max_attempts' => 1]);
        $user = User::factory()->create();
        [$session, $firstWord] = $this->createActiveSessionWithWords($user);

        Livewire::actingAs($user)
            ->test(GameBoard::class)
            ->call('selectWord', 0, 0, 2, 3)
            ->call('selectWord', 0, 0, 0, 4)
            ->assertSee('Muitas tentativas');

        $this->assertFalse($firstWord->refresh()->is_found);
        $this->assertSame(0, $session->refresh()->found_words_count);
    }

    public function test_last_word_completion_is_reflected_and_blocks_more_selections(): void
    {
        $this->travelTo('2026-10-06 12:01:05');
        $user = User::factory()->create();
        $session = GameSession::factory()->for($user)->active()->create([
            'started_at' => now()->subSeconds(65),
        ]);
        $word = GameSessionWord::factory()->for($session)->pending()->create();

        $component = Livewire::actingAs($user)
            ->test(GameBoard::class)
            ->call('selectWord', 0, 0, 0, 4)
            ->assertSee('Parabéns! +100 pela palavra, +500 de conclusão e +500 de velocidade. Pontuação final: 1100.')
            ->assertSee('Tempo registrado:')
            ->assertSee('Pontuação final:')
            ->assertSee('1.100')
            ->assertSee('01:05')
            ->assertSee('Nova partida')
            ->assertDontSee('Abandonar partida');

        $component->call('selectWord', 0, 0, 0, 4)->assertSee('já foi encerrada');

        $this->assertSame(GameSessionStatus::Completed, $session->refresh()->status);
        $this->assertSame(1, $session->found_words_count);
        $this->assertSame(1100, $session->score);
        $this->assertTrue($word->refresh()->is_found);
    }

    public function test_explicit_abandonment_ends_the_session_and_allows_a_new_game(): void
    {
        $user = User::factory()->create();
        [$session] = $this->createActiveSessionWithWords($user);

        Livewire::actingAs($user)
            ->test(GameBoard::class)
            ->call('abandonGame')
            ->assertSee('Partida abandonada')
            ->assertSee('Nova partida')
            ->assertDontSee('Abandonar partida')
            ->call('selectWord', 0, 0, 0, 4)
            ->assertSee('já foi encerrada');

        $this->assertSame(GameSessionStatus::Abandoned, $session->refresh()->status);
    }

    public function test_component_only_loads_the_authenticated_users_session(): void
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        [$otherSession, $otherWord] = $this->createActiveSessionWithWords($otherUser);

        Livewire::actingAs($owner)
            ->test(GameBoard::class)
            ->assertSee('Seu desafio começa agora.')
            ->assertDontSee('Juros')
            ->call('selectWord', 0, 0, 0, 4)
            ->assertSee('já foi encerrada');

        $this->assertSame(0, $otherSession->refresh()->found_words_count);
        $this->assertFalse($otherWord->refresh()->is_found);
    }

    public function test_reload_restores_grid_progress_and_server_based_clock_origin(): void
    {
        $user = User::factory()->create();
        [$session] = $this->createActiveSessionWithWords($user, firstWordFound: true);
        $startedAtMilliseconds = (string) ($session->started_at->getTimestamp() * 1000);

        $firstLoad = Livewire::actingAs($user)->test(GameBoard::class);
        $secondLoad = Livewire::actingAs($user)->test(GameBoard::class);

        $firstLoad
            ->assertSee('1 de 2 palavras encontradas')
            ->assertSee('100');
        $secondLoad
            ->assertSee('1 de 2 palavras encontradas')
            ->assertSee('100')
            ->assertSeeHtml('letra J, encontrada');
        $this->assertStringContainsString($startedAtMilliseconds, $firstLoad->html());
        $this->assertStringContainsString($startedAtMilliseconds, $secondLoad->html());
        $this->assertSame(1, GameSession::query()->whereBelongsTo($user)->count());
    }

    public function test_snapshot_terms_are_escaped_in_the_interface(): void
    {
        $user = User::factory()->create();
        $session = GameSession::factory()->for($user)->active()->create();
        GameSessionWord::factory()->for($session)->create([
            'original_term' => '<script>alert("xss")</script>',
        ]);

        $html = Livewire::actingAs($user)->test(GameBoard::class)->html();

        $this->assertStringNotContainsString('<script>alert("xss")</script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    /**
     * @return array{GameSession, GameSessionWord, GameSessionWord}
     */
    private function createActiveSessionWithWords(User $user, bool $firstWordFound = false): array
    {
        $session = GameSession::factory()->for($user)->active()->create([
            'grid' => [
                ['J', 'U', 'R', 'O', 'S'],
                ['P', 'I', 'X', 'A', 'B'],
                ['C', 'D', 'E', 'F', 'G'],
            ],
            'total_words' => 2,
            'found_words_count' => $firstWordFound ? 1 : 0,
            'score' => $firstWordFound ? 100 : 0,
        ]);
        $juros = FinancialTerm::factory()->active()->create([
            'term' => 'Juros',
            'description' => 'Juros.',
        ]);
        $pix = FinancialTerm::factory()->active()->create([
            'term' => 'Pix',
            'description' => 'Pix.',
        ]);
        $firstWordFactory = GameSessionWord::factory()
            ->for($session)
            ->for($juros, 'financialTerm')
            ->state([
                'original_term' => 'Juros',
                'normalized_term' => 'JUROS',
                'start_row' => 0,
                'start_column' => 0,
                'end_row' => 0,
                'end_column' => 4,
                'direction' => WordDirection::Right,
            ]);
        $firstWord = ($firstWordFound ? $firstWordFactory->found() : $firstWordFactory->pending())->create();
        $secondWord = GameSessionWord::factory()
            ->for($session)
            ->for($pix, 'financialTerm')
            ->pending()
            ->create([
                'original_term' => 'Pix',
                'normalized_term' => 'PIX',
                'start_row' => 1,
                'start_column' => 0,
                'end_row' => 1,
                'end_column' => 2,
                'direction' => WordDirection::Right,
            ]);

        return [$session, $firstWord, $secondWord];
    }
}
