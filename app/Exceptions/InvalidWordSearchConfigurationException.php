<?php

namespace App\Exceptions;

use Exception;

class InvalidWordSearchConfigurationException extends Exception
{
    public static function invalidDimensions(int $rows, int $columns, int $minimum, int $maximum): self
    {
        return new self(
            "As dimensões {$rows}x{$columns} devem estar entre {$minimum} e {$maximum} células.",
        );
    }

    public static function invalidWordCount(int $wordCount, int $maximum): self
    {
        return new self("A quantidade de palavras deve estar entre 1 e {$maximum}; recebido: {$wordCount}.");
    }

    public static function invalidGenerationAttempts(int $attempts): self
    {
        return new self("A quantidade de tentativas de geração deve ser positiva; recebido: {$attempts}.");
    }

    public static function missingDirections(): self
    {
        return new self('Ao menos uma direção deve estar disponível para posicionar palavras.');
    }
}
