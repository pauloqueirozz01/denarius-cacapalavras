<?php

namespace App\Exceptions;

use Exception;

class InsufficientFinancialTermsException extends Exception
{
    public static function forRequestedCount(int $requested, int $available): self
    {
        return new self(
            "Foram solicitados {$requested} termos, mas somente {$available} termos ativos, únicos e compatíveis com o grid estão disponíveis.",
        );
    }
}
