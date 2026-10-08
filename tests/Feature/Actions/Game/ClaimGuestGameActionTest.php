<?php

namespace Tests\Feature\Actions\Game;

use App\Actions\Game\AbandonGuestGameAction;
use App\Actions\Game\FindGuestGameWordAction;
use App\Actions\Game\StartGuestGameAction;
use App\Enums\GameSessionStatus;
use App\Models\FinancialTerm;
use App\Models\GameSession;
use App\Models\GameSessionWord;
use App\Models\User;
use App\Services\GuestGameStore;
use App\Services\RankingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClaimGuestGameActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['denarius.word_search.word_count' => 2]);
        FinancialTerm::factory()->active()->create(['term' => 'Juros']);
        FinancialTerm::factory()->active()->create(['term' => 'Pix']);
    }

    public function test_login_saves_the_completed_guest_game_into_the_ranking(): void
    {
        $user = User::factory()->participant()->create([
            'email' => 'player@example.com',
            'password' => 'Financeiro123',
        ]);
        $this->travelTo('2026-10-08 10:00:00');
        $guestGame = $this->startGuestGame();
        $this->travel(65)->seconds();
        $this->findAllWords($guestGame);

        $this->post(route('login.store'), [
            'email' => 'player@example.com',
            'password' => 'Financeiro123',
        ])->assertRedirectToRoute('game');

        $session = GameSession::query()->whereBelongsTo($user)->sole();

        $this->assertSame(GameSessionStatus::Completed, $session->status);
        $this->assertSame(1200, $session->score);
        $this->assertSame(65, $session->duration_seconds);
        $this->assertSame(2, $session->found_words_count);
        $this->assertSame($guestGame->grid, $session->grid);
        $this->assertSame(2, $session->words()->where('is_found', true)->count());
        $this->assertSame(1, app(RankingService::class)->positionForUser((int) $user->getKey())?->position);
        $this->assertNull(app(GuestGameStore::class)->current());
    }

    public function test_registration_continues_the_active_guest_game_in_the_new_account(): void
    {
        $guestGame = $this->startGuestGame();
        $firstWord = $guestGame->words->first();
        app(FindGuestGameWordAction::class)->execute(
            $guestGame,
            $firstWord->start_row,
            $firstWord->start_column,
            $firstWord->end_row,
            $firstWord->end_column,
        );

        $this->post(route('register.store'), [
            'name' => 'Ana Silva',
            'email' => 'ana@example.com',
            'password' => 'Financeiro123',
            'password_confirmation' => 'Financeiro123',
        ])->assertRedirectToRoute('game');

        $session = GameSession::query()->sole();

        $this->assertSame('ana@example.com', $session->user->email);
        $this->assertSame(GameSessionStatus::Active, $session->status);
        $this->assertSame(1, $session->found_words_count);
        $this->assertSame(100, $session->score);
        $this->assertSame(
            $guestGame->started_at->getTimestamp(),
            $session->started_at->getTimestamp(),
        );
        $this->assertSame(2, $session->words()->count());
        $this->assertSame(1, $session->words()->where('is_found', true)->count());
    }

    public function test_active_guest_game_is_discarded_when_the_account_already_has_one(): void
    {
        $user = User::factory()->participant()->create([
            'email' => 'player@example.com',
            'password' => 'Financeiro123',
        ]);
        $existingSession = GameSession::factory()->for($user)->active()->create();
        $this->startGuestGame();

        $this->post(route('login.store'), [
            'email' => 'player@example.com',
            'password' => 'Financeiro123',
        ])->assertRedirectToRoute('game');

        $this->assertSame([$existingSession->getKey()], GameSession::query()->pluck('id')->all());
        $this->assertNull(app(GuestGameStore::class)->current());
    }

    public function test_abandoned_guest_game_is_not_saved(): void
    {
        $user = User::factory()->participant()->create([
            'email' => 'player@example.com',
            'password' => 'Financeiro123',
        ]);
        app(AbandonGuestGameAction::class)->execute($this->startGuestGame());

        $this->post(route('login.store'), [
            'email' => 'player@example.com',
            'password' => 'Financeiro123',
        ])->assertRedirectToRoute('game');

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseCount('game_sessions', 0);
        $this->assertDatabaseCount('game_session_words', 0);
    }

    public function test_login_without_a_guest_game_creates_nothing(): void
    {
        User::factory()->participant()->create([
            'email' => 'player@example.com',
            'password' => 'Financeiro123',
        ]);

        $this->post(route('login.store'), [
            'email' => 'player@example.com',
            'password' => 'Financeiro123',
        ])->assertRedirectToRoute('game');

        $this->assertDatabaseCount('game_sessions', 0);
    }

    private function startGuestGame(): GameSession
    {
        return app(StartGuestGameAction::class)->execute();
    }

    private function findAllWords(GameSession $guestGame): void
    {
        $guestGame->words->each(function (GameSessionWord $word) use ($guestGame): void {
            app(FindGuestGameWordAction::class)->execute(
                $guestGame,
                $word->start_row,
                $word->start_column,
                $word->end_row,
                $word->end_column,
            );
        });
    }
}
