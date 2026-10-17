<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;

class InvalidWordSelectionException extends Exception implements ShouldntReport
{
    public static function outsideGrid(): self
    {
        return new self('A seleção informada está fora dos limites do grid.');
    }

    public static function notFound(): self
    {
        return new self('A seleção não corresponde a uma palavra desta partida.');
    }
}
