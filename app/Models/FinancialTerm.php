<?php

namespace App\Models;

use App\Enums\FinancialTermDifficulty;
use App\Observers\FinancialTermObserver;
use Database\Factories\FinancialTermFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['term', 'description', 'difficulty', 'is_active'])]
#[ObservedBy([FinancialTermObserver::class])]
class FinancialTerm extends Model
{
    /** @use HasFactory<FinancialTermFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'difficulty' => FinancialTermDifficulty::Medium->value,
        'is_active' => true,
    ];

    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    #[Scope]
    protected function byDifficulty(Builder $query, FinancialTermDifficulty $difficulty): Builder
    {
        return $query->where('difficulty', $difficulty->value);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'difficulty' => FinancialTermDifficulty::class,
            'is_active' => 'boolean',
        ];
    }
}
