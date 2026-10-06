<?php

namespace App\Data;

use App\Enums\WordDirection;

readonly class WordPlacement
{
    public function __construct(
        public ?int $financialTermId,
        public string $originalTerm,
        public string $normalizedTerm,
        public WordCoordinate $start,
        public WordCoordinate $end,
        public WordDirection $direction,
    ) {}

    /**
     * @return array{
     *     financial_term_id: int|null,
     *     original_term: string,
     *     normalized_term: string,
     *     start: array{row: int, column: int},
     *     end: array{row: int, column: int},
     *     direction: string
     * }
     */
    public function toArray(): array
    {
        return [
            'financial_term_id' => $this->financialTermId,
            'original_term' => $this->originalTerm,
            'normalized_term' => $this->normalizedTerm,
            'start' => $this->start->toArray(),
            'end' => $this->end->toArray(),
            'direction' => $this->direction->value,
        ];
    }
}
