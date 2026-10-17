<?php

namespace App\Exceptions;

use Exception;

class WordSearchGenerationException extends Exception
{
    public static function wordDoesNotFit(string $normalizedTerm, int $rows, int $columns): self
    {
        return new self("A palavra '{$normalizedTerm}' não cabe no grid {$rows}x{$columns}.");
    }

    public static function unableToPlaceWords(int $attempts): self
    {
        return new self(
            "Não foi possível posicionar todas as palavras após {$attempts} tentativas de geração.",
        );
    }
}
