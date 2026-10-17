<?php

namespace App\Models;

use App\Enums\GameSessionStatus;
use App\Exceptions\InvalidGameSessionSnapshotException;
use App\Exceptions\InvalidGameSessionStateException;
use Carbon\CarbonInterface;
use Database\Factories\GameSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Guarded(['*'])]
class GameSession extends Model
{
    /** @use HasFactory<GameSessionFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => GameSessionStatus::Active->value,
        'found_words_count' => 0,
        'score' => 0,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function words(): HasMany
    {
        return $this->hasMany(GameSessionWord::class);
    }

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('status', GameSessionStatus::Active->value);
    }

    public function isActive(): bool
    {
        return $this->status === GameSessionStatus::Active;
    }

    public function assertActive(): void
    {
        if (! $this->isActive()) {
            throw InvalidGameSessionStateException::sessionIsNotActive($this->status);
        }
    }

    public function complete(CarbonInterface $finishedAt): void
    {
        $this->assertActive();

        if ($this->found_words_count !== $this->total_words) {
            throw InvalidGameSessionStateException::incompleteSessionCannotBeCompleted(
                foundWords: $this->found_words_count,
                totalWords: $this->total_words,
            );
        }

        $this->status = GameSessionStatus::Completed;
        $this->finished_at = $finishedAt;
        $this->duration_seconds = $this->durationUntil($finishedAt);
    }

    public function abandon(CarbonInterface $finishedAt): void
    {
        $this->assertActive();

        $this->status = GameSessionStatus::Abandoned;
        $this->finished_at = $finishedAt;
        $this->duration_seconds = $this->durationUntil($finishedAt);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => GameSessionStatus::class,
            'grid' => 'array',
            'rows' => 'integer',
            'columns' => 'integer',
            'total_words' => 'integer',
            'found_words_count' => 'integer',
            'score' => 'integer',
            'generation_config' => 'array',
            'started_at' => 'immutable_datetime',
            'finished_at' => 'immutable_datetime',
            'duration_seconds' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $session): void {
            if ($session->exists) {
                $originalStatus = GameSessionStatus::from((string) $session->getRawOriginal('status'));

                if ($originalStatus->isFinal()) {
                    throw InvalidGameSessionStateException::sessionIsNotActive($originalStatus);
                }

                if ($session->isDirty([
                    'user_id',
                    'grid',
                    'rows',
                    'columns',
                    'total_words',
                    'generation_config',
                    'started_at',
                ])) {
                    throw InvalidGameSessionSnapshotException::immutable();
                }

                if ($session->isDirty('status') && ! $originalStatus->canTransitionTo($session->status)) {
                    throw InvalidGameSessionStateException::invalidTransition($originalStatus, $session->status);
                }

                if ($session->isDirty('score')) {
                    if ($session->score < 0) {
                        throw InvalidGameSessionSnapshotException::invalid('score não pode ser negativo.');
                    }

                    if ($session->score < (int) $session->getRawOriginal('score')) {
                        throw InvalidGameSessionSnapshotException::invalid('score não pode ser reduzido.');
                    }
                }
            }

            $session->assertConsistentState();
        });
    }

    private function durationUntil(CarbonInterface $finishedAt): int
    {
        return max(0, (int) $this->started_at->diffInSeconds($finishedAt));
    }

    private function assertConsistentState(): void
    {
        if ($this->total_words < 1) {
            throw InvalidGameSessionSnapshotException::invalid('total_words deve ser positivo.');
        }

        if ($this->found_words_count < 0 || $this->found_words_count > $this->total_words) {
            throw InvalidGameSessionSnapshotException::invalid(
                'found_words_count deve permanecer entre zero e total_words.',
            );
        }

        if ($this->score < 0) {
            throw InvalidGameSessionSnapshotException::invalid('score não pode ser negativo.');
        }

        if ($this->started_at === null) {
            throw InvalidGameSessionSnapshotException::invalid('started_at é obrigatório.');
        }

        if ($this->status === GameSessionStatus::Active) {
            if ($this->finished_at !== null || $this->duration_seconds !== null) {
                throw InvalidGameSessionSnapshotException::invalid(
                    'uma partida ativa não pode possuir término ou duração.',
                );
            }

            return;
        }

        if ($this->finished_at === null || $this->duration_seconds === null) {
            throw InvalidGameSessionSnapshotException::invalid(
                'uma partida finalizada deve possuir término e duração.',
            );
        }

        if (
            $this->status === GameSessionStatus::Completed
            && $this->found_words_count !== $this->total_words
        ) {
            throw InvalidGameSessionSnapshotException::invalid(
                'uma partida concluída deve possuir todas as palavras encontradas.',
            );
        }
    }
}
