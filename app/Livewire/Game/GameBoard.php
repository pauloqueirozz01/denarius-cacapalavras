<?php

namespace App\Livewire\Game;

use App\Actions\Game\AbandonGameSessionAction;
use App\Actions\Game\FindGameSessionWordAction;
use App\Actions\Game\StartGameSessionAction;
use App\Enums\GameSessionStatus;
use App\Exceptions\ActiveGameSessionExistsException;
use App\Exceptions\InsufficientFinancialTermsException;
use App\Exceptions\InvalidGameSessionStateException;
use App\Exceptions\InvalidWordSearchConfigurationException;
use App\Exceptions\InvalidWordSearchTermsException;
use App\Exceptions\InvalidWordSelectionException;
use App\Exceptions\WordSearchGenerationException;
use App\Models\GameSession;
use App\Models\GameSessionWord;
use App\Models\User;
use App\Services\RankingService;
use App\Services\ScoreCalculator;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Throwable;

class GameBoard extends Component
{
    #[Locked]
    public string $feedbackMessage = '';

    #[Locked]
    public string $feedbackTone = 'info';

    public function startGame(StartGameSessionAction $startGameSession): void
    {
        $user = $this->authenticatedUser();

        if (! $this->consumeAttempt('start', config('denarius.game_interface.start.max_attempts'))) {
            return;
        }

        try {
            $startGameSession->execute($user);
            $this->setFeedback('Sua partida começou. Encontre os termos escondidos!', 'success');
        } catch (ActiveGameSessionExistsException) {
            $this->setFeedback('Sua partida ativa foi retomada.', 'info');
        } catch (
            InsufficientFinancialTermsException|InvalidWordSearchConfigurationException|InvalidWordSearchTermsException|WordSearchGenerationException $exception
        ) {
            report($exception);
            $this->setFeedback('Não foi possível montar o tabuleiro agora. Tente novamente.', 'error');
        } catch (Throwable $exception) {
            report($exception);
            $this->setFeedback('Não foi possível iniciar a partida. Tente novamente.', 'error');
        }
    }

    /**
     * @return array{active: bool}
     */
    public function selectWord(
        mixed $startRow,
        mixed $startColumn,
        mixed $endRow,
        mixed $endColumn,
        FindGameSessionWordAction $findGameSessionWord,
    ): array {
        $validator = Validator::make(
            compact('startRow', 'startColumn', 'endRow', 'endColumn'),
            [
                'startRow' => ['required', 'integer', 'min:0'],
                'startColumn' => ['required', 'integer', 'min:0'],
                'endRow' => ['required', 'integer', 'min:0'],
                'endColumn' => ['required', 'integer', 'min:0'],
            ],
        );

        if ($validator->fails()) {
            $this->setFeedback('As coordenadas da seleção são inválidas.', 'error');

            return ['active' => true];
        }

        $coordinates = $validator->validated();

        $user = $this->authenticatedUser();

        if (! $this->consumeAttempt('selection', config('denarius.game_interface.selection.max_attempts'))) {
            return ['active' => true];
        }

        $session = $this->activeSessionFor($user);

        if ($session === null) {
            $this->setFeedback('Esta partida já foi encerrada. Inicie uma nova para continuar.', 'info');

            return ['active' => false];
        }

        try {
            $result = $findGameSessionWord->execute(
                user: $user,
                session: $session,
                startRow: $coordinates['startRow'],
                startColumn: $coordinates['startColumn'],
                endRow: $coordinates['endRow'],
                endColumn: $coordinates['endColumn'],
            );

            if (! $result->wasNewlyFound) {
                $this->setFeedback('Você já encontrou essa palavra.', 'info');

                return ['active' => true];
            }

            if ($result->completedSession) {
                $bonusMessage = " +{$result->completionBonus} de conclusão";

                if ($result->speedBonus > 0) {
                    $bonusMessage .= " e +{$result->speedBonus} de velocidade";
                }

                $this->setFeedback(
                    "Parabéns! +{$result->wordPoints} pela palavra,{$bonusMessage}. Pontuação final: {$result->session->score}.",
                    'success',
                );

                return ['active' => false];
            }

            $this->setFeedback(
                "Boa! +{$result->pointsAwarded} pontos por {$result->word->original_term}.",
                'success',
            );

            return ['active' => true];
        } catch (InvalidWordSelectionException) {
            $this->setFeedback('Essa seleção não corresponde a um termo da partida.', 'error');
        } catch (InvalidGameSessionStateException) {
            $this->setFeedback('Esta partida já foi encerrada.', 'info');
        } catch (AuthorizationException $exception) {
            report($exception);
            $this->setFeedback('Você não pode alterar esta partida.', 'error');
        } catch (Throwable $exception) {
            report($exception);
            $this->setFeedback('Não foi possível validar a seleção. Tente novamente.', 'error');
        }

        return ['active' => $this->activeSessionFor($user) !== null];
    }

    public function abandonGame(AbandonGameSessionAction $abandonGameSession): void
    {
        $user = $this->authenticatedUser();

        if (! $this->consumeAttempt('abandon', config('denarius.game_interface.abandon.max_attempts'))) {
            return;
        }

        $session = $this->activeSessionFor($user);

        if ($session === null) {
            $this->setFeedback('Esta partida já foi encerrada.', 'info');

            return;
        }

        try {
            $abandonGameSession->execute($user, $session);
            $this->setFeedback('Partida abandonada. Você pode começar um novo desafio.', 'info');
        } catch (InvalidGameSessionStateException) {
            $this->setFeedback('Esta partida já foi encerrada.', 'info');
        } catch (Throwable $exception) {
            report($exception);
            $this->setFeedback('Não foi possível abandonar a partida. Tente novamente.', 'error');
        }
    }

    public function render(): View
    {
        $user = $this->authenticatedUser();
        $session = $this->displaySessionFor($user);
        $words = collect();
        $foundCells = [];
        $rankingPosition = null;
        $rankingUnavailable = false;
        $scoreBreakdown = null;

        if ($session !== null) {
            Gate::forUser($user)->authorize('view', $session);
            $session->loadMissing(['words' => fn (HasMany $query): HasMany => $query
                ->orderBy('original_term')
                ->orderBy('id')]);
            $words = $session->words;

            foreach ($words->where('is_found', true) as $word) {
                foreach ($this->cellsFor($word) as $cell) {
                    $foundCells[$cell] = true;
                }
            }

            if ($session->status === GameSessionStatus::Completed) {
                try {
                    $rankingPosition = app(RankingService::class)
                        ->positionForCompletedSession((int) $session->getKey());
                } catch (Throwable $exception) {
                    report($exception);
                    $rankingUnavailable = true;
                }

                $scoringSnapshot = $session->generation_config['scoring'] ?? null;

                if (is_array($scoringSnapshot) && $session->duration_seconds !== null) {
                    try {
                        $scoreBreakdown = (new ScoreCalculator($scoringSnapshot))->completedBreakdown(
                            foundWordsCount: $session->found_words_count,
                            durationSeconds: $session->duration_seconds,
                        );

                        if ($scoreBreakdown->total() !== $session->score) {
                            $scoreBreakdown = null;
                        }
                    } catch (Throwable $exception) {
                        report($exception);
                        $scoreBreakdown = null;
                    }
                }
            }
        }

        return view('livewire.game.game-board', [
            'session' => $session,
            'words' => $words,
            'foundCells' => $foundCells,
            'rankingPosition' => $rankingPosition,
            'rankingUnavailable' => $rankingUnavailable,
            'scoreBreakdown' => $scoreBreakdown,
        ]);
    }

    private function authenticatedUser(): User
    {
        $user = Auth::user();
        abort_unless($user instanceof User, 401);

        return $user;
    }

    private function activeSessionFor(User $user): ?GameSession
    {
        return GameSession::query()
            ->whereBelongsTo($user)
            ->active()
            ->latest('id')
            ->first();
    }

    private function displaySessionFor(User $user): ?GameSession
    {
        return $this->activeSessionFor($user) ?? GameSession::query()
            ->whereBelongsTo($user)
            ->whereIn('status', [GameSessionStatus::Completed, GameSessionStatus::Abandoned])
            ->latest('id')
            ->first();
    }

    private function consumeAttempt(string $action, int $maxAttempts): bool
    {
        $key = "game-interface:{$action}:".$this->authenticatedUser()->getKey().'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            $this->setFeedback("Muitas tentativas. Aguarde {$seconds} segundos.", 'error');

            return false;
        }

        RateLimiter::hit($key, config('denarius.game_interface.decay_seconds'));

        return true;
    }

    /**
     * @return list<string>
     */
    private function cellsFor(GameSessionWord $word): array
    {
        $cells = [];
        $row = $word->start_row;
        $column = $word->start_column;

        for ($index = 0; $index < mb_strlen($word->normalized_term); $index++) {
            $cells[] = "{$row}:{$column}";
            $row += $word->direction->rowDelta();
            $column += $word->direction->columnDelta();
        }

        return $cells;
    }

    private function setFeedback(string $message, string $tone): void
    {
        $this->feedbackMessage = $message;
        $this->feedbackTone = $tone;
    }
}
