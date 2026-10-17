<?php

namespace App\Services;

use App\Data\WordPlacement;
use App\Data\WordSearchResult;
use App\Exceptions\InvalidGameSessionSnapshotException;

class GameSessionSnapshotValidator
{
    /**
     * @throws InvalidGameSessionSnapshotException
     */
    public function validate(WordSearchResult $result): void
    {
        if ($result->rows < 1 || count($result->grid) !== $result->rows) {
            throw InvalidGameSessionSnapshotException::invalid('a quantidade de linhas não corresponde ao grid.');
        }

        foreach ($result->grid as $row) {
            if (count($row) !== $result->columns) {
                throw InvalidGameSessionSnapshotException::invalid(
                    'a quantidade de colunas não corresponde ao grid.',
                );
            }

            foreach ($row as $letter) {
                if (preg_match('/\A[A-Z]\z/', $letter) !== 1) {
                    throw InvalidGameSessionSnapshotException::invalid(
                        'todas as células devem conter uma letra entre A e Z.',
                    );
                }
            }
        }

        if ($result->placements === [] || count($result->placements) !== count($result->selectedTerms)) {
            throw InvalidGameSessionSnapshotException::invalid(
                'placements e termos selecionados devem possuir a mesma quantidade positiva.',
            );
        }

        $selectedTerms = [];

        foreach ($result->selectedTerms as $term) {
            if ($term->financialTermId === null) {
                throw InvalidGameSessionSnapshotException::invalid(
                    'todos os termos selecionados devem possuir financial_term_id.',
                );
            }

            $selectedTerms[$this->termKey($term->financialTermId, $term->normalizedTerm)] = true;
        }

        $normalizedTerms = [];
        $endpointPairs = [];

        foreach ($result->placements as $placement) {
            if ($placement->financialTermId === null) {
                throw InvalidGameSessionSnapshotException::invalid(
                    'todos os placements devem possuir financial_term_id.',
                );
            }

            if (! isset($selectedTerms[$this->termKey($placement->financialTermId, $placement->normalizedTerm)])) {
                throw InvalidGameSessionSnapshotException::invalid(
                    'um placement não corresponde aos termos selecionados.',
                );
            }

            if (isset($normalizedTerms[$placement->normalizedTerm])) {
                throw InvalidGameSessionSnapshotException::invalid(
                    'palavras normalizadas duplicadas não são permitidas.',
                );
            }

            $endpointKey = $this->endpointKey(
                $placement->start->row,
                $placement->start->column,
                $placement->end->row,
                $placement->end->column,
            );

            if (isset($endpointPairs[$endpointKey])) {
                throw InvalidGameSessionSnapshotException::invalid(
                    'dois placements não podem compartilhar exatamente os mesmos extremos.',
                );
            }

            $this->validatePlacement($result, $placement);
            $normalizedTerms[$placement->normalizedTerm] = true;
            $endpointPairs[$endpointKey] = true;
        }
    }

    private function validatePlacement(WordSearchResult $result, WordPlacement $placement): void
    {
        $letters = str_split($placement->normalizedTerm);
        $lastOffset = count($letters) - 1;
        $expectedEndRow = $placement->start->row + ($placement->direction->rowDelta() * $lastOffset);
        $expectedEndColumn = $placement->start->column + ($placement->direction->columnDelta() * $lastOffset);

        if ($placement->end->row !== $expectedEndRow || $placement->end->column !== $expectedEndColumn) {
            throw InvalidGameSessionSnapshotException::invalid(
                'as coordenadas finais não correspondem à direção e ao tamanho da palavra.',
            );
        }

        $wordFromGrid = '';

        foreach ($letters as $offset => $letter) {
            $row = $placement->start->row + ($placement->direction->rowDelta() * $offset);
            $column = $placement->start->column + ($placement->direction->columnDelta() * $offset);

            if ($row < 0 || $row >= $result->rows || $column < 0 || $column >= $result->columns) {
                throw InvalidGameSessionSnapshotException::invalid('um placement ultrapassa os limites do grid.');
            }

            $wordFromGrid .= $result->grid[$row][$column];
        }

        if ($wordFromGrid !== $placement->normalizedTerm) {
            throw InvalidGameSessionSnapshotException::invalid(
                'um placement não pode ser reconstruído a partir do grid.',
            );
        }
    }

    private function termKey(int $financialTermId, string $normalizedTerm): string
    {
        return "{$financialTermId}:{$normalizedTerm}";
    }

    private function endpointKey(int $startRow, int $startColumn, int $endRow, int $endColumn): string
    {
        $endpoints = ["{$startRow}:{$startColumn}", "{$endRow}:{$endColumn}"];
        sort($endpoints);

        return implode('|', $endpoints);
    }
}
