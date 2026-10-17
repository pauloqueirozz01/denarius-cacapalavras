<?php

namespace App\Services;

use App\Data\SelectedFinancialTerm;
use App\Data\WordCoordinate;
use App\Data\WordPlacement;
use App\Data\WordSearchResult;
use App\Enums\WordDirection;
use App\Exceptions\InsufficientFinancialTermsException;
use App\Exceptions\InvalidWordSearchConfigurationException;
use App\Exceptions\InvalidWordSearchTermsException;
use App\Exceptions\WordSearchGenerationException;
use App\Models\FinancialTerm;
use Illuminate\Support\Collection;
use Random\Randomizer;

class WordSearchGeneratorService
{
    private readonly Randomizer $randomizer;

    public function __construct(?Randomizer $randomizer = null)
    {
        $this->randomizer = $randomizer ?? new Randomizer;
    }

    /**
     * Select active terms with one query and generate a complete word-search grid in memory.
     *
     * @throws InsufficientFinancialTermsException
     * @throws InvalidWordSearchConfigurationException
     * @throws WordSearchGenerationException
     */
    public function generate(
        ?int $rows = null,
        ?int $columns = null,
        ?int $wordCount = null,
    ): WordSearchResult {
        [$resolvedRows, $resolvedColumns, $resolvedWordCount, $generationAttempts] = $this->resolveConfiguration(
            rows: $rows,
            columns: $columns,
            wordCount: $wordCount,
        );

        $activeTerms = FinancialTerm::query()
            ->active()
            ->select(['id', 'term', 'normalized_term'])
            ->orderBy('id')
            ->get();

        $selectedTerms = $this->selectTerms(
            financialTerms: $activeTerms,
            wordCount: $resolvedWordCount,
            rows: $resolvedRows,
            columns: $resolvedColumns,
        );

        return $this->generateGrid(
            selectedTerms: $selectedTerms,
            rows: $resolvedRows,
            columns: $resolvedColumns,
            generationAttempts: $generationAttempts,
            directions: WordDirection::cases(),
        );
    }

    /**
     * Generate a grid from an explicit set of terms. Active-state filtering belongs to generate().
     *
     * @param  iterable<FinancialTerm>  $financialTerms
     * @param  list<WordDirection>|null  $directions
     *
     * @throws InvalidWordSearchConfigurationException
     * @throws InvalidWordSearchTermsException
     * @throws WordSearchGenerationException
     */
    public function generateFromTerms(
        iterable $financialTerms,
        ?int $rows = null,
        ?int $columns = null,
        ?array $directions = null,
    ): WordSearchResult {
        $terms = $this->prepareExplicitTerms($financialTerms);
        [$resolvedRows, $resolvedColumns, , $generationAttempts] = $this->resolveConfiguration(
            rows: $rows,
            columns: $columns,
            wordCount: count($terms),
        );
        $resolvedDirections = $directions ?? WordDirection::cases();

        if ($resolvedDirections === []) {
            throw InvalidWordSearchConfigurationException::missingDirections();
        }

        foreach ($terms as $term) {
            if (! $this->wordCanFitAnyDirection($term->normalizedTerm, $resolvedRows, $resolvedColumns, $resolvedDirections)) {
                throw WordSearchGenerationException::wordDoesNotFit(
                    normalizedTerm: $term->normalizedTerm,
                    rows: $resolvedRows,
                    columns: $resolvedColumns,
                );
            }
        }

        return $this->generateGrid(
            selectedTerms: $terms,
            rows: $resolvedRows,
            columns: $resolvedColumns,
            generationAttempts: $generationAttempts,
            directions: $resolvedDirections,
        );
    }

    /**
     * @return array{int, int, int, int}
     *
     * @throws InvalidWordSearchConfigurationException
     */
    private function resolveConfiguration(?int $rows, ?int $columns, ?int $wordCount): array
    {
        $resolvedRows = $rows ?? (int) config('denarius.word_search.rows');
        $resolvedColumns = $columns ?? (int) config('denarius.word_search.columns');
        $resolvedWordCount = $wordCount ?? (int) config('denarius.word_search.word_count');
        $generationAttempts = (int) config('denarius.word_search.generation_attempts');
        $minimumDimension = (int) config('denarius.word_search.minimum_dimension');
        $maximumDimension = (int) config('denarius.word_search.maximum_dimension');
        $maximumWordCount = (int) config('denarius.word_search.maximum_word_count');

        if (
            $resolvedRows < $minimumDimension
            || $resolvedColumns < $minimumDimension
            || $resolvedRows > $maximumDimension
            || $resolvedColumns > $maximumDimension
        ) {
            throw InvalidWordSearchConfigurationException::invalidDimensions(
                rows: $resolvedRows,
                columns: $resolvedColumns,
                minimum: $minimumDimension,
                maximum: $maximumDimension,
            );
        }

        if ($resolvedWordCount < 1 || $resolvedWordCount > $maximumWordCount) {
            throw InvalidWordSearchConfigurationException::invalidWordCount($resolvedWordCount, $maximumWordCount);
        }

        if ($generationAttempts < 1) {
            throw InvalidWordSearchConfigurationException::invalidGenerationAttempts($generationAttempts);
        }

        return [$resolvedRows, $resolvedColumns, $resolvedWordCount, $generationAttempts];
    }

    /**
     * @param  Collection<int, FinancialTerm>  $financialTerms
     * @return list<SelectedFinancialTerm>
     *
     * @throws InsufficientFinancialTermsException
     */
    private function selectTerms(Collection $financialTerms, int $wordCount, int $rows, int $columns): array
    {
        $eligibleTerms = $financialTerms
            ->map(static fn (FinancialTerm $term): SelectedFinancialTerm => SelectedFinancialTerm::fromModel($term))
            ->unique('normalizedTerm')
            ->filter(fn (SelectedFinancialTerm $term): bool => $this->wordCanFitAnyDirection(
                normalizedTerm: $term->normalizedTerm,
                rows: $rows,
                columns: $columns,
                directions: WordDirection::cases(),
            ))
            ->values()
            ->all();

        if (count($eligibleTerms) < $wordCount) {
            throw InsufficientFinancialTermsException::forRequestedCount($wordCount, count($eligibleTerms));
        }

        return array_slice($this->randomizer->shuffleArray($eligibleTerms), 0, $wordCount);
    }

    /**
     * @param  iterable<FinancialTerm>  $financialTerms
     * @return list<SelectedFinancialTerm>
     *
     * @throws InvalidWordSearchTermsException
     */
    private function prepareExplicitTerms(iterable $financialTerms): array
    {
        $selectedTerms = [];
        $normalizedTerms = [];

        foreach ($financialTerms as $financialTerm) {
            $selectedTerm = SelectedFinancialTerm::fromModel($financialTerm);

            if ($selectedTerm->normalizedTerm === '') {
                throw InvalidWordSearchTermsException::emptyNormalizedTerm($selectedTerm->originalTerm);
            }

            if (isset($normalizedTerms[$selectedTerm->normalizedTerm])) {
                throw InvalidWordSearchTermsException::duplicateNormalizedTerm($selectedTerm->normalizedTerm);
            }

            $normalizedTerms[$selectedTerm->normalizedTerm] = true;
            $selectedTerms[] = $selectedTerm;
        }

        if ($selectedTerms === []) {
            throw InvalidWordSearchTermsException::noTerms();
        }

        return $selectedTerms;
    }

    /**
     * @param  list<SelectedFinancialTerm>  $selectedTerms
     * @param  list<WordDirection>  $directions
     *
     * @throws WordSearchGenerationException
     */
    private function generateGrid(
        array $selectedTerms,
        int $rows,
        int $columns,
        int $generationAttempts,
        array $directions,
    ): WordSearchResult {
        for ($attempt = 0; $attempt < $generationAttempts; $attempt++) {
            $grid = array_fill(0, $rows, array_fill(0, $columns, null));
            $placements = [];
            $orderedTerms = $this->orderTermsForPlacement($selectedTerms);
            $allTermsPlaced = true;

            foreach ($orderedTerms as $term) {
                $candidate = $this->choosePlacementCandidate(
                    grid: $grid,
                    normalizedTerm: $term->normalizedTerm,
                    rows: $rows,
                    columns: $columns,
                    directions: $directions,
                );

                if ($candidate === null) {
                    $allTermsPlaced = false;

                    break;
                }

                $this->applyWordToGrid(
                    grid: $grid,
                    normalizedTerm: $term->normalizedTerm,
                    startRow: $candidate['row'],
                    startColumn: $candidate['column'],
                    direction: $candidate['direction'],
                );

                $placements[] = $this->makePlacement($term, $candidate);
            }

            if ($allTermsPlaced) {
                return new WordSearchResult(
                    grid: $this->fillEmptyCells($grid),
                    rows: $rows,
                    columns: $columns,
                    placements: $placements,
                    selectedTerms: $selectedTerms,
                );
            }
        }

        throw WordSearchGenerationException::unableToPlaceWords($generationAttempts);
    }

    /**
     * Longer words are placed first; equal lengths remain randomized between attempts.
     *
     * @param  list<SelectedFinancialTerm>  $selectedTerms
     * @return list<SelectedFinancialTerm>
     */
    private function orderTermsForPlacement(array $selectedTerms): array
    {
        $orderedTerms = $this->randomizer->shuffleArray($selectedTerms);

        usort(
            $orderedTerms,
            static fn (SelectedFinancialTerm $left, SelectedFinancialTerm $right): int => strlen($right->normalizedTerm) <=> strlen($left->normalizedTerm),
        );

        return $orderedTerms;
    }

    /**
     * @param  list<list<string|null>>  $grid
     * @param  list<WordDirection>  $directions
     * @return array{row: int, column: int, direction: WordDirection, crossings: int}|null
     */
    private function choosePlacementCandidate(
        array $grid,
        string $normalizedTerm,
        int $rows,
        int $columns,
        array $directions,
    ): ?array {
        $candidates = [];

        foreach ($directions as $direction) {
            for ($row = 0; $row < $rows; $row++) {
                for ($column = 0; $column < $columns; $column++) {
                    $crossings = $this->countCompatibleCrossings(
                        grid: $grid,
                        normalizedTerm: $normalizedTerm,
                        startRow: $row,
                        startColumn: $column,
                        direction: $direction,
                        rows: $rows,
                        columns: $columns,
                    );

                    if ($crossings !== null) {
                        $candidates[] = [
                            'row' => $row,
                            'column' => $column,
                            'direction' => $direction,
                            'crossings' => $crossings,
                        ];
                    }
                }
            }
        }

        if ($candidates === []) {
            return null;
        }

        $maximumCrossings = max(array_column($candidates, 'crossings'));
        $bestCandidates = array_values(array_filter(
            $candidates,
            static fn (array $candidate): bool => $candidate['crossings'] === $maximumCrossings,
        ));

        return $bestCandidates[$this->randomizer->getInt(0, count($bestCandidates) - 1)];
    }

    /**
     * The full candidate is validated before the grid is mutated.
     *
     * @param  list<list<string|null>>  $grid
     */
    private function countCompatibleCrossings(
        array $grid,
        string $normalizedTerm,
        int $startRow,
        int $startColumn,
        WordDirection $direction,
        int $rows,
        int $columns,
    ): ?int {
        $letters = str_split($normalizedTerm);
        $endRow = $startRow + (($direction->rowDelta()) * (count($letters) - 1));
        $endColumn = $startColumn + (($direction->columnDelta()) * (count($letters) - 1));

        if (! $this->coordinateIsInside($endRow, $endColumn, $rows, $columns)) {
            return null;
        }

        $crossings = 0;

        foreach ($letters as $offset => $letter) {
            $row = $startRow + ($direction->rowDelta() * $offset);
            $column = $startColumn + ($direction->columnDelta() * $offset);
            $existingLetter = $grid[$row][$column];

            if ($existingLetter !== null && $existingLetter !== $letter) {
                return null;
            }

            if ($existingLetter === $letter) {
                $crossings++;
            }
        }

        return $crossings;
    }

    /**
     * @param  list<list<string|null>>  $grid
     */
    private function applyWordToGrid(
        array &$grid,
        string $normalizedTerm,
        int $startRow,
        int $startColumn,
        WordDirection $direction,
    ): void {
        foreach (str_split($normalizedTerm) as $offset => $letter) {
            $row = $startRow + ($direction->rowDelta() * $offset);
            $column = $startColumn + ($direction->columnDelta() * $offset);
            $grid[$row][$column] = $letter;
        }
    }

    /**
     * @param  array{row: int, column: int, direction: WordDirection, crossings: int}  $candidate
     */
    private function makePlacement(SelectedFinancialTerm $term, array $candidate): WordPlacement
    {
        $lastOffset = strlen($term->normalizedTerm) - 1;

        return new WordPlacement(
            financialTermId: $term->financialTermId,
            originalTerm: $term->originalTerm,
            normalizedTerm: $term->normalizedTerm,
            start: new WordCoordinate($candidate['row'], $candidate['column']),
            end: new WordCoordinate(
                row: $candidate['row'] + ($candidate['direction']->rowDelta() * $lastOffset),
                column: $candidate['column'] + ($candidate['direction']->columnDelta() * $lastOffset),
            ),
            direction: $candidate['direction'],
        );
    }

    /**
     * @param  list<list<string|null>>  $grid
     * @return list<list<string>>
     */
    private function fillEmptyCells(array $grid): array
    {
        $alphabet = str_split((string) config('denarius.word_search.alphabet'));

        foreach ($grid as $rowIndex => $row) {
            foreach ($row as $columnIndex => $letter) {
                if ($letter === null) {
                    $grid[$rowIndex][$columnIndex] = $alphabet[
                        $this->randomizer->getInt(0, count($alphabet) - 1)
                    ];
                }
            }
        }

        /** @var list<list<string>> $grid */
        return $grid;
    }

    /**
     * @param  list<WordDirection>  $directions
     */
    private function wordCanFitAnyDirection(
        string $normalizedTerm,
        int $rows,
        int $columns,
        array $directions,
    ): bool {
        $lastOffset = strlen($normalizedTerm) - 1;

        foreach ($directions as $direction) {
            $rowSpan = abs($direction->rowDelta() * $lastOffset) + 1;
            $columnSpan = abs($direction->columnDelta() * $lastOffset) + 1;

            if ($rowSpan <= $rows && $columnSpan <= $columns) {
                return true;
            }
        }

        return false;
    }

    private function coordinateIsInside(int $row, int $column, int $rows, int $columns): bool
    {
        return $row >= 0 && $row < $rows && $column >= 0 && $column < $columns;
    }
}
