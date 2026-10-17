<?php

namespace App\Exceptions;

use Exception;

class InvalidWordSearchTermsException extends Exception
{
    public static function emptyNormalizedTerm(string $term): self
    {
        return new self("O termo '{$term}' não possui letras compatíveis com o grid.");
    }

    public static function duplicateNormalizedTerm(string $normalizedTerm): self
    {
        return new self("O termo normalizado '{$normalizedTerm}' foi informado mais de uma vez.");
    }

    public static function noTerms(): self
    {
        return new self('Ao menos um termo financeiro deve ser informado para gerar o grid.');
    }
}
