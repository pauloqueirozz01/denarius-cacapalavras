<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;

class ActiveGameSessionExistsException extends Exception implements ShouldntReport
{
    public static function forUser(int $userId): self
    {
        return new self("O usuário {$userId} já possui uma partida ativa.");
    }

    public static function forGuest(): self
    {
        return new self('O visitante já possui uma partida ativa.');
    }
}
