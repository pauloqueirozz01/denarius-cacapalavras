<?php

namespace Tests\Feature\FinancialTerm;

use App\Enums\FinancialTermDifficulty;
use App\Models\FinancialTerm;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FinancialTermTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_factory_produces_a_coherent_normalized_term(): void
    {
        $term = FinancialTerm::factory()->make(['term' => 'Tesouro Direto']);

        $this->assertSame('TESOURODIRETO', $term->normalized_term);
    }

    public function test_valid_term_is_created_with_automatic_normalization_and_casts(): void
    {
        $term = FinancialTerm::factory()->create([
            'term' => '  Renda   Fixa ',
            'difficulty' => FinancialTermDifficulty::Medium,
            'is_active' => 1,
        ]);

        $this->assertSame('Renda Fixa', $term->term);
        $this->assertSame('RENDAFIXA', $term->normalized_term);
        $this->assertSame(FinancialTermDifficulty::Medium, $term->difficulty);
        $this->assertTrue($term->is_active);
    }

    public function test_normalized_term_cannot_be_set_through_mass_assignment(): void
    {
        $term = FinancialTerm::create([
            'term' => 'Ação',
            'normalized_term' => 'ADMINISTRADOR',
            'description' => 'Parcela do capital de uma empresa.',
            'difficulty' => FinancialTermDifficulty::Easy,
            'is_active' => true,
        ]);

        $this->assertSame('ACAO', $term->normalized_term);
    }

    public function test_active_and_difficulty_scopes_return_only_matching_terms(): void
    {
        $easyActive = FinancialTerm::factory()->active()->create([
            'term' => 'Saldo',
            'difficulty' => FinancialTermDifficulty::Easy,
        ]);
        FinancialTerm::factory()->inactive()->create([
            'term' => 'Crédito',
            'difficulty' => FinancialTermDifficulty::Easy,
        ]);
        FinancialTerm::factory()->active()->create([
            'term' => 'Volatilidade',
            'difficulty' => FinancialTermDifficulty::Hard,
        ]);

        $terms = FinancialTerm::query()
            ->active()
            ->byDifficulty(FinancialTermDifficulty::Easy)
            ->get();

        $this->assertCount(1, $terms);
        $this->assertTrue($terms->first()->is($easyActive));
    }

    public function test_equivalent_normalized_terms_cannot_coexist(): void
    {
        FinancialTerm::factory()->create(['term' => 'Ação']);

        $this->expectException(QueryException::class);

        FinancialTerm::factory()->create(['term' => 'Acao']);
    }

    public function test_term_without_letters_is_rejected_before_persistence(): void
    {
        $this->expectException(ValidationException::class);

        FinancialTerm::factory()->create(['term' => '2026 - !!!']);
    }

    public function test_term_longer_than_grid_limit_is_rejected_before_persistence(): void
    {
        $this->expectException(ValidationException::class);

        FinancialTerm::factory()->create(['term' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ']);
    }
}
