<?php

namespace Tests\Feature\Livewire\Game;

use App\Enums\GameSessionStatus;
use App\Livewire\Game\GameBoard;
use App\Models\FinancialTerm;
use App\Models\GameSession;
use App\Models\GameSessionWord;
use App\Services\GuestGameStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GuestGameTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['denarius.word_search.word_count' => 2]);
        FinancialTerm::factory()->active()->create(['term' => 'Juros']);
        FinancialTerm::factory()->active()->create(['term' => 'Pix']);
    }

    public function test_guest_sees_the_start_screen_with_an_invitation_to_save_the_score(): void
    {
        Livewire::test(GameBoard::class)
            ->assertSee('Olá, visitante')
            ->assertSee('Você pode jogar sem cadastro.')
            ->assertSee('Iniciar partida');
    }

    public function test_guest_can_start_a_game_kept_only_in_the_session(): void
    {
        Livewire::test(GameBoard::class)
            ->call('startGame')
            ->assertSee('Sua partida começou')
            ->assertSee('Juros')
            ->assertSee('Pix')
            ->assertSee('Você está jogando como visitante.')
            ->assertSee('0 de 2 palavras encontradas');

        $guestGame = app(GuestGameStore::class)->current();

        $this->assertNotNull($guestGame);
        $this->assertSame(GameSessionStatus::Active, $guestGame->status);
        $this->assertCount(2, $guestGame->words);
        $this->assertDatabaseCount('game_sessions', 0);
        $this->assertDatabaseCount('game_session_words', 0);
    }

    public function test_repeated_start_resumes_the_active_guest_game(): void
    {
        $component = Livewire::test(GameBoard::class)->call('startGame');
        $firstGrid = app(GuestGameStore::class)->current()->grid;

        $component->call('startGame')->assertSee('Sua partida ativa foi retomada.');

        $this->assertSame($firstGrid, app(GuestGameStore::class)->current()->grid);
    }

    public function test_guest_finds_words_and_completes_with_server_side_scoring(): void
    {
        $this->travelTo('2026-10-08 10:00:00');
        $component = Livewire::test(GameBoard::class)->call('startGame');
        [$firstWord, $secondWord] = app(GuestGameStore::class)->current()->words->all();

        $component
            ->call('selectWord', $firstWord->start_row, $firstWord->start_column, $firstWord->end_row, $firstWord->end_column)
            ->assertSee("Boa! +100 pontos por {$firstWord->original_term}.")
            ->assertSee('1 de 2 palavras encontradas');

        $this->travel(65)->seconds();

        $component
            ->call('selectWord', $secondWord->end_row, $secondWord->end_column, $secondWord->start_row, $secondWord->start_column)
            ->assertSee('Parabéns! +100 pela palavra, +500 de conclusão e +500 de velocidade. Pontuação final: 1200.')
            ->assertSee('01:05')
            ->assertSee('Esta pontuação ainda não está no ranking.')
            ->assertSee('Entrar e salvar')
            ->assertSee('Criar conta e salvar')
            ->assertDontSee('Ver ranking')
            ->assertDontSee('Abandonar partida');

        $guestGame = app(GuestGameStore::class)->current();

        $this->assertSame(GameSessionStatus::Completed, $guestGame->status);
        $this->assertSame(2, $guestGame->found_words_count);
        $this->assertSame(1200, $guestGame->score);
        $this->assertSame(65, $guestGame->duration_seconds);
        $this->assertDatabaseCount('game_sessions', 0);
    }

    public function test_guest_invalid_and_repeated_selections_do_not_change_the_score(): void
    {
        $component = Livewire::test(GameBoard::class)->call('startGame');
        $word = app(GuestGameStore::class)->current()->words->first();

        $component
            ->call('selectWord', 0, 0, 99, 99)
            ->assertSee('Essa seleção não corresponde a um termo da partida.')
            ->call('selectWord', $word->start_row, $word->start_column, $word->end_row, $word->end_column)
            ->call('selectWord', $word->start_row, $word->start_column, $word->end_row, $word->end_column)
            ->assertSee('Você já encontrou essa palavra.');

        $guestGame = app(GuestGameStore::class)->current();

        $this->assertSame(1, $guestGame->found_words_count);
        $this->assertSame(100, $guestGame->score);
    }

    public function test_guest_can_abandon_and_start_a_new_game(): void
    {
        Livewire::test(GameBoard::class)
            ->call('startGame')
            ->call('abandonGame')
            ->assertSee('Partida abandonada')
            ->assertSee('Nova partida')
            ->assertDontSee('Abandonar partida')
            ->call('selectWord', 0, 0, 0, 1)
            ->assertSee('já foi encerrada')
            ->call('startGame')
            ->assertSee('Sua partida começou');

        $this->assertSame(GameSessionStatus::Active, app(GuestGameStore::class)->current()->status);
    }

    public function test_guest_does_not_see_games_from_registered_players(): void
    {
        $session = GameSession::factory()->active()->create();
        GameSessionWord::factory()->for($session)->pending()->create([
            'financial_term_id' => FinancialTerm::query()->where('term', 'Juros')->value('id'),
            'original_term' => 'Termo privado',
        ]);

        Livewire::test(GameBoard::class)
            ->assertSee('Iniciar partida')
            ->assertDontSee('Termo privado');
    }
}
