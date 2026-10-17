<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum FinancialTermDifficulty: string implements HasColor, HasLabel
{
    case Easy = 'easy';
    case Medium = 'medium';
    case Hard = 'hard';

    public function getLabel(): string
    {
        return match ($this) {
            self::Easy => 'Fácil',
            self::Medium => 'Médio',
            self::Hard => 'Difícil',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Easy => 'success',
            self::Medium => 'warning',
            self::Hard => 'danger',
        };
    }
}
