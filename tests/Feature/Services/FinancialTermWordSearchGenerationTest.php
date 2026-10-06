<?php

namespace Tests\Feature\Services;

use App\Exceptions\InsufficientFinancialTermsException;
use App\Models\FinancialTerm;
use App\Services\WordSearchGeneratorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Random\Engine\Mt19937;
use Random\Randomizer;
use Tests\TestCase;

class FinancialTermWordSearchGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_generation_selects_only_active_terms(): void
    {
        $activeTerms = collect([
            $this->createTerm('Ação', active: true),
            $this->createTerm('Crédito', active: true),
            $this->createTerm('Juros', active: true),
        ]);
        $inactiveTerm = $this->createTerm('Bitcoin', active: false);

        $result = $this->generator(seed: 1001)->generate(rows: 10, columns: 10, wordCount: 3);
        $selectedIds = collect($result->selectedTerms)->pluck('financialTermId');
        $expectedIds = $activeTerms->pluck('id')->sort()->values()->all();
        $actualIds = $selectedIds->sort()->values()->all();

        $this->assertSame($expectedIds, $actualIds);
        $this->assertNotContains($inactiveTerm->id, $selectedIds->all());
    }

    public function test_generate_uses_the_central_default_configuration(): void
    {
        config()->set('denarius.word_search.rows', 7);
        config()->set('denarius.word_search.columns', 9);
        config()->set('denarius.word_search.word_count', 2);
        $this->createTerm('Ação', active: true);
        $this->createTerm('Crédito', active: true);

        $result = $this->generator(seed: 1004)->generate();

        $this->assertSame(7, $result->rows);
        $this->assertSame(9, $result->columns);
        $this->assertCount(2, $result->placements);
    }

    public function test_normal_generation_fails_when_active_compatible_terms_are_insufficient(): void
    {
        $this->createTerm('Ação', active: true);
        $this->createTerm('Crédito', active: false);

        $this->expectException(InsufficientFinancialTermsException::class);
        $this->expectExceptionMessage('Foram solicitados 2 termos, mas somente 1');

        $this->generator(seed: 1002)->generate(rows: 10, columns: 10, wordCount: 2);
    }

    public function test_normal_generation_treats_oversized_terms_as_incompatible(): void
    {
        $this->createTerm('Financiamento', active: true);
        $this->createTerm('Pix', active: true);

        $this->expectException(InsufficientFinancialTermsException::class);
        $this->expectExceptionMessage('Foram solicitados 2 termos, mas somente 1');

        $this->generator(seed: 1003)->generate(rows: 5, columns: 5, wordCount: 2);
    }

    private function generator(int $seed): WordSearchGeneratorService
    {
        return new WordSearchGeneratorService(new Randomizer(new Mt19937($seed)));
    }

    private function createTerm(string $term, bool $active): FinancialTerm
    {
        return FinancialTerm::factory()->create([
            'term' => $term,
            'description' => "Descrição de {$term}.",
            'is_active' => $active,
        ]);
    }
}
