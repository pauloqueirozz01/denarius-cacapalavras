<?php

namespace Tests\Feature\FinancialTerm;

use App\Models\FinancialTerm;
use Database\Seeders\FinancialTermSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class FinancialTermSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_seeder_creates_a_complete_valid_catalog(): void
    {
        $this->seed(FinancialTermSeeder::class);

        $terms = FinancialTerm::query()->get();
        $this->assertCount(90, $terms);
        $this->assertSame(90, $terms->pluck('normalized_term')->unique()->count());

        foreach ($terms as $term) {
            $this->assertNotSame('', $term->description);
            $this->assertNotNull($term->difficulty);
            $this->assertTrue($term->is_active);
            $this->assertMatchesRegularExpression('/\A[A-Z]+\z/', $term->normalized_term);
            $this->assertLessThanOrEqual(24, strlen($term->normalized_term));
        }
    }

    public function test_seeder_is_idempotent_and_preserves_administrator_changes(): void
    {
        $this->seed(FinancialTermSeeder::class);
        $term = FinancialTerm::query()->where('normalized_term', 'ACAO')->sole();
        $term->update([
            'description' => 'Descrição personalizada pela equipe Denarius.',
            'is_active' => false,
        ]);

        $this->seed(FinancialTermSeeder::class);

        $this->assertDatabaseCount('financial_terms', 90);
        $this->assertDatabaseHas('financial_terms', [
            'id' => $term->id,
            'description' => 'Descrição personalizada pela equipe Denarius.',
            'is_active' => false,
        ]);
    }
}
