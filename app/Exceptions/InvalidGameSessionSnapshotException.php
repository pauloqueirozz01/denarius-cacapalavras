<?php

namespace App\Exceptions;

use Exception;

class InvalidGameSessionSnapshotException extends Exception
{
    public static function invalid(string $reason): self
    {
        return new self("Snapshot de partida inválido: {$reason}");
    }

    public static function immutable(): self
    {
        return new self('O snapshot de uma partida iniciada é imutável.');
    }
}
