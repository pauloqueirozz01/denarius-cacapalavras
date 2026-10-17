<?php

namespace App\Data;

readonly class WordSearchResult
{
    /**
     * @param  list<list<string>>  $grid
     * @param  list<WordPlacement>  $placements
     * @param  list<SelectedFinancialTerm>  $selectedTerms
     */
    public function __construct(
        public array $grid,
        public int $rows,
        public int $columns,
        public array $placements,
        public array $selectedTerms,
    ) {}

    /**
     * @return array{
     *     grid: list<list<string>>,
     *     rows: int,
     *     columns: int,
     *     placements: list<array<string, mixed>>,
     *     selected_terms: list<array<string, mixed>>
     * }
     */
    public function toArray(): array
    {
        return [
            'grid' => $this->grid,
            'rows' => $this->rows,
            'columns' => $this->columns,
            'placements' => array_map(
                static fn (WordPlacement $placement): array => $placement->toArray(),
                $this->placements,
            ),
            'selected_terms' => array_map(
                static fn (SelectedFinancialTerm $term): array => $term->toArray(),
                $this->selectedTerms,
            ),
        ];
    }
}
