<?php

namespace App\Exceptions;

use App\Enums\GameSessionStatus;
use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;

class InvalidGameSessionStateException extends Exception implements ShouldntReport
{
    public static function sessionIsNotActive(GameSessionStatus $status): self
    {
        return new self("A partida está no estado '{$status->value}' e não aceita esta operação.");
    }

    public static function invalidTransition(GameSessionStatus $from, GameSessionStatus $to): self
    {
        return new self("A transição de '{$from->value}' para '{$to->value}' não é permitida.");
    }

    public static function incompleteSessionCannotBeCompleted(int $foundWords, int $totalWords): self
    {
        return new self(
            "A partida não pode ser concluída com {$foundWords} de {$totalWords} palavras encontradas.",
        );
    }

    public static function foundWordCannotBeReverted(): self
    {
        return new self('Uma palavra encontrada não pode voltar ao estado pendente.');
    }
}
