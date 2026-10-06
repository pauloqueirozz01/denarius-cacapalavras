<?php

namespace Tests\Unit\Services;

use App\Data\WordPlacement;
use App\Enums\FinancialTermDifficulty;
use App\Enums\WordDirection;
use App\Exceptions\InvalidWordSearchConfigurationException;
use App\Exceptions\InvalidWordSearchTermsException;
use App\Exceptions\WordSearchGenerationException;
use App\Models\FinancialTerm;
use App\Services\WordSearchGeneratorService;
use PHPUnit\Framework\Attributes\DataProvider;
use Random\Engine\Mt19937;
use Random\Randomizer;
use Tests\TestCase;

class WordSearchGeneratorServiceTest extends TestCase
{
    public function test_generates_a_complete_grid_with_the_requested_dimensions(): void
    {
        $result = $this->generator(seed: 101)->generateFromTerms(
            financialTerms: $this->terms('Crédito', 'Juros', 'Ação', 'Renda'),
            rows: 8,
            columns: 10,
        );

        $this->assertSame(8, $result->rows);
        $this->assertSame(10, $result->columns);
        $this->assertCount(8, $result->grid);
        $this->assertCount(4, $result->placements);
        $this->assertCount(4, $result->selectedTerms);

        foreach ($result->grid as $row) {
            $this->assertCount(10, $row);

            foreach ($row as $letter) {
                $this->assertMatchesRegularExpression('/\A[A-Z]\z/', $letter);
            }
        }
    }

    public function test_each_placement_reconstructs_its_word_inside_the_grid(): void
    {
        $result = $this->generator(seed: 202)->generateFromTerms(
            financialTerms: $this->terms('Investimento', 'Crédito', 'Juros', 'Pix'),
            rows: 15,
            columns: 15,
        );

        foreach ($result->placements as $placement) {
            $this->assertSame($placement->normalizedTerm, $this->readPlacement($result->grid, $placement));
            $this->assertGreaterThanOrEqual(0, $placement->start->row);
            $this->assertLessThan($result->rows, $placement->start->row);
            $this->assertGreaterThanOrEqual(0, $placement->start->column);
            $this->assertLessThan($result->columns, $placement->start->column);
            $this->assertGreaterThanOrEqual(0, $placement->end->row);
            $this->assertLessThan($result->rows, $placement->end->row);
            $this->assertGreaterThanOrEqual(0, $placement->end->column);
            $this->assertLessThan($result->columns, $placement->end->column);
        }
    }

    #[DataProvider('directions')]
    public function test_places_words_in_each_supported_direction(WordDirection $direction): void
    {
        $result = $this->generator(seed: 303)->generateFromTerms(
            financialTerms: $this->terms('Ação'),
            rows: 7,
            columns: 7,
            directions: [$direction],
        );
        $placement = $result->placements[0];

        $this->assertSame($direction, $placement->direction);
        $this->assertSame('ACAO', $this->readPlacement($result->grid, $placement));
        $this->assertSame(
            $placement->start->row + ($direction->rowDelta() * 3),
            $placement->end->row,
        );
        $this->assertSame(
            $placement->start->column + ($direction->columnDelta() * 3),
            $placement->end->column,
        );
    }

    /**
     * @return array<string, array{WordDirection}>
     */
    public static function directions(): array
    {
        return [
            'horizontal direita' => [WordDirection::Right],
            'horizontal esquerda' => [WordDirection::Left],
            'vertical baixo' => [WordDirection::Down],
            'vertical cima' => [WordDirection::Up],
            'diagonal baixo-direita' => [WordDirection::DownRight],
            'diagonal baixo-esquerda' => [WordDirection::DownLeft],
            'diagonal cima-direita' => [WordDirection::UpRight],
            'diagonal cima-esquerda' => [WordDirection::UpLeft],
        ];
    }

    public function test_preserves_the_original_term_and_uses_existing_normalization(): void
    {
        $result = $this->generator(seed: 404)->generateFromTerms(
            financialTerms: $this->terms('Ações'),
            rows: 6,
            columns: 6,
        );

        $this->assertSame('Ações', $result->selectedTerms[0]->originalTerm);
        $this->assertSame('ACOES', $result->selectedTerms[0]->normalizedTerm);
        $this->assertSame('ACOES', $result->placements[0]->normalizedTerm);
    }

    public function test_compatible_letters_can_share_a_cell(): void
    {
        $result = $this->generator(seed: 505)->generateFromTerms(
            financialTerms: $this->terms('AB', 'BC'),
            rows: 4,
            columns: 4,
        );
        $firstCoordinates = $this->placementCoordinates($result->placements[0]);
        $secondCoordinates = $this->placementCoordinates($result->placements[1]);

        $this->assertNotEmpty(array_intersect(array_keys($firstCoordinates), array_keys($secondCoordinates)));
    }

    public function test_incompatible_letters_never_overwrite_an_existing_word(): void
    {
        $result = $this->generator(seed: 606)->generateFromTerms(
            financialTerms: $this->terms('ABC', 'XYZ'),
            rows: 5,
            columns: 5,
        );
        $occupiedLetters = [];

        foreach ($result->placements as $placement) {
            foreach ($this->placementCoordinates($placement) as $coordinate => $letter) {
                if (isset($occupiedLetters[$coordinate])) {
                    $this->assertSame($occupiedLetters[$coordinate], $letter);
                }

                $occupiedLetters[$coordinate] = $letter;
            }
        }

        $this->assertSame(
            [],
            array_intersect_key(
                $this->placementCoordinates($result->placements[0]),
                $this->placementCoordinates($result->placements[1]),
            ),
        );
        $this->assertSame('ABC', $this->readPlacement($result->grid, $result->placements[0]));
        $this->assertSame('XYZ', $this->readPlacement($result->grid, $result->placements[1]));
    }

    public function test_same_seed_produces_the_same_result(): void
    {
        $terms = $this->terms('Crédito', 'Juros', 'Ação');

        $firstResult = $this->generator(seed: 707)->generateFromTerms($terms, rows: 8, columns: 8);
        $secondResult = $this->generator(seed: 707)->generateFromTerms($terms, rows: 8, columns: 8);

        $this->assertSame($firstResult->toArray(), $secondResult->toArray());
    }

    public function test_different_random_sources_can_produce_different_results(): void
    {
        $terms = $this->terms('Crédito', 'Juros', 'Ação');

        $firstResult = $this->generator(seed: 808)->generateFromTerms($terms, rows: 8, columns: 8);
        $secondResult = $this->generator(seed: 909)->generateFromTerms($terms, rows: 8, columns: 8);

        $this->assertNotSame($firstResult->toArray(), $secondResult->toArray());
    }

    public function test_rejects_terms_with_equivalent_normalization(): void
    {
        $this->expectException(InvalidWordSearchTermsException::class);
        $this->expectExceptionMessage("O termo normalizado 'ACAO' foi informado mais de uma vez.");

        $this->generator(seed: 1)->generateFromTerms(
            financialTerms: $this->terms('Ação', 'ACAO'),
            rows: 6,
            columns: 6,
        );
    }

    public function test_rejects_a_word_that_cannot_fit_the_grid(): void
    {
        $this->expectException(WordSearchGenerationException::class);
        $this->expectExceptionMessage("A palavra 'FINANCIAMENTO' não cabe no grid 5x5.");

        $this->generator(seed: 1)->generateFromTerms(
            financialTerms: $this->terms('Financiamento'),
            rows: 5,
            columns: 5,
        );
    }

    public function test_fails_after_bounded_attempts_when_words_cannot_all_be_placed(): void
    {
        config()->set('denarius.word_search.generation_attempts', 2);

        $this->expectException(WordSearchGenerationException::class);
        $this->expectExceptionMessage('após 2 tentativas');

        $this->generator(seed: 1)->generateFromTerms(
            financialTerms: $this->terms('AB', 'CD', 'EF'),
            rows: 2,
            columns: 2,
            directions: [WordDirection::Right],
        );
    }

    public function test_rejects_an_empty_direction_set(): void
    {
        $this->expectException(InvalidWordSearchConfigurationException::class);
        $this->expectExceptionMessage('Ao menos uma direção');

        $this->generator(seed: 1)->generateFromTerms(
            financialTerms: $this->terms('Ação'),
            rows: 6,
            columns: 6,
            directions: [],
        );
    }

    public function test_rejects_grid_dimensions_outside_the_configured_limits(): void
    {
        $this->expectException(InvalidWordSearchConfigurationException::class);
        $this->expectExceptionMessage('As dimensões 1x6 devem estar entre 2 e 50 células.');

        $this->generator(seed: 1)->generateFromTerms(
            financialTerms: $this->terms('Ação'),
            rows: 1,
            columns: 6,
        );
    }

    public function test_rejects_an_empty_explicit_term_set(): void
    {
        $this->expectException(InvalidWordSearchTermsException::class);
        $this->expectExceptionMessage('Ao menos um termo financeiro');

        $this->generator(seed: 1)->generateFromTerms(
            financialTerms: [],
            rows: 6,
            columns: 6,
        );
    }

    private function generator(int $seed): WordSearchGeneratorService
    {
        return new WordSearchGeneratorService(new Randomizer(new Mt19937($seed)));
    }

    /**
     * @return list<FinancialTerm>
     */
    private function terms(string ...$terms): array
    {
        return array_map(
            static fn (string $term): FinancialTerm => new FinancialTerm([
                'term' => $term,
                'description' => "Descrição de {$term}.",
                'difficulty' => FinancialTermDifficulty::Easy,
                'is_active' => true,
            ]),
            $terms,
        );
    }

    /**
     * @param  list<list<string>>  $grid
     */
    private function readPlacement(array $grid, WordPlacement $placement): string
    {
        $word = '';

        for ($offset = 0; $offset < strlen($placement->normalizedTerm); $offset++) {
            $row = $placement->start->row + ($placement->direction->rowDelta() * $offset);
            $column = $placement->start->column + ($placement->direction->columnDelta() * $offset);
            $word .= $grid[$row][$column];
        }

        return $word;
    }

    /**
     * @return array<string, string>
     */
    private function placementCoordinates(WordPlacement $placement): array
    {
        $coordinates = [];

        foreach (str_split($placement->normalizedTerm) as $offset => $letter) {
            $row = $placement->start->row + ($placement->direction->rowDelta() * $offset);
            $column = $placement->start->column + ($placement->direction->columnDelta() * $offset);
            $coordinates["{$row}:{$column}"] = $letter;
        }

        return $coordinates;
    }
}
