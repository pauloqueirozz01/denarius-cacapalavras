<?php

namespace App\Models;

use App\Enums\WordDirection;
use App\Exceptions\InvalidGameSessionSnapshotException;
use App\Exceptions\InvalidGameSessionStateException;
use Database\Factories\GameSessionWordFactory;
use Illuminate\Database\Eloquent\Attributes\Guarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Guarded(['*'])]
class GameSessionWord extends Model
{
    /** @use HasFactory<GameSessionWordFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_found' => false,
    ];

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    public function financialTerm(): BelongsTo
    {
        return $this->belongsTo(FinancialTerm::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_row' => 'integer',
            'start_column' => 'integer',
            'end_row' => 'integer',
            'end_column' => 'integer',
            'direction' => WordDirection::class,
            'is_found' => 'boolean',
            'found_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $word): void {
            if ($word->exists && $word->isDirty([
                'game_session_id',
                'financial_term_id',
                'original_term',
                'normalized_term',
                'start_row',
                'start_column',
                'end_row',
                'end_column',
                'direction',
            ])) {
                throw InvalidGameSessionSnapshotException::immutable();
            }

            if ($word->exists && (bool) $word->getRawOriginal('is_found')) {
                throw InvalidGameSessionStateException::foundWordCannotBeReverted();
            }

            if ($word->is_found !== ($word->found_at !== null)) {
                throw InvalidGameSessionSnapshotException::invalid(
                    'is_found e found_at devem mudar em conjunto.',
                );
            }
        });
    }
}
