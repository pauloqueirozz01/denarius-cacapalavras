<?php

namespace App\Data;

readonly class WordCoordinate
{
    public function __construct(
        public int $row,
        public int $column,
    ) {}

    /**
     * @return array{row: int, column: int}
     */
    public function toArray(): array
    {
        return [
            'row' => $this->row,
            'column' => $this->column,
        ];
    }
}
